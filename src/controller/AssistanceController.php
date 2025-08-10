<?php

declare(strict_types=1);

/**
 * AssistanceController
 * 
 * Handles operations related to assistance records
 */
class AssistanceController
{
    private Assistance $assistanceModel;
    private PWDRecords $pwdModel;
    private SupportingDocument $documentModel;
    private AssistanceDistribution $distributionModel;
    private ActivityLog $logModel;
    private PDO $db;

    public function __construct(
        Assistance $assistanceModel,
        PWDRecords $pwdModel,
        SupportingDocument $documentModel,
        AssistanceDistribution $distributionModel,
        ActivityLog $logModel,
        PDO $db
    ) {
        $this->assistanceModel = $assistanceModel;
        $this->pwdModel = $pwdModel;
        $this->documentModel = $documentModel;
        $this->distributionModel = $distributionModel;
        $this->logModel = $logModel;
        $this->db = $db;
    }

    /**
     * Get all assistance records (with pagination)
     */
    public function index($request, $response)
    {
        $assistanceRecords = $this->assistanceModel->getAll($limit, $offset);
        $totalCount = $this->assistanceModel->getCount();

        $result = [
            'assistance_records' => $assistanceRecords,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ];

        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get assistance by status
     */
    public function getByStatus($request, $response, $args)
    {
        $status = $args['status'];
        $assistanceRecords = $this->assistanceModel->getByStatus($status);

        $response->getBody()->write(json_encode([
            'assistance_records' => $assistanceRecords
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get assistance records by PWD ID
     */
    public function getByPWD($request, $response, $args)
    {
        $pwdId = (int)$args['pwd_id'];

        // Check if PWD exists
        $pwd = $this->pwdModel->getById($pwdId);
        if (!$pwd) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $assistanceRecords = $this->assistanceModel->getByPWDId($pwdId);

        $response->getBody()->write(json_encode([
            'pwd' => $pwd,
            'assistance_records' => $assistanceRecords
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get assistance by quarter and year
     */
    public function getByQuarter($request, $response, $args)
    {
        $quarter = $args['quarter'] ?? null;
        $year = isset($args['year']) ? (int)$args['year'] : null;

        if (!$quarter || !$year) {
            $response->getBody()->write(json_encode([
                'error' => 'Quarter and year parameters are required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $assistanceRecords = $this->assistanceModel->getByQuarterYear($quarter, $year);

        $response->getBody()->write(json_encode([
            'assistance_records' => $assistanceRecords,
            'quarter' => $quarter,
            'year' => $year
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get a single assistance record
     */
    public function show($request, $response, $args)
    {
        $assistanceId = (int)$args['id'];
        $assistance = $this->assistanceModel->getById($assistanceId);

        if (!$assistance) {
            $response->getBody()->write(json_encode([
                'error' => 'Assistance record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Get related data
        $assistance['documents'] = $this->documentModel->getByRelatedEntity('assistance', $assistanceId);
        $assistance['distribution'] = $this->distributionModel->getByAssistanceId($assistanceId);

        if ($assistance['pwd_id']) {
            $assistance['pwd'] = $this->pwdModel->getById($assistance['pwd_id']);
        }

        $response->getBody()->write(json_encode($assistance));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Create a new assistance record
     */
    public function create($request, $response)
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate PWD ID if provided
        if (isset($data['pwd_id']) && $data['pwd_id']) {
            $pwd = $this->pwdModel->getById($data['pwd_id']);
            if (!$pwd) {
                $response->getBody()->write(json_encode([
                    'error' => 'PWD record not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
        }

        // Start transaction for related data
        $this->db->beginTransaction();

        try {
            // Create assistance record
            $assistanceId = $this->assistanceModel->create($data);

            if (!$assistanceId) {
                throw new Exception('Failed to create assistance record: ' . $this->assistanceModel->getLastError());
            }

            // Add distribution records if provided
            if (isset($data['distribution']) && is_array($data['distribution'])) {
                foreach ($data['distribution'] as $distribution) {
                    $distribution['assistance_id'] = $assistanceId;
                    $distributionId = $this->distributionModel->create($distribution);

                    if (!$distributionId) {
                        throw new Exception('Failed to create distribution record: ' . $this->distributionModel->getLastError());
                    }
                }
            }

            $this->logModel->log($userId, "Created assistance record #{$assistanceId}");

            // Commit transaction
            $this->db->commit();

            $assistance = $this->assistanceModel->getById($assistanceId);
            $assistance['distribution'] = $this->distributionModel->getByAssistanceId($assistanceId);

            $response->getBody()->write(json_encode([
                'message' => 'Assistance record created successfully',
                'assistance' => $assistance
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (Exception $e) {
            // Roll back transaction on error
            $this->db->rollBack();

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Update an existing assistance record
     */
    public function update($request, $response, $args)
    {
        $assistanceId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Check if assistance record exists
        $existingAssistance = $this->assistanceModel->getById($assistanceId);
        if (!$existingAssistance) {
            $response->getBody()->write(json_encode([
                'error' => 'Assistance record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Start transaction for related data
        $this->db->beginTransaction();

        try {
            // Update assistance record
            $success = $this->assistanceModel->update($assistanceId, $data);

            if (!$success) {
                throw new Exception('Failed to update assistance record: ' . $this->assistanceModel->getLastError());
            }

            // Update distribution records if provided
            if (isset($data['distribution']) && is_array($data['distribution'])) {
                // Option: Delete existing distribution records first
                // $this->distributionModel->deleteByAssistanceId($assistanceId);

                foreach ($data['distribution'] as $distribution) {
                    $distribution['assistance_id'] = $assistanceId;

                    if (isset($distribution['distribution_id'])) {
                        // Update existing distribution record
                        $success = $this->distributionModel->update($distribution['distribution_id'], $distribution);
                        if (!$success) {
                            throw new Exception('Failed to update distribution record: ' . $this->distributionModel->getLastError());
                        }
                    } else {
                        // Create new distribution record
                        $distributionId = $this->distributionModel->create($distribution);
                        if (!$distributionId) {
                            throw new Exception('Failed to create distribution record: ' . $this->distributionModel->getLastError());
                        }
                    }
                }
            }

            $this->logModel->log($userId, "Updated assistance record #{$assistanceId}");

            // Commit transaction
            $this->db->commit();

            $assistance = $this->assistanceModel->getById($assistanceId);
            $assistance['distribution'] = $this->distributionModel->getByAssistanceId($assistanceId);

            $response->getBody()->write(json_encode([
                'message' => 'Assistance record updated successfully',
                'assistance' => $assistance
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (Exception $e) {
            // Roll back transaction on error
            $this->db->rollBack();

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Update assistance status
     */
    public function updateStatus($request, $response, $args)
    {
        $assistanceId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        if (!isset($data['status'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Status field is required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $existingAssistance = $this->assistanceModel->getById($assistanceId);
        if (!$existingAssistance) {
            $response->getBody()->write(json_encode([
                'error' => 'Assistance record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $success = $this->assistanceModel->updateStatus($assistanceId, $data['status']);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to update status: ' . $this->assistanceModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Updated assistance #{$assistanceId} status to {$data['status']}");

        $assistance = $this->assistanceModel->getById($assistanceId);

        $response->getBody()->write(json_encode([
            'message' => 'Assistance status updated successfully',
            'assistance' => $assistance
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Delete an assistance record
     */
    public function delete($request, $response, $args)
    {
        $assistanceId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        $existingAssistance = $this->assistanceModel->getById($assistanceId);
        if (!$existingAssistance) {
            $response->getBody()->write(json_encode([
                'error' => 'Assistance record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Start transaction for related data
        $this->db->beginTransaction();

        try {
            // Delete distribution records
            $this->distributionModel->deleteByAssistanceId($assistanceId);

            // Delete documents (or optionally just unlink them)
            $documents = $this->documentModel->getByRelatedEntity('assistance', $assistanceId);
            foreach ($documents as $doc) {
                $this->documentModel->delete($doc['document_id']);
            }

            // Delete assistance record
            $success = $this->assistanceModel->delete($assistanceId);

            if (!$success) {
                throw new Exception('Failed to delete assistance record: ' . $this->assistanceModel->getLastError());
            }

            $this->logModel->log($userId, "Deleted assistance record #{$assistanceId}");

            // Commit transaction
            $this->db->commit();

            $response->getBody()->write(json_encode([
                'message' => 'Assistance record deleted successfully'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (Exception $e) {
            // Roll back transaction on error
            $this->db->rollBack();

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * Get statistics on assistance distribution
     */
    public function getStatistics($request, $response)
    {
        $queryParams = $request->getQueryParams();
        $quarter = $queryParams['quarter'] ?? null;
        $year = isset($queryParams['year']) ? (int)$queryParams['year'] : null;

        if (!$year) {
            $year = (int)date('Y');
        }

        if ($quarter) {
            $stats = $this->assistanceModel->getStatsByQuarterYear($quarter, $year);
        } else {
            $stats = $this->assistanceModel->getStatsByYear($year);
        }

        $response->getBody()->write(json_encode($stats));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
