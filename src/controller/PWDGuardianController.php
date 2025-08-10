<?php

declare(strict_types=1);

require_once MODEL . 'PWDGuardian.php';
require_once MODEL . 'PWDRecords.php';
require_once MODEL . 'ActivityLog.php';

/**
 * PWDGuardianController
 * 
 * Handles operations related to PWD guardian records
 */
class PWDGuardianController
{
    protected PWDGuardian $guardianModel;
    protected PWDRecords $pwdModel;
    protected ActivityLog $logModel;

    public function __construct()
    {
        $this->guardianModel = new PWDGuardian();
        $this->pwdModel = new PWDRecords();
        $this->logModel = new ActivityLog();
    }

    /**
     * Get guardian records for a specific PWD
     */
    public function getByPWD(int $pwdId): string
    {
        // Check if PWD exists
        $pwd = $this->pwdModel->getByPWDId($pwdId);
        if (!$pwd) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with ID: {$pwdId}"
            ], JSON_PRETTY_PRINT);
        }

        $guardians = $this->guardianModel->getByPWDId($pwdId);

        return json_encode([
            'status' => 'success',
            'pwd' => $pwd,
            'guardians' => $guardians,
            'message' => null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get a specific guardian record
     */
    public function show(int $guardianId): string
    {
        $guardian = $this->guardianModel->getById($guardianId);

        if (!$guardian) {
            return json_encode([
                'status' => 'error',
                'message' => "Guardian record not found with ID: {$guardianId}"
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'guardian' => $guardian,
            'message' => null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new guardian record
     */
    public function create(array $data, int $userId = null): string
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

        $guardianId = $this->guardianModel->create($data);

        if (!$guardianId) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create guardian record: ' . $this->guardianModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Added guardian record for PWD #{$data['pwd_id']}");
        }

        $guardian = $this->guardianModel->getById($guardianId);

        return json_encode([
            'status' => 'success',
            'guardian' => $guardian,
            'message' => 'Guardian record created successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing guardian record
     */
    public function update(int $guardianId, array $data, int $userId = null): string
    {
        // Check if guardian record exists
        $existingGuardian = $this->guardianModel->getById($guardianId);
        if (!$existingGuardian) {
            return json_encode([
                'status' => 'error',
                'message' => "Guardian record not found with ID: {$guardianId}"
            ], JSON_PRETTY_PRINT);
        }

        // If PWD ID is being changed, check if the new PWD exists
        if (isset($data['pwd_id']) && $data['pwd_id'] != $existingGuardian['pwd_id']) {
            $pwd = $this->pwdModel->getByPWDId($data['pwd_id']);
            if (!$pwd) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'PWD record not found'
                ], JSON_PRETTY_PRINT);
            }
        }

        $success = $this->guardianModel->update($guardianId, $data);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update guardian record: ' . $this->guardianModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Updated guardian record #{$guardianId}");
        }

        $guardian = $this->guardianModel->getById($guardianId);

        return json_encode([
            'status' => 'success',
            'guardian' => $guardian,
            'message' => 'Guardian record updated successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a guardian record
     */
    public function delete(int $guardianId, int $userId = null): string
    {
        // Check if guardian record exists
        $existingGuardian = $this->guardianModel->getById($guardianId);
        if (!$existingGuardian) {
            return json_encode([
                'status' => 'error',
                'message' => "Guardian record not found with ID: {$guardianId}"
            ], JSON_PRETTY_PRINT);
        }

        $success = $this->guardianModel->delete($guardianId);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete guardian record: ' . $this->guardianModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Deleted guardian record #{$guardianId}");
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Guardian record deleted successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get guardian relationship statistics
     */
    public function getStatistics(?int $communityId = null): string
    {
        if ($communityId) {
            $stats = $this->guardianModel->getStatsByCommunity($communityId);
        } else {
            $stats = $this->guardianModel->getStatsOverall();
        }

        return json_encode([
            'status' => !empty($stats) ? 'success' : 'error',
            'stats' => $stats,
            'message' => empty($stats) ? "No statistics found" : null
        ], JSON_PRETTY_PRINT);
    }
}
