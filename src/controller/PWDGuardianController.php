<?php

declare(strict_types=1);

/**
 * PWDGuardianController
 * 
 * Handles operations related to PWD guardian records
 */
class PWDGuardianController
{
    private PWDGuardian $guardianModel;
    private PWDRecords $pwdModel;
    private ActivityLog $logModel;

    public function __construct(
        PWDGuardian $guardianModel,
        PWDRecords $pwdModel,
        ActivityLog $logModel
    ) {
        $this->guardianModel = $guardianModel;
        $this->pwdModel = $pwdModel;
        $this->logModel = $logModel;
    }

    /**
     * Get guardian records for a specific PWD
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

        $guardians = $this->guardianModel->getByPWDId($pwdId);

        $response->getBody()->write(json_encode([
            'pwd' => $pwd,
            'guardians' => $guardians
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get a specific guardian record
     */
    public function show($request, $response, $args)
    {
        $guardianId = (int)$args['id'];
        $guardian = $this->guardianModel->getById($guardianId);

        if (!$guardian) {
            $response->getBody()->write(json_encode([
                'error' => 'Guardian record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($guardian));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Create a new guardian record
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

        $guardianId = $this->guardianModel->create($data);

        if (!$guardianId) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to create guardian record: ' . $this->guardianModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Added guardian record for PWD #{$data['pwd_id']}");

        $guardian = $this->guardianModel->getById($guardianId);

        $response->getBody()->write(json_encode([
            'message' => 'Guardian record created successfully',
            'guardian' => $guardian
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Update an existing guardian record
     */
    public function update($request, $response, $args)
    {
        $guardianId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Check if guardian record exists
        $existingGuardian = $this->guardianModel->getById($guardianId);
        if (!$existingGuardian) {
            $response->getBody()->write(json_encode([
                'error' => 'Guardian record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // If PWD ID is being changed, check if the new PWD exists
        if (isset($data['pwd_id']) && $data['pwd_id'] != $existingGuardian['pwd_id']) {
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

        $success = $this->guardianModel->update($guardianId, $data);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to update guardian record: ' . $this->guardianModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Updated guardian record #{$guardianId}");

        $guardian = $this->guardianModel->getById($guardianId);

        $response->getBody()->write(json_encode([
            'message' => 'Guardian record updated successfully',
            'guardian' => $guardian
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Delete a guardian record
     */
    public function delete($request, $response, $args)
    {
        $guardianId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        // Check if guardian record exists
        $existingGuardian = $this->guardianModel->getById($guardianId);
        if (!$existingGuardian) {
            $response->getBody()->write(json_encode([
                'error' => 'Guardian record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $success = $this->guardianModel->delete($guardianId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to delete guardian record: ' . $this->guardianModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Deleted guardian record #{$guardianId}");

        $response->getBody()->write(json_encode([
            'message' => 'Guardian record deleted successfully'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get guardian relationship statistics
     */
    public function getStatistics($request, $response)
    {
        $queryParams = $request->getQueryParams();
        $communityId = isset($queryParams['community_id']) ? (int)$queryParams['community_id'] : null;

        if ($communityId) {
            $stats = $this->guardianModel->getStatsByCommunity($communityId);
        } else {
            $stats = $this->guardianModel->getStatsOverall();
        }

        $response->getBody()->write(json_encode($stats));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
