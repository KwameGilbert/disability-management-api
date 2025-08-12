<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * PwdRecords Model
 * 
 * Handles database operations for pwd_records table
 * Table structure:
 * - pwd_records(pwd_id, user_id, quarter, year, gender_id, full_name, occupation, 
 *   contact, dob, age, disability_category_id, disability_type_id, gh_card_number, 
 *   nhis_number, community_id, guardian_name, guardian_occupation, guardian_phone, 
 *   guardian_relationship, education_level, school_name, assistance_type_needed_id, 
 *   support_needs, supporting_documents, status, profile_image, created_at)
 * 
 * Has foreign key relationships with:
 * - users(user_id)
 * - genders(gender_id)
 * - disability_categories(category_id)
 * - disability_types(type_id)
 * - communities(community_id)
 * - assistance_types(assistance_type_id)
 */
class PwdRecords
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'pwd_records';

    /** @var string */
    private string $lastError = '';

    public function __construct()
    {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (PDOException $e) {
            $this->lastError = "Database connection failed: " . $e->getMessage();
        }
    }

    /**
     * Get the last error message
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Execute a prepared statement with error handling
     */
    protected function executeQuery(\PDOStatement $statement, array $params = []): bool
    {
        try {
            return $statement->execute($params);
        } catch (PDOException $e) {
            $this->lastError = "Query execution failed: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get all PWD records with related data
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @param array $filters Optional array of filter conditions
     * @return array List of PWD records
     */
    public function getAll(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        try {
            $query = "SELECT 
                        p.*, 
                        u.username as registered_by,
                        g.gender_name,
                        c.community_name,
                        dc.category_name as disability_category,
                        dt.type_name as disability_type,
                        at.assistance_type_name
                      FROM {$this->tableName} p
                      LEFT JOIN users u ON p.user_id = u.user_id
                      LEFT JOIN genders g ON p.gender_id = g.gender_id
                      LEFT JOIN communities c ON p.community_id = c.community_id
                      LEFT JOIN disability_categories dc ON p.disability_category_id = dc.category_id
                      LEFT JOIN disability_types dt ON p.disability_type_id = dt.type_id
                      LEFT JOIN assistance_types at ON p.assistance_type_needed_id = at.assistance_type_id";
            
            $whereConditions = [];
            $params = [];
            
            // Apply filters if provided
            if (!empty($filters)) {
                foreach ($filters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'search') {
                            // Special case for search
                            $whereConditions[] = "(p.full_name LIKE :search OR p.gh_card_number LIKE :search OR p.nhis_number LIKE :search)";
                            $params['search'] = "%{$value}%";
                        } else {
                            $whereConditions[] = "p.{$column} = :{$column}";
                            $params[$column] = $value;
                        }
                    }
                }
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            // Bind limit and offset params
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Bind filter params if any
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process any JSON fields
            foreach ($records as &$record) {
                if (isset($record['supporting_documents']) && !empty($record['supporting_documents'])) {
                    $record['supporting_documents'] = json_decode($record['supporting_documents'], true);
                }
            }
            
            return $records;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching PWD records: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get total count of PWD records
     * 
     * @param array $filters Optional array of filter conditions
     * @return int Count of records
     */
    public function getCount(array $filters = []): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->tableName} p";
            
            $whereConditions = [];
            $params = [];
            
            // Apply filters if provided
            if (!empty($filters)) {
                foreach ($filters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'search') {
                            // Special case for search
                            $whereConditions[] = "(p.full_name LIKE :search OR p.gh_card_number LIKE :search OR p.nhis_number LIKE :search)";
                            $params['search'] = "%{$value}%";
                        } else {
                            $whereConditions[] = "p.{$column} = :{$column}";
                            $params[$column] = $value;
                        }
                    }
                }
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $stmt = $this->db->prepare($query);
            
            // Bind filter params if any
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing count query: " . implode(" ", $stmt->errorInfo());
                return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = "Error counting PWD records: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Get PWD record by ID with related data
     * 
     * @param int $pwdId PWD record ID to retrieve
     * @return array|null PWD record or null if not found
     */
    public function getById(int $pwdId): ?array
    {
        try {
            $query = "SELECT 
                        p.*, 
                        u.username as registered_by,
                        g.gender_name,
                        c.community_name,
                        dc.category_name as disability_category,
                        dt.type_name as disability_type,
                        at.assistance_type_name
                      FROM {$this->tableName} p
                      LEFT JOIN users u ON p.user_id = u.user_id
                      LEFT JOIN genders g ON p.gender_id = g.gender_id
                      LEFT JOIN communities c ON p.community_id = c.community_id
                      LEFT JOIN disability_categories dc ON p.disability_category_id = dc.category_id
                      LEFT JOIN disability_types dt ON p.disability_type_id = dt.type_id
                      LEFT JOIN assistance_types at ON p.assistance_type_needed_id = at.assistance_type_id
                      WHERE p.pwd_id = :pwd_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['pwd_id' => $pwdId])) {
                return null;
            }
            
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$record) {
                return null;
            }
            
            // Process JSON fields
            if (isset($record['supporting_documents']) && !empty($record['supporting_documents'])) {
                $record['supporting_documents'] = json_decode($record['supporting_documents'], true);
            }
            
            return $record;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching PWD record: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create a new PWD record
     * 
     * @param array $data PWD record data
     * @return int|false Inserted PWD ID or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            // Handle JSON fields
            if (isset($data['supporting_documents']) && is_array($data['supporting_documents'])) {
                $data['supporting_documents'] = json_encode($data['supporting_documents']);
            }
            
            // Calculate age from DOB if provided and age is not set
            if (!empty($data['dob']) && empty($data['age'])) {
                $dob = new \DateTime($data['dob']);
                $now = new \DateTime();
                $data['age'] = (int) $now->diff($dob)->y;
            }
            
            // Prepare query dynamically based on available fields
            $columns = [];
            $placeholders = [];
            $params = [];
            
            $allowedFields = [
                'user_id', 'quarter', 'year', 'gender_id', 'full_name', 'occupation',
                'contact', 'dob', 'age', 'disability_category_id', 'disability_type_id',
                'gh_card_number', 'nhis_number', 'community_id', 'guardian_name',
                'guardian_occupation', 'guardian_phone', 'guardian_relationship',
                'education_level', 'school_name', 'assistance_type_needed_id',
                'support_needs', 'supporting_documents', 'status', 'profile_image'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $columns[] = $field;
                    $placeholders[] = ":{$field}";
                    $params[$field] = $data[$field];
                }
            }
            
            $query = "INSERT INTO {$this->tableName} (" . implode(", ", $columns) . ") 
                      VALUES (" . implode(", ", $placeholders) . ")";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing insert: " . implode(" ", $stmt->errorInfo());
                return false;
            }
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error creating PWD record: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update an existing PWD record
     * 
     * @param int $pwdId PWD record ID to update
     * @param array $data PWD record data
     * @return bool True on success, false on failure
     */
    public function update(int $pwdId, array $data): bool
    {
        try {
            // Handle JSON fields
            if (isset($data['supporting_documents']) && is_array($data['supporting_documents'])) {
                $data['supporting_documents'] = json_encode($data['supporting_documents']);
            }
            
            // Calculate age from DOB if DOB is provided and age is not set
            if (!empty($data['dob']) && empty($data['age'])) {
                $dob = new \DateTime($data['dob']);
                $now = new \DateTime();
                $data['age'] = (int) $now->diff($dob)->y;
            }
            
            // Prepare query dynamically based on available fields
            $setStatements = [];
            $params = ['pwd_id' => $pwdId];
            
            $allowedFields = [
                'user_id', 'quarter', 'year', 'gender_id', 'full_name', 'occupation',
                'contact', 'dob', 'age', 'disability_category_id', 'disability_type_id',
                'gh_card_number', 'nhis_number', 'community_id', 'guardian_name',
                'guardian_occupation', 'guardian_phone', 'guardian_relationship',
                'education_level', 'school_name', 'assistance_type_needed_id',
                'support_needs', 'supporting_documents', 'status', 'profile_image'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $setStatements[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($setStatements)) {
                return true; // Nothing to update
            }
            
            $query = "UPDATE {$this->tableName} SET " . implode(", ", $setStatements) . " 
                      WHERE pwd_id = :pwd_id";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = "Error updating PWD record: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete a PWD record
     * 
     * @param int $pwdId PWD record ID to delete
     * @return bool True on success, false on failure
     */
    public function delete(int $pwdId): bool
    {
        try {
            // Check if there are any assistance requests for this PWD
            $checkQuery = "SELECT COUNT(*) as count FROM assistance_requests 
                          WHERE beneficiary_id = :pwd_id";
            
            $checkStmt = $this->db->prepare($checkQuery);
            
            if (!$this->executeQuery($checkStmt, ['pwd_id' => $pwdId])) {
                return false;
            }
            
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)($result['count'] ?? 0);
            
            if ($count > 0) {
                $this->lastError = "Cannot delete PWD record because it has {$count} assistance requests. Delete those first.";
                return false;
            }
            
            // If no assistance requests, delete the PWD record
            $query = "DELETE FROM {$this->tableName} WHERE pwd_id = :pwd_id";
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, ['pwd_id' => $pwdId]);
        } catch (PDOException $e) {
            $this->lastError = "Error deleting PWD record: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update status of a PWD record
     * 
     * @param int $pwdId PWD record ID
     * @param string $status New status ('pending', 'approved', 'declined')
     * @return bool True on success, false on failure
     */
    public function updateStatus(int $pwdId, string $status): bool
    {
        try {
            if (!in_array($status, ['pending', 'approved', 'declined'])) {
                $this->lastError = "Invalid status value: {$status}";
                return false;
            }
            
            $query = "UPDATE {$this->tableName} SET status = :status 
                      WHERE pwd_id = :pwd_id";
            
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, [
                'status' => $status,
                'pwd_id' => $pwdId
            ]);
        } catch (PDOException $e) {
            $this->lastError = "Error updating PWD status: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get PWD records by quarter and year
     * 
     * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
     * @param int $year Year
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array List of PWD records
     */
    public function getByQuarterAndYear(string $quarter, int $year, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT 
                        p.*, 
                        u.username as registered_by,
                        g.gender_name,
                        c.community_name,
                        dc.category_name as disability_category,
                        dt.type_name as disability_type,
                        at.assistance_type_name
                      FROM {$this->tableName} p
                      LEFT JOIN users u ON p.user_id = u.user_id
                      LEFT JOIN genders g ON p.gender_id = g.gender_id
                      LEFT JOIN communities c ON p.community_id = c.community_id
                      LEFT JOIN disability_categories dc ON p.disability_category_id = dc.category_id
                      LEFT JOIN disability_types dt ON p.disability_type_id = dt.type_id
                      LEFT JOIN assistance_types at ON p.assistance_type_needed_id = at.assistance_type_id
                      WHERE p.quarter = :quarter AND p.year = :year
                      ORDER BY p.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':quarter', $quarter);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process any JSON fields
            foreach ($records as &$record) {
                if (isset($record['supporting_documents']) && !empty($record['supporting_documents'])) {
                    $record['supporting_documents'] = json_decode($record['supporting_documents'], true);
                }
            }
            
            return $records;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching PWD records by quarter and year: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get PWD records by disability category
     * 
     * @param int $categoryId Disability category ID
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array List of PWD records
     */
    public function getByDisabilityCategory(int $categoryId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT 
                        p.*, 
                        u.username as registered_by,
                        g.gender_name,
                        c.community_name,
                        dc.category_name as disability_category,
                        dt.type_name as disability_type,
                        at.assistance_type_name
                      FROM {$this->tableName} p
                      LEFT JOIN users u ON p.user_id = u.user_id
                      LEFT JOIN genders g ON p.gender_id = g.gender_id
                      LEFT JOIN communities c ON p.community_id = c.community_id
                      LEFT JOIN disability_categories dc ON p.disability_category_id = dc.category_id
                      LEFT JOIN disability_types dt ON p.disability_type_id = dt.type_id
                      LEFT JOIN assistance_types at ON p.assistance_type_needed_id = at.assistance_type_id
                      WHERE p.disability_category_id = :category_id
                      ORDER BY p.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process any JSON fields
            foreach ($records as &$record) {
                if (isset($record['supporting_documents']) && !empty($record['supporting_documents'])) {
                    $record['supporting_documents'] = json_decode($record['supporting_documents'], true);
                }
            }
            
            return $records;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching PWD records by disability category: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get PWD records by community
     * 
     * @param int $communityId Community ID
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array List of PWD records
     */
    public function getByCommunity(int $communityId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT 
                        p.*, 
                        u.username as registered_by,
                        g.gender_name,
                        c.community_name,
                        dc.category_name as disability_category,
                        dt.type_name as disability_type,
                        at.assistance_type_name
                      FROM {$this->tableName} p
                      LEFT JOIN users u ON p.user_id = u.user_id
                      LEFT JOIN genders g ON p.gender_id = g.gender_id
                      LEFT JOIN communities c ON p.community_id = c.community_id
                      LEFT JOIN disability_categories dc ON p.disability_category_id = dc.category_id
                      LEFT JOIN disability_types dt ON p.disability_type_id = dt.type_id
                      LEFT JOIN assistance_types at ON p.assistance_type_needed_id = at.assistance_type_id
                      WHERE p.community_id = :community_id
                      ORDER BY p.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':community_id', $communityId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process any JSON fields
            foreach ($records as &$record) {
                if (isset($record['supporting_documents']) && !empty($record['supporting_documents'])) {
                    $record['supporting_documents'] = json_decode($record['supporting_documents'], true);
                }
            }
            
            return $records;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching PWD records by community: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get quarterly statistics
     * 
     * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
     * @param int $year Year
     * @return array Statistics
     */
    public function getQuarterlyStatistics(string $quarter, int $year): array
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_records,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                      FROM {$this->tableName}
                      WHERE quarter = :quarter AND year = :year";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':quarter', $quarter);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Add additional statistics
            $query = "SELECT 
                        COUNT(DISTINCT community_id) as communities,
                        COUNT(DISTINCT disability_category_id) as categories
                      FROM {$this->tableName}
                      WHERE quarter = :quarter AND year = :year";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':quarter', $quarter);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return $stats;
            }
            
            $additionalStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return array_merge($stats, $additionalStats);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching quarterly statistics: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Verify required foreign keys exist
     * 
     * @param array $data Data to validate
     * @return array Array of error messages, empty if all validations pass
     */
    public function validateForeignKeys(array $data): array
    {
        $errors = [];
        
        // Check user_id exists
        if (isset($data['user_id'])) {
            $query = "SELECT user_id FROM users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "User ID {$data['user_id']} does not exist";
            }
        }
        
        // Check gender_id exists
        if (isset($data['gender_id'])) {
            $query = "SELECT gender_id FROM genders WHERE gender_id = :gender_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':gender_id', $data['gender_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Gender ID {$data['gender_id']} does not exist";
            }
        }
        
        // Check disability_category_id exists
        if (isset($data['disability_category_id'])) {
            $query = "SELECT category_id FROM disability_categories WHERE category_id = :category_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':category_id', $data['disability_category_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Disability category ID {$data['disability_category_id']} does not exist";
            }
        }
        
        // Check disability_type_id exists
        if (isset($data['disability_type_id'])) {
            $query = "SELECT type_id FROM disability_types WHERE type_id = :type_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':type_id', $data['disability_type_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Disability type ID {$data['disability_type_id']} does not exist";
            }
            
            // Check that disability_type belongs to the specified category if both are provided
            if (isset($data['disability_category_id'])) {
                $query = "SELECT type_id FROM disability_types 
                          WHERE type_id = :type_id AND category_id = :category_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':type_id', $data['disability_type_id'], PDO::PARAM_INT);
                $stmt->bindParam(':category_id', $data['disability_category_id'], PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() === 0) {
                    $errors[] = "Disability type ID {$data['disability_type_id']} does not belong to category ID {$data['disability_category_id']}";
                }
            }
        }
        
        // Check community_id exists
        if (isset($data['community_id'])) {
            $query = "SELECT community_id FROM communities WHERE community_id = :community_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $data['community_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Community ID {$data['community_id']} does not exist";
            }
        }
        
        // Check assistance_type_needed_id exists if provided
        if (isset($data['assistance_type_needed_id']) && !empty($data['assistance_type_needed_id'])) {
            $query = "SELECT assistance_type_id FROM assistance_types WHERE assistance_type_id = :assistance_type_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':assistance_type_id', $data['assistance_type_needed_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Assistance type ID {$data['assistance_type_needed_id']} does not exist";
            }
        }
        
        return $errors;
    }
}