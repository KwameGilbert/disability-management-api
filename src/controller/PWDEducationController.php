<?php

declare(strict_types=1);

require_once MODEL . 'PWDEducation.php';
require_once MODEL . 'PWDRecords.php';
require_once MODEL . 'ActivityLog.php';

/**
 * PWDEducationController
 * 
 * Handles operations related to PWD education records
 */
class PWDEducationController
{
    protected PWDEducation $educationModel;
    protected PWDRecords $pwdModel;
    protected ActivityLog $logModel;

    public function __construct()
    {
        $this->educationModel = new PWDEducation();
        $this->pwdModel = new PWDRecords();
        $this->logModel = new ActivityLog();
    }

    /**
     * Get education records for a specific PWD
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

        $educationRecords = $this->educationModel->getByPWDId($pwdId);

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => [
                'pwd' => $pwd,
                'education_records' => $educationRecords
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get a specific education record
     */
    public function show(int $educationId): string
    {
        $education = $this->educationModel->getById($educationId);

        if (!$education) {
            return json_encode([
                'status' => 'error',
                'message' => 'Education record not found'
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => $education
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new education record
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

        $educationId = $this->educationModel->create($data);

        if (!$educationId) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create education record: ' . $this->educationModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Added education record for PWD #{$data['pwd_id']}");
        }

        $education = $this->educationModel->getById($educationId);

        return json_encode([
            'status' => 'success',
            'message' => 'Education record created successfully',
            'data' => $education
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing education record
     */
    public function update(int $educationId, array $data, ?int $userId = null): string
    {
        // Check if education record exists
        $existingEducation = $this->educationModel->getById($educationId);
        if (!$existingEducation) {
            return json_encode([
                'status' => 'error',
                'message' => 'Education record not found'
            ], JSON_PRETTY_PRINT);
        }

        // If PWD ID is being changed, check if the new PWD exists
        if (isset($data['pwd_id']) && $data['pwd_id'] != $existingEducation['pwd_id']) {
            $pwd = $this->pwdModel->getByPWDId($data['pwd_id']);
            if (!$pwd) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'PWD record not found'
                ], JSON_PRETTY_PRINT);
            }
        }

        $success = $this->educationModel->update($educationId, $data);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update education record: ' . $this->educationModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Updated education record #{$educationId}");
        }

        $education = $this->educationModel->getById($educationId);

        return json_encode([
            'status' => 'success',
            'message' => 'Education record updated successfully',
            'data' => $education
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete an education record
     */
    public function delete(int $educationId, ?int $userId = null): string
    {
        // Check if education record exists
        $existingEducation = $this->educationModel->getById($educationId);
        if (!$existingEducation) {
            return json_encode([
                'status' => 'error',
                'message' => 'Education record not found'
            ], JSON_PRETTY_PRINT);
        }

        $success = $this->educationModel->delete($educationId);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete education record: ' . $this->educationModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Deleted education record #{$educationId}");
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Education record deleted successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get education statistics
     */
    public function getStatistics(?int $communityId = null): string
    {
        if ($communityId) {
            $stats = $this->educationModel->getStatsByCommunity($communityId);
        } else {
            $stats = $this->educationModel->getStatsOverall();
        }

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => $stats
        ], JSON_PRETTY_PRINT);
    }
}
