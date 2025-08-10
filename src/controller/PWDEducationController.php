<?php

declare(strict_types=1);

/**
 * PWDEducationController
 * 
 * Handles operations related to PWD education records
 */
class PWDEducationController
{
    private PWDEducation $educationModel;
    private PWDRecords $pwdModel;
    private ActivityLog $logModel;

    public function __construct(
        PWDEducation $educationModel,
        PWDRecords $pwdModel,
        ActivityLog $logModel
    ) {
        $this->educationModel = $educationModel;
        $this->pwdModel = $pwdModel;
        $this->logModel = $logModel;
    }

    /**
     * Get education records for a specific PWD
     */
    public function getByPWD($request, $response, $args)
    {
        $pwdId = (int)$args['pwd_id'];

        // Check if PWD exists
        $pwd = $this->pwdModel->getByPWDId($pwdId);
        if (!$pwd) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $educationRecords = $this->educationModel->getByPWDId($pwdId);

        $response->getBody()->write(json_encode([
            'pwd' => $pwd,
            'education_records' => $educationRecords
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get a specific education record
     */
    public function show($request, $response, $args)
    {
        $educationId = (int)$args['id'];
        $education = $this->educationModel->getById($educationId);

        if (!$education) {
            $response->getBody()->write(json_encode([
                'error' => 'Education record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($education));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Create a new education record
     */
    public function create($request, $response)
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        if (!isset($data['pwd_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD ID is required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // Check if PWD exists
        $pwd = $this->pwdModel->getByPWDId($data['pwd_id']);
        if (!$pwd) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $educationId = $this->educationModel->create($data);

        if (!$educationId) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to create education record: ' . $this->educationModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Added education record for PWD #{$data['pwd_id']}");

        $education = $this->educationModel->getById($educationId);

        $response->getBody()->write(json_encode([
            'message' => 'Education record created successfully',
            'education' => $education
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Update an existing education record
     */
    public function update($request, $response, $args)
    {
        $educationId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Check if education record exists
        $existingEducation = $this->educationModel->getById($educationId);
        if (!$existingEducation) {
            $response->getBody()->write(json_encode([
                'error' => 'Education record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // If PWD ID is being changed, check if the new PWD exists
        if (isset($data['pwd_id']) && $data['pwd_id'] != $existingEducation['pwd_id']) {
            $pwd = $this->pwdModel->getByPWDId($data['pwd_id']);
            if (!$pwd) {
                $response->getBody()->write(json_encode([
                    'error' => 'PWD record not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
        }

        $success = $this->educationModel->update($educationId, $data);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to update education record: ' . $this->educationModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Updated education record #{$educationId}");

        $education = $this->educationModel->getById($educationId);

        $response->getBody()->write(json_encode([
            'message' => 'Education record updated successfully',
            'education' => $education
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Delete an education record
     */
    public function delete($request, $response, $args)
    {
        $educationId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        // Check if education record exists
        $existingEducation = $this->educationModel->getById($educationId);
        if (!$existingEducation) {
            $response->getBody()->write(json_encode([
                'error' => 'Education record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $success = $this->educationModel->delete($educationId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to delete education record: ' . $this->educationModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Deleted education record #{$educationId}");

        $response->getBody()->write(json_encode([
            'message' => 'Education record deleted successfully'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get education statistics
     */
    public function getStatistics($request, $response)
    {
        $queryParams = $request->getQueryParams();
        $communityId = isset($queryParams['community_id']) ? (int)$queryParams['community_id'] : null;

        if ($communityId) {
            $stats = $this->educationModel->getStatsByCommunity($communityId);
        } else {
            $stats = $this->educationModel->getStatsOverall();
        }

        $response->getBody()->write(json_encode($stats));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
