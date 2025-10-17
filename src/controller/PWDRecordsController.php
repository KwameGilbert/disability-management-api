<?php

declare(strict_types=1);

require_once MODEL . 'PWDRecords.php';
require_once MODEL . 'ActivityLogs.php';

/**
 * PwdRecordsController
 *
 * Handles PWD records CRUD operations and related processes
 */
class PwdRecordsController
{
    /**
     * Get demographics summary report (age group, gender, disability type)
     * Endpoint: /v1/pwd-records/demographics
     * @return string JSON response with demographics summary
     */
    public function getDemographicsSummaryReport(): string
    {
        // Age Group Analysis
        $ageGroups = [
            ['label' => '0-17', 'min' => 0, 'max' => 17],
            ['label' => '18-35', 'min' => 18, 'max' => 35],
            ['label' => '36-59', 'min' => 36, 'max' => 59],
            ['label' => '60+', 'min' => 60, 'max' => 200],
        ];
        $ageGroupResults = [];
        $db = $this->pwdModel->db;
        foreach ($ageGroups as $group) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM pwd_records WHERE age >= :min AND age <= :max");
            $stmt->bindValue(':min', $group['min'], PDO::PARAM_INT);
            $stmt->bindValue(':max', $group['max'], PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            $ageGroupResults[] = [
                'age_group' => $group['label'],
                'count' => (int)$count
            ];
        }

        // Gender Analysis
        $stmt = $db->prepare("SELECT g.gender_name as gender, COUNT(*) as count FROM pwd_records p LEFT JOIN genders g ON p.gender_id = g.gender_id GROUP BY g.gender_name");
        $stmt->execute();
        $genderResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($genderResults as &$row) {
            $row['count'] = (int)$row['count'];
        }

        // Disability Type Analysis
        $stmt = $db->prepare("SELECT dt.type_name as disability_type, COUNT(*) as count FROM pwd_records p LEFT JOIN disability_types dt ON p.disability_type_id = dt.type_id GROUP BY dt.type_name");
        $stmt->execute();
        $disabilityTypeResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($disabilityTypeResults as &$row) {
            $row['count'] = (int)$row['count'];
        }

        return json_encode([
            'status' => 'success',
            'data' => [
                'age_groups' => $ageGroupResults,
                'genders' => $genderResults,
                'disability_types' => $disabilityTypeResults
            ],
            'message' => 'Demographics summary report generated successfully'
        ], JSON_PRETTY_PRINT);
    }
    
    protected PwdRecords $pwdModel;
    protected ActivityLogs $logModel;


    public function __construct()

    {

        $this->pwdModel = new PwdRecords();

        $this->logModel = new ActivityLogs();

    }


    /**
     * List all PWD records with pagination and optional filtering
     * 
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param array $filters Optional filters to apply
     * @return string JSON response
     */
    public function listPwdRecords(int $page = 1, int $perPage = 20, array $filters = []): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;

        $records = $this->pwdModel->getAll($perPage, $offset, $filters);
        $totalRecords = $this->pwdModel->getCount($filters);
        $totalPages = ceil($totalRecords / $perPage);

        return json_encode([
            'status' => 'success',
            'data' => $records,
            'pagination' => [
                'total_records' => $totalRecords,
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ],
            'filters' => $filters,
            'message' => empty($records) ? 'No PWD records found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get total count of PWD records
     * 
     * @param array $filters Optional array of filter conditions
     * @return int Count of records
     */
    public function getNumberOfPWDs(array $filters = []): string
    {
        $count = $this->pwdModel->getCount($filters);

        // Get current quarter and year
        $currentQuarter = 'Q' . ceil(date('n') / 3);
        $currentYear = (int) date('Y');

        // Get quarterly additions
        $quarterlyAdditions = $this->pwdModel->getCurrentQuarterAdditions($currentQuarter, $currentYear);

        // Get total assessed beneficiaries
        $assessedBeneficiaries = $this->pwdModel->getTotalAssessedBeneficiaries();

        return json_encode([
            'status' => 'success',
            'total_pwd' => $count,
            'current_quarter_additions' => $quarterlyAdditions,
            'total_assessed_beneficiaries' => $assessedBeneficiaries,
            'current_period' => [
                'quarter' => $currentQuarter,
                'year' => $currentYear
            ],
            'message' => 'PWD statistics'
        ], JSON_PRETTY_PRINT);
    }


    /**
     * Get PWD record by ID
     * 
     * @param int $id PWD record ID to retrieve
     * @return string JSON response
     */
    public function getPwdRecordById(int $id): string
    {
        $record = $this->pwdModel->getById($id);

        if (!$record) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'data' => $record,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new PWD record
     * 
     * @param array $data PWD record data
     * @param int $userId ID of user creating the record
     * @return string JSON response
     */
    public function createPwdRecord(array $data): string
    {
        // // Validate required fields
        // $requiredFields = ['quarter', 'year', 'gender_id', 'full_name', 'disability_category_id', 'disability_type_id', 'community_id'];
        // $missingFields = [];
        // foreach ($requiredFields as $field) {
        //     if (empty($data[$field])) {
        //         $missingFields[] = $field;
        //     }
        // }
        // if (!empty($missingFields)) {
        //     return json_encode([
        //         'status' => 'error',
        //         'message' => 'Missing required fields: ' . implode(', ', $missingFields),
        //     ], JSON_PRETTY_PRINT);
        // }

        // Handle file uploads
        $uploadDir = __DIR__ . '/../../public/uploads/pwd/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle profile_image (single file)
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['profile_image']['tmp_name'];
            $fileName = uniqid('profile_') . '_' . basename($_FILES['profile_image']['name']);
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($fileTmp, $destPath)) {
                $data['profile_image'] = 'uploads/pwd/' . $fileName;
            }
        }

        // Handle supporting_documents (multiple files)
        if (isset($_FILES['supporting_documents'])) {
            $docs = [];
            $files = $_FILES['supporting_documents'];
            $count = is_array($files['name']) ? count($files['name']) : 0;
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileTmp = $files['tmp_name'][$i];
                    $fileName = uniqid('doc_') . '_' . basename($files['name'][$i]);
                    $destPath = $uploadDir . $fileName;
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        $docs[] = 'uploads/pwd/' . $fileName;
                    }
                }
            }
            if (!empty($docs)) {
                $data['supporting_documents'] = $docs;
            }
        }

        // Validate quarter format
        if (!in_array($data['quarter'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid quarter format. Must be Q1, Q2, Q3, or Q4',
            ], JSON_PRETTY_PRINT);
        }

        // Validate year format
        $currentYear = (int) date('Y');
        if (!is_numeric($data['year']) || (int)$data['year'] < 2000 || (int)$data['year'] > $currentYear + 1) {
            return json_encode([
                'status' => 'error',
                'message' => "Invalid year. Must be between 2000 and {$currentYear} + 1",
            ], JSON_PRETTY_PRINT);
        }

        // Validate foreign key relationships
        $validationErrors = $this->pwdModel->validateForeignKeys($data);
        if (!empty($validationErrors)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Validation errors: ' . implode(', ', $validationErrors),
            ], JSON_PRETTY_PRINT);
        }

        // Create the PWD record
        $pwdId = $this->pwdModel->create($data);
        if ($pwdId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create PWD record: ' . $this->pwdModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        $userId = (int) $data['user_id'];
        // Log the activity
        $this->logModel->logActivity($userId, "Created new PWD record with ID {$pwdId} for {$data['full_name']}");

        $record = $this->pwdModel->getById((int) $pwdId);

        return json_encode([
            'status' => 'success',
            'message' => 'PWD record created successfully',
            'data' => $record,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing PWD record
     * 
     * @param int $id PWD record ID to update
     * @param array $data PWD record data
     * @param int $userId ID of user updating the record
     * @return string JSON response
     */
    public function updatePwdRecord(int $id, array $data, int $userId): string
    {
        // Check if record exists
        $existing = $this->pwdModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Handle file uploads
        $uploadDir = __DIR__ . '/../../public/uploads/pwd/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle profile_image (single file)
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['profile_image']['tmp_name'];
            $fileName = uniqid('profile_') . '_' . basename($_FILES['profile_image']['name']);
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($fileTmp, $destPath)) {
                $data['profile_image'] = 'uploads/pwd/' . $fileName;
            }
        }

        // Handle supporting_documents (multiple files)
        if (isset($_FILES['supporting_documents'])) {
            $docs = [];
            $files = $_FILES['supporting_documents'];
            $count = is_array($files['name']) ? count($files['name']) : 0;
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileTmp = $files['tmp_name'][$i];
                    $fileName = uniqid('doc_') . '_' . basename($files['name'][$i]);
                    $destPath = $uploadDir . $fileName;
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        $docs[] = 'uploads/pwd/' . $fileName;
                    }
                }
            }
            if (!empty($docs)) {
                $data['supporting_documents'] = $docs;
            }
        }

        // Validate quarter format if provided
        if (isset($data['quarter']) && !in_array($data['quarter'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid quarter format. Must be Q1, Q2, Q3, or Q4',
            ], JSON_PRETTY_PRINT);
        }

        // Validate year format if provided
        if (isset($data['year'])) {
            $currentYear = (int) date('Y');
            if (!is_numeric($data['year']) || (int)$data['year'] < 2000 || (int)$data['year'] > $currentYear + 1) {
                return json_encode([
                    'status' => 'error',
                    'message' => "Invalid year. Must be between 2000 and {$currentYear} + 1",
                ], JSON_PRETTY_PRINT);
            }
        }

        // Validate foreign key relationships
        $validationErrors = $this->pwdModel->validateForeignKeys($data);
        if (!empty($validationErrors)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Validation errors: ' . implode(', ', $validationErrors),
            ], JSON_PRETTY_PRINT);
        }

        // Update the PWD record
        $updated = $this->pwdModel->update($id, $data);
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update PWD record: ' . $this->pwdModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        // Log the activity
        $this->logModel->logActivity($userId, "Updated PWD record with ID {$id} for {$existing['full_name']}");

        $record = $this->pwdModel->getById($id);

        return json_encode([
            'status' => 'success',
            'message' => 'PWD record updated successfully',
            'data' => $record,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a PWD record
     * 
     * @param int $id PWD record ID to delete
     * @param int $userId ID of user deleting the record
     * @return string JSON response
     */
    public function deletePwdRecord(int $id, int $userId): string
    {
        // Check if record exists
        $existing = $this->pwdModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Delete the PWD record
        $deleted = $this->pwdModel->delete($id);

        if (!$deleted) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete PWD record: ' . $this->pwdModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        // Log the activity
        $this->logModel->logActivity($userId, "Deleted PWD record with ID {$id} for {$existing['full_name']}");

        return json_encode([
            'status' => 'success',
            'message' => 'PWD record deleted successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update PWD record status
     * 
     * @param int $id PWD record ID
     * @param string $status New status ('pending', 'approved', 'declined')
     * @param int $userId ID of user updating the status
     * @return string JSON response
     */
    public function updatePwdStatus(int $id, string $status, int $userId): string
    {
        // Check if record exists
        $existing = $this->pwdModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "PWD record not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Validate status
        if (!in_array($status, ['pending', 'approved', 'declined'])) {
            return json_encode([
                'status' => 'error',
                'message' => "Invalid status. Must be 'pending', 'approved', or 'declined'",
            ], JSON_PRETTY_PRINT);
        }

        // Update the status
        $updated = $this->pwdModel->updateStatus($id, $status);

        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update PWD status: ' . $this->pwdModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        // Log the activity
        $this->logModel->logActivity($userId, "Updated status of PWD record ID {$id} to '{$status}' for {$existing['full_name']}");

        $record = $this->pwdModel->getById($id);

        return json_encode([
            'status' => 'success',
            'message' => "PWD record status updated to '{$status}'",
            'data' => $record,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get PWD records by quarter and year
     * 
     * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
     * @param int $year Year
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getRecordsByQuarterAndYear(string $quarter, int $year, int $page = 1, int $perPage = 20): string
    {
        // Validate quarter format
        if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid quarter format. Must be Q1, Q2, Q3, or Q4',
            ], JSON_PRETTY_PRINT);
        }

        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;

        $records = $this->pwdModel->getByQuarterAndYear($quarter, $year, $perPage, $offset);

        // Get quarterly statistics
        $stats = $this->pwdModel->getQuarterlyStatistics($quarter, $year);

        // Get current quarter additions
        $quarterlyAdditions = $this->pwdModel->getCurrentQuarterAdditions($quarter, $year);

        // Get total assessed beneficiaries
        $assessedBeneficiaries = $this->pwdModel->getTotalAssessedBeneficiaries();

        // Add these to stats
        $stats['current_quarter_additions'] = $quarterlyAdditions;
        $stats['total_assessed_beneficiaries'] = $assessedBeneficiaries;

        return json_encode([
            'status' => 'success',
            'data' => $records,
            'statistics' => $stats,
            'period' => [
                'quarter' => $quarter,
                'year' => $year
            ],
            'message' => empty($records) ? "No PWD records found for {$quarter} {$year}" : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get PWD records by disability category
     * 
     * @param int $categoryId Disability category ID
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getRecordsByCategory(int $categoryId, int $page = 1, int $perPage = 20): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;

        $records = $this->pwdModel->getByDisabilityCategory($categoryId, $perPage, $offset);

        $categoryName = '';
        if (!empty($records)) {
            $categoryName = $records[0]['disability_category'] ?? '';
        }

        return json_encode([
            'status' => 'success',
            'data' => $records,
            'category' => [
                'id' => $categoryId,
                'name' => $categoryName
            ],
            'message' => empty($records) ? "No PWD records found for category ID {$categoryId}" : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get PWD records by community
     * 
     * @param int $communityId Community ID
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getRecordsByCommunity(int $communityId, int $page = 1, int $perPage = 20): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;

        $records = $this->pwdModel->getByCommunity($communityId, $perPage, $offset);

        $communityName = '';
        if (!empty($records)) {
            $communityName = $records[0]['community_name'] ?? '';
        }

        return json_encode([
            'status' => 'success',
            'data' => $records,
            'community' => [
                'id' => $communityId,
                'name' => $communityName
            ],
            'message' => empty($records) ? "No PWD records found for community ID {$communityId}" : null,
        ], JSON_PRETTY_PRINT);
    }
}
