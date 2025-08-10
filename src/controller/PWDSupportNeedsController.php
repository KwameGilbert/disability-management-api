<?php

declare(strict_types=1);

require_once MODEL . 'PWDSupportNeeds.php';
require_once MODEL . 'PWDRecords.php';
require_once MODEL . 'ActivityLog.php';

/**
 * PWDSupportNeedsController
 * 
 * Handles operations related to PWD support needs
 */
class PWDSupportNeedsController
{
    protected PWDSupportNeeds $supportNeedsModel;
    protected PWDRecords $pwdModel;
    protected ActivityLog $logModel;

    public function __construct()
    {
        $this->supportNeedsModel = new PWDSupportNeeds();
        $this->pwdModel = new PWDRecords();
        $this->logModel = new ActivityLog();
    }

    /**
     * Get support needs for a specific PWD
     */
    public function getByPWD(int $pwdId): string
    {
        // Check if PWD exists
        $pwd = $this->pwdModel->getByPWDId($pwdId);
        if (!$pwd) {
            return json_encode([
                'status' => 'error',
                'message' => 'PWD record not found'
            ], JSON_PRETTY_PRINT);
        }

        $supportNeeds = $this->supportNeedsModel->getByPWDId($pwdId);

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => [
                'pwd' => $pwd,
                'support_needs' => $supportNeeds
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get a specific support need
     */
    public function show(int $needId): string
    {
        $need = $this->supportNeedsModel->getById($needId);

        if (!$need) {
            return json_encode([
                'status' => 'error',
                'message' => 'Support need not found'
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => $need
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new support need
     */
    public function create(array $data, ?int $userId = null): string
    {
        if (!isset($data['pwd_id'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'PWD ID is required'
            ], JSON_PRETTY_PRINT);
        }

        // Check if PWD exists
        $pwd = $this->pwdModel->getByPWDId($data['pwd_id']);
        if (!$pwd) {
            return json_encode([
                'status' => 'error',
                'message' => 'PWD record not found'
            ], JSON_PRETTY_PRINT);
        }

        $needId = $this->supportNeedsModel->create($data);

        if (!$needId) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create support need: ' . $this->supportNeedsModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Added support need for PWD #{$data['pwd_id']}");
        }

        $need = $this->supportNeedsModel->getById($needId);

        return json_encode([
            'status' => 'success',
            'message' => 'Support need created successfully',
            'data' => $need
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing support need
     */
    public function update(int $needId, array $data, ?int $userId = null): string
    {
        // Check if support need exists
        $existingNeed = $this->supportNeedsModel->getById($needId);
        if (!$existingNeed) {
            return json_encode([
                'status' => 'error',
                'message' => 'Support need not found'
            ], JSON_PRETTY_PRINT);
        }

        // If PWD ID is being changed, check if the new PWD exists
        if (isset($data['pwd_id']) && $data['pwd_id'] != $existingNeed['pwd_id']) {
            $pwd = $this->pwdModel->getByPWDId($data['pwd_id']);
            if (!$pwd) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'PWD record not found'
                ], JSON_PRETTY_PRINT);
            }
        }

        $success = $this->supportNeedsModel->update($needId, $data);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update support need: ' . $this->supportNeedsModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Updated support need #{$needId}");
        }

        $need = $this->supportNeedsModel->getById($needId);

        return json_encode([
            'status' => 'success',
            'message' => 'Support need updated successfully',
            'data' => $need
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a support need
     */
    public function delete(int $needId, ?int $userId = null): string
    {
        // Check if support need exists
        $existingNeed = $this->supportNeedsModel->getById($needId);
        if (!$existingNeed) {
            return json_encode([
                'status' => 'error',
                'message' => 'Support need not found'
            ], JSON_PRETTY_PRINT);
        }

        $success = $this->supportNeedsModel->delete($needId);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete support need: ' . $this->supportNeedsModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Deleted support need #{$needId}");
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Support need deleted successfully'
        ], JSON_PRETTY_PRINT);
            ->withStatus(200);
    }

    /**
     * Get statistics on common support needs
     */
    // public function getStatistics($request, $response)
    // {
    //     $queryParams = $request->getQueryParams();
    //     $communityId = isset($queryParams['community_id']) ? (int)$queryParams['community_id'] : null;

    //     if ($communityId) {
    //         $stats = $this->supportNeedsModel->getStatsByCommunity($communityId);
    //     } else {
    //         $stats = $this->supportNeedsModel->getStatsOverall();
    //     }

    //     $response->getBody()->write(json_encode($stats));
    //     return $response
    //         ->withHeader('Content-Type', 'application/json')
    //         ->withStatus(200);
    // }
}
