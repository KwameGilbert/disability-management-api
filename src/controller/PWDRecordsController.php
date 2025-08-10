<?php

declare(strict_types=1);

require_once MODEL . 'PWDRecords.php';
require_once MODEL . 'PWDGuardian.php';
require_once MODEL . 'PWDEducation.php';
require_once MODEL . 'PWDSupportNeeds.php';
require_once MODEL . 'SupportingDocument.php';
require_once MODEL . 'ActivityLog.php';
require_once CONFIG . 'Database.php';

/**
 * PWDRecordsController
 * 
 * Handles endpoints for managing PWD records, including support for combined data submission
 * (PWD records with guardians, education, support needs, and supporting documents)
 */
class PWDRecordsController
{
    protected PWDRecords $pwdModel;
    protected PWDGuardian $guardianModel;
    protected PWDEducation $educationModel;
    protected PWDSupportNeeds $supportNeedsModel;
    protected SupportingDocument $documentModel;
    protected ActivityLog $logModel;
    protected PDO $db;

    public function __construct()
    {
        $this->pwdModel = new PWDRecords();
        $this->guardianModel = new PWDGuardian();
        $this->educationModel = new PWDEducation();
        $this->supportNeedsModel = new PWDSupportNeeds();
        $this->documentModel = new SupportingDocument();
        $this->logModel = new ActivityLog();

        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all PWD records (with pagination)
     */
    public function index(?int $limit = null, ?int $offset = null): string
    {
        $pwds = $this->pwdModel->getAll($limit, $offset);
        $totalCount = $this->pwdModel->getCount();

        $result = [
            'status' => !empty($pwds) ? 'success' : 'error',
            'pwds' => $pwds,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'message' => empty($pwds) ? 'No PWD records found' : null
        ];

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Get PWD records by status
     */
    public function getByStatus(string $status): string
    {
        $pwds = $this->pwdModel->getByStatus($status);

        return json_encode([
            'status' => !empty($pwds) ? 'success' : 'error',
            'pwds' => $pwds,
            'message' => empty($pwds) ? "No PWD records found with status: {$status}" : null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get PWD records by community
     */
    public function getByCommunity(int $communityId): string
    {
        $pwds = $this->pwdModel->getByCommunityId($communityId);

        return json_encode([
            'status' => !empty($pwds) ? 'success' : 'error',
            'pwds' => $pwds,
            'message' => empty($pwds) ? "No PWD records found for community ID: {$communityId}" : null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Search PWD records
     */
    public function search(string $term): string
    {
        $pwds = $this->pwdModel->search($term);

        return json_encode([
            'status' => !empty($pwds) ? 'success' : 'error',
            'pwds' => $pwds,
            'message' => empty($pwds) ? "No PWD records found matching: {$term}" : null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get PWD record by ID with related data
     */
    public function show(int $pwdId): string
    {
        $pwd = $this->pwdModel->getByPWDId($pwdId);

        if (!$pwd) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with ID: {$pwdId}"
            ], JSON_PRETTY_PRINT);
        }

        // Get related data
        $pwd['guardians'] = $this->guardianModel->getByPWDId($pwdId);
        $pwd['education'] = $this->educationModel->getByPWDId($pwdId);
        $pwd['support_needs'] = $this->supportNeedsModel->getByPWDId($pwdId);
        $pwd['documents'] = $this->documentModel->getByRelatedEntity('pwd', $pwdId);

        return json_encode([
            'status' => 'success',
            'pwd' => $pwd,
            'message' => null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new PWD record with related data
     */
    public function create(array $data, int $userId = null): string
    {
        // Start a transaction
        $this->db->beginTransaction();

        try {
            // 1. Create the PWD record
            $pwdId = $this->pwdModel->create($data);

            if (!$pwdId) {
                throw new Exception('Failed to create PWD record: ' . $this->pwdModel->getLastError());
            }

            // 2. Add guardians if provided
            if (isset($data['guardians']) && is_array($data['guardians']) && !empty($data['guardians'])) {
                foreach ($data['guardians'] as $guardian) {
                    $guardian['pwd_id'] = $pwdId;
                    $guardianId = $this->guardianModel->create($guardian);

                    if (!$guardianId) {
                        throw new Exception('Failed to create guardian: ' . $this->guardianModel->getLastError());
                    }
                }
            }

            // 3. Add education records if provided
            if (isset($data['education']) && is_array($data['education']) && !empty($data['education'])) {
                foreach ($data['education'] as $education) {
                    $education['pwd_id'] = $pwdId;
                    $educationId = $this->educationModel->create($education);

                    if (!$educationId) {
                        throw new Exception('Failed to create education record: ' . $this->educationModel->getLastError());
                    }
                }
            }

            // 4. Add support needs if provided
            if (isset($data['support_needs']) && is_array($data['support_needs']) && !empty($data['support_needs'])) {
                foreach ($data['support_needs'] as $need) {
                    $need['pwd_id'] = $pwdId;
                    $needId = $this->supportNeedsModel->create($need);

                    if (!$needId) {
                        throw new Exception('Failed to create support need: ' . $this->supportNeedsModel->getLastError());
                    }
                }
            }

            // Log the activity if user ID provided
            if ($userId) {
                $this->logModel->log($userId, "Created PWD record for {$data['full_name']} with ID {$pwdId}");
            }

            // Commit the transaction
            $this->db->commit();

            $pwd = $this->pwdModel->getByPWDId($pwdId);
            $pwd['guardians'] = $this->guardianModel->getByPWDId($pwdId);
            $pwd['education'] = $this->educationModel->getByPWDId($pwdId);
            $pwd['support_needs'] = $this->supportNeedsModel->getByPWDId($pwdId);

            return json_encode([
                'status' => 'success',
                'pwd' => $pwd,
                'message' => 'PWD record created successfully'
            ], JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            // Roll back the transaction on error
            $this->db->rollBack();

            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Update a PWD record with related data
     */
    public function update(int $pwdId, array $data, int $userId = null): string
    {
        // Check if PWD record exists
        $existingPWD = $this->pwdModel->getByPWDId($pwdId);
        if (!$existingPWD) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with ID: {$pwdId}"
            ], JSON_PRETTY_PRINT);
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
            if (isset($data['guardians']) && is_array($data['guardians']) && !empty($data['guardians'])) {
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
            if (isset($data['education']) && is_array($data['education']) && !empty($data['education'])) {
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
            if (isset($data['support_needs']) && is_array($data['support_needs']) && !empty($data['support_needs'])) {
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

            // Log the activity if user ID provided
            if ($userId) {
                $this->logModel->log($userId, "Updated PWD record for {$existingPWD['full_name']} with ID {$pwdId}");
            }

            // Commit the transaction
            $this->db->commit();

            $pwd = $this->pwdModel->getByPWDId($pwdId);
            $pwd['guardians'] = $this->guardianModel->getByPWDId($pwdId);
            $pwd['education'] = $this->educationModel->getByPWDId($pwdId);
            $pwd['support_needs'] = $this->supportNeedsModel->getByPWDId($pwdId);

            return json_encode([
                'status' => 'success',
                'pwd' => $pwd,
                'message' => 'PWD record updated successfully'
            ], JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            // Roll back the transaction on error
            $this->db->rollBack();

            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Update PWD status
     */
    public function updateStatus(int $pwdId, string $status, int $userId = null): string
    {
        if (empty($status)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Status field is required'
            ], JSON_PRETTY_PRINT);
        }

        $existingPWD = $this->pwdModel->getByPWDId($pwdId);
        if (!$existingPWD) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with ID: {$pwdId}"
            ], JSON_PRETTY_PRINT);
        }

        $success = $this->pwdModel->updateStatus($pwdId, $status);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $this->pwdModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        // Log the activity if user ID provided
        if ($userId) {
            $this->logModel->log($userId, "Updated PWD status for {$existingPWD['full_name']} to {$status}");
        }

        $pwd = $this->pwdModel->getByPWDId($pwdId);

        return json_encode([
            'status' => 'success',
            'pwd' => $pwd,
            'message' => 'PWD status updated successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a PWD record and all related data
     */
    public function delete(int $pwdId, int $userId = null): string
    {
        $existingPWD = $this->pwdModel->getByPWDId($pwdId);
        if (!$existingPWD) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with ID: {$pwdId}"
            ], JSON_PRETTY_PRINT);
        }

        // Delete PWD record (this will cascade delete related records)
        $success = $this->pwdModel->delete($pwdId);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete PWD record: ' . $this->pwdModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        // Log the activity if user ID provided
        if ($userId) {
            $this->logModel->log($userId, "Deleted PWD record for {$existingPWD['full_name']} with ID {$pwdId}");
        }

        return json_encode([
            'status' => 'success',
            'message' => 'PWD record deleted successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get statistics by quarter and year
     */
    public function getStatistics(string $quarter, int $year): string
    {
        if (!$quarter || !$year) {
            return json_encode([
                'status' => 'error',
                'message' => 'Quarter and year parameters are required'
            ], JSON_PRETTY_PRINT);
        }

        $stats = $this->pwdModel->getStatsByQuarterYear($quarter, $year);

        return json_encode([
            'status' => !empty($stats) ? 'success' : 'error',
            'stats' => $stats,
            'message' => empty($stats) ? "No statistics found for {$quarter} {$year}" : null
        ], JSON_PRETTY_PRINT);
    }
}
