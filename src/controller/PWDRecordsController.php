<?php

declare(strict_types=1);

/**
 * PWDRecordsController
 * 
 * Handles endpoints for managing PWD records, including support for combined data submission
 * (PWD records with guardians, education, support needs, and supporting documents)
 */
class PWDRecordsController
{
    private PWDRecords $pwdModel;
    private PWDGuardian $guardianModel;
    private PWDEducation $educationModel;
    private PWDSupportNeeds $supportNeedsModel;
    private SupportingDocument $documentModel;
    private ActivityLog $logModel;
    private PDO $db;

    public function __construct(
        PWDRecords $pwdModel,
        PWDGuardian $guardianModel,
        PWDEducation $educationModel,
        PWDSupportNeeds $supportNeedsModel,
        SupportingDocument $documentModel,
        ActivityLog $logModel,
        PDO $db
    ) {
        $this->pwdModel = $pwdModel;
        $this->guardianModel = $guardianModel;
        $this->educationModel = $educationModel;
        $this->supportNeedsModel = $supportNeedsModel;
        $this->documentModel = $documentModel;
        $this->logModel = $logModel;
        $this->db = $db;
    }

    /**
     * Get all PWD records (with pagination)
     */
    public function index($request, $response)
    {
        $queryParams = $request->getQueryParams();
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : null;
        $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : null;

        $pwds = $this->pwdModel->getAll($limit, $offset);
        $totalCount = $this->pwdModel->getCount();

        $result = [
            'pwds' => $pwds,
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
     * Get PWD records by status
     */
    public function getByStatus($request, $response, $args)
    {
        $status = $args['status'];
        $pwds = $this->pwdModel->getByStatus($status);

        $response->getBody()->write(json_encode(['pwds' => $pwds]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get PWD records by community
     */
    public function getByCommunity($request, $response, $args)
    {
        $communityId = (int)$args['community_id'];
        $pwds = $this->pwdModel->getByCommunityId($communityId);

        $response->getBody()->write(json_encode(['pwds' => $pwds]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Search PWD records
     */
    public function search($request, $response)
    {
        $queryParams = $request->getQueryParams();
        $term = $queryParams['term'] ?? '';

        $pwds = $this->pwdModel->search($term);

        $response->getBody()->write(json_encode(['pwds' => $pwds]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get PWD record by ID with related data
     */
    public function show($request, $response, $args)
    {
        $pwdId = (int)$args['id'];
        $pwd = $this->pwdModel->getById($pwdId);

        if (!$pwd) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Get related data
        $pwd['guardians'] = $this->guardianModel->getByPWDId($pwdId);
        $pwd['education'] = $this->educationModel->getByPWDId($pwdId);
        $pwd['support_needs'] = $this->supportNeedsModel->getByPWDId($pwdId);
        $pwd['documents'] = $this->documentModel->getByRelatedEntity('pwd', $pwdId);

        $response->getBody()->write(json_encode($pwd));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Create a new PWD record with related data
     */
    public function create($request, $response)
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id'); // Assumes middleware sets this

        // Start a transaction
        $this->db->beginTransaction();

        try {
            // 1. Create the PWD record
            $pwdId = $this->pwdModel->create($data);

            if (!$pwdId) {
                throw new Exception('Failed to create PWD record: ' . $this->pwdModel->getLastError());
            }

            // 2. Add guardians if provided
            if (isset($data['guardians']) && is_array($data['guardians'])) {
                foreach ($data['guardians'] as $guardian) {
                    $guardian['pwd_id'] = $pwdId;
                    $guardianId = $this->guardianModel->create($guardian);

                    if (!$guardianId) {
                        throw new Exception('Failed to create guardian: ' . $this->guardianModel->getLastError());
                    }
                }
            }

            // 3. Add education records if provided
            if (isset($data['education']) && is_array($data['education'])) {
                foreach ($data['education'] as $education) {
                    $education['pwd_id'] = $pwdId;
                    $educationId = $this->educationModel->create($education);

                    if (!$educationId) {
                        throw new Exception('Failed to create education record: ' . $this->educationModel->getLastError());
                    }
                }
            }

            // 4. Add support needs if provided
            if (isset($data['support_needs']) && is_array($data['support_needs'])) {
                foreach ($data['support_needs'] as $need) {
                    $need['pwd_id'] = $pwdId;
                    $needId = $this->supportNeedsModel->create($need);

                    if (!$needId) {
                        throw new Exception('Failed to create support need: ' . $this->supportNeedsModel->getLastError());
                    }
                }
            }

            // Log the activity
            $this->logModel->log($userId, "Created PWD record for {$data['full_name']} with ID {$pwdId}");

            // Commit the transaction
            $this->db->commit();

            $pwd = $this->pwdModel->getById($pwdId);
            $pwd['guardians'] = $this->guardianModel->getByPWDId($pwdId);
            $pwd['education'] = $this->educationModel->getByPWDId($pwdId);
            $pwd['support_needs'] = $this->supportNeedsModel->getByPWDId($pwdId);

            $response->getBody()->write(json_encode([
                'message' => 'PWD record created successfully',
                'pwd' => $pwd
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (Exception $e) {
            // Roll back the transaction on error
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
     * Update a PWD record with related data
     */
    public function update($request, $response, $args)
    {
        $pwdId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id'); // Assumes middleware sets this

        // Check if PWD record exists
        $existingPWD = $this->pwdModel->getById($pwdId);
        if (!$existingPWD) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Start a transaction
        $this->db->beginTransaction();

        try {
            // 1. Update the PWD record
            $success = $this->pwdModel->update($pwdId, $data);

            if (!$success) {
                throw new Exception('Failed to update PWD record: ' . $this->pwdModel->getLastError());
            }

            // 2. Update guardians if provided
            if (isset($data['guardians']) && is_array($data['guardians'])) {
                // Optional: Delete existing guardians first
                // $this->guardianModel->deleteByPWDId($pwdId);

                foreach ($data['guardians'] as $guardian) {
                    $guardian['pwd_id'] = $pwdId;

                    if (isset($guardian['guardian_id'])) {
                        // Update existing guardian
                        $success = $this->guardianModel->update($guardian['guardian_id'], $guardian);
                        if (!$success) {
                            throw new Exception('Failed to update guardian: ' . $this->guardianModel->getLastError());
                        }
                    } else {
                        // Create new guardian
                        $guardianId = $this->guardianModel->create($guardian);
                        if (!$guardianId) {
                            throw new Exception('Failed to create guardian: ' . $this->guardianModel->getLastError());
                        }
                    }
                }
            }

            // 3. Update education records if provided
            if (isset($data['education']) && is_array($data['education'])) {
                // Optional: Delete existing education records first
                // $this->educationModel->deleteByPWDId($pwdId);

                foreach ($data['education'] as $education) {
                    $education['pwd_id'] = $pwdId;

                    if (isset($education['education_id'])) {
                        // Update existing education record
                        $success = $this->educationModel->update($education['education_id'], $education);
                        if (!$success) {
                            throw new Exception('Failed to update education record: ' . $this->educationModel->getLastError());
                        }
                    } else {
                        // Create new education record
                        $educationId = $this->educationModel->create($education);
                        if (!$educationId) {
                            throw new Exception('Failed to create education record: ' . $this->educationModel->getLastError());
                        }
                    }
                }
            }

            // 4. Update support needs if provided
            if (isset($data['support_needs']) && is_array($data['support_needs'])) {
                // Optional: Delete existing support needs first
                // $this->supportNeedsModel->deleteByPWDId($pwdId);

                foreach ($data['support_needs'] as $need) {
                    $need['pwd_id'] = $pwdId;

                    if (isset($need['need_id'])) {
                        // Update existing support need
                        $success = $this->supportNeedsModel->update($need['need_id'], $need);
                        if (!$success) {
                            throw new Exception('Failed to update support need: ' . $this->supportNeedsModel->getLastError());
                        }
                    } else {
                        // Create new support need
                        $needId = $this->supportNeedsModel->create($need);
                        if (!$needId) {
                            throw new Exception('Failed to create support need: ' . $this->supportNeedsModel->getLastError());
                        }
                    }
                }
            }

            // Log the activity
            $this->logModel->log($userId, "Updated PWD record for {$existingPWD['full_name']} with ID {$pwdId}");

            // Commit the transaction
            $this->db->commit();

            $pwd = $this->pwdModel->getById($pwdId);
            $pwd['guardians'] = $this->guardianModel->getByPWDId($pwdId);
            $pwd['education'] = $this->educationModel->getByPWDId($pwdId);
            $pwd['support_needs'] = $this->supportNeedsModel->getByPWDId($pwdId);

            $response->getBody()->write(json_encode([
                'message' => 'PWD record updated successfully',
                'pwd' => $pwd
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (Exception $e) {
            // Roll back the transaction on error
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
     * Update PWD status
     */
    public function updateStatus($request, $response, $args)
    {
        $pwdId = (int)$args['id'];
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

        $existingPWD = $this->pwdModel->getById($pwdId);
        if (!$existingPWD) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $success = $this->pwdModel->updateStatus($pwdId, $data['status']);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to update status: ' . $this->pwdModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Updated PWD status for {$existingPWD['full_name']} to {$data['status']}");

        $pwd = $this->pwdModel->getById($pwdId);

        $response->getBody()->write(json_encode([
            'message' => 'PWD status updated successfully',
            'pwd' => $pwd
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Delete a PWD record and all related data
     */
    public function delete($request, $response, $args)
    {
        $pwdId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        $existingPWD = $this->pwdModel->getById($pwdId);
        if (!$existingPWD) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Delete PWD record (this will cascade delete related records)
        $success = $this->pwdModel->delete($pwdId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to delete PWD record: ' . $this->pwdModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Deleted PWD record for {$existingPWD['full_name']} with ID {$pwdId}");

        $response->getBody()->write(json_encode([
            'message' => 'PWD record deleted successfully'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get statistics by quarter and year
     */
    public function getStatistics($request, $response, $args)
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

        $stats = $this->pwdModel->getStatsByQuarterYear($quarter, $year);

        $response->getBody()->write(json_encode($stats));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
