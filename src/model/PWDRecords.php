<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * PWDRecords Model
 *
 * Handles database operations for the pwd_records table.
 * Schema: pwd_records(pwd_id, officer_id, quarter, gender, full_name, occupation, contact, 
 *          dob, age, disability_category, disability_type, gh_card_number, nhis_number,
 *          community_id, status, profile_image, created_at)
 */
class PWDRecords
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
            $connection = $database->getConnection();
            if (!$connection) {
                throw new PDOException('Database connection is null');
            }
            $this->db = $connection;
        } catch (PDOException $e) {
            $this->lastError = 'Database connection failed: ' . $e->getMessage();
            error_log($this->lastError);
            throw $e;
        }
    }

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
            $this->lastError = 'Query execution failed: ' . $e->getMessage();
            error_log($this->lastError . ' - SQL: ' . $statement->queryString);
            return false;
        }
    }

    /**
     * Get all PWD records with related data
     * 
     * @param int|null $limit Optional limit on number of records returned
     * @param int|null $offset Optional offset for pagination
     */
    public function getAll(?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT 
                      p.pwd_id, p.officer_id, p.quarter, p.gender, p.full_name, 
                      p.occupation, p.contact, p.dob, p.age, p.disability_category, 
                      p.disability_type, p.gh_card_number, p.nhis_number, p.community_id, 
                      p.status, p.profile_image, p.created_at,
                      u.username as officer_name,
                      c.community_name
                    FROM {$this->tableName} p
                    JOIN users u ON p.officer_id = u.user_id
                    JOIN communities c ON p.community_id = c.community_id
                    ORDER BY p.created_at DESC";

            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->db->prepare($sql);

            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                if ($offset !== null) {
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                }
            }

            if (!$this->executeQuery($stmt, [])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get PWD records: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get total count of PWD records
     */
    public function getCount(): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->tableName}";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return 0;
            }
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get PWD records count: ' . $e->getMessage();
            error_log($this->lastError);
            return 0;
        }
    }

    /**
     * Get PWD record by ID with related data
     */
    public function getByPWDId(int $pwdId): ?array
    {
        try {
            $sql = "SELECT 
                      p.pwd_id, p.officer_id, p.quarter, p.gender, p.full_name, 
                      p.occupation, p.contact, p.dob, p.age, p.disability_category, 
                      p.disability_type, p.gh_card_number, p.nhis_number, p.community_id, 
                      p.status, p.profile_image, p.created_at,
                      u.username as officer_name,
                      c.community_name
                    FROM {$this->tableName} p
                    JOIN users u ON p.officer_id = u.user_id
                    JOIN communities c ON p.community_id = c.community_id
                    WHERE p.pwd_id = :pwd_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['pwd_id' => $pwdId])) {
                return null;
            }
            $pwd = $stmt->fetch(PDO::FETCH_ASSOC);
            return $pwd ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get PWD by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get PWD records by officer ID
     */
    public function getByOfficerId(int $officerId): array
    {
        try {
            $sql = "SELECT 
                      p.pwd_id, p.officer_id, p.quarter, p.gender, p.full_name, 
                      p.occupation, p.contact, p.dob, p.age, p.disability_category, 
                      p.disability_type, p.gh_card_number, p.nhis_number, p.community_id, 
                      p.status, p.profile_image, p.created_at,
                      u.username as officer_name,
                      c.community_name
                    FROM {$this->tableName} p
                    JOIN users u ON p.officer_id = u.user_id
                    JOIN communities c ON p.community_id = c.community_id
                    WHERE p.officer_id = :officer_id
                    ORDER BY p.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['officer_id' => $officerId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get PWD records by officer ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get PWD records by community ID
     */
    public function getByCommunityId(int $communityId): array
    {
        try {
            $sql = "SELECT 
                      p.pwd_id, p.officer_id, p.quarter, p.gender, p.full_name, 
                      p.occupation, p.contact, p.dob, p.age, p.disability_category, 
                      p.disability_type, p.gh_card_number, p.nhis_number, p.community_id, 
                      p.status, p.profile_image, p.created_at,
                      u.username as officer_name,
                      c.community_name
                    FROM {$this->tableName} p
                    JOIN users u ON p.officer_id = u.user_id
                    JOIN communities c ON p.community_id = c.community_id
                    WHERE p.community_id = :community_id
                    ORDER BY p.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['community_id' => $communityId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get PWD records by community ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get PWD records by status
     */
    public function getByStatus(string $status): array
    {
        try {
            if (!in_array($status, ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be "pending", "approved", or "disapproved"';
                return [];
            }

            $sql = "SELECT 
                      p.pwd_id, p.officer_id, p.quarter, p.gender, p.full_name, 
                      p.occupation, p.contact, p.dob, p.age, p.disability_category, 
                      p.disability_type, p.gh_card_number, p.nhis_number, p.community_id, 
                      p.status, p.profile_image, p.created_at,
                      u.username as officer_name,
                      c.community_name
                    FROM {$this->tableName} p
                    JOIN users u ON p.officer_id = u.user_id
                    JOIN communities c ON p.community_id = c.community_id
                    WHERE p.status = :status
                    ORDER BY p.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['status' => $status])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get PWD records by status: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Search PWD records by name, disability type, or ID number
     */
    public function search(string $searchTerm): array
    {
        try {
            $sql = "SELECT 
                      p.pwd_id, p.officer_id, p.quarter, p.gender, p.full_name, 
                      p.occupation, p.contact, p.dob, p.age, p.disability_category, 
                      p.disability_type, p.gh_card_number, p.nhis_number, p.community_id, 
                      p.status, p.profile_image, p.created_at,
                      u.username as officer_name,
                      c.community_name
                    FROM {$this->tableName} p
                    JOIN users u ON p.officer_id = u.user_id
                    JOIN communities c ON p.community_id = c.community_id
                    WHERE p.full_name LIKE :search 
                       OR p.disability_type LIKE :search 
                       OR p.gh_card_number LIKE :search
                       OR p.nhis_number LIKE :search
                    ORDER BY p.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['search' => '%' . $searchTerm . '%'])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to search PWD records: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new PWD record
     * @param array $data Array with all required PWD fields
     * @return int|false Inserted pwd_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            $requiredFields = [
                'officer_id',
                'quarter',
                'gender',
                'full_name',
                'occupation',
                'contact',
                'dob',
                'age',
                'disability_category',
                'disability_type',
                'gh_card_number',
                'community_id',
                'profile_image'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || ($field !== 'age' && $data[$field] === '')) {
                    $this->lastError = "Missing required field: {$field}";
                    return false;
                }
            }

            if (!in_array($data['quarter'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return false;
            }

            if (!in_array($data['gender'], ['male', 'female', 'other'])) {
                $this->lastError = 'Invalid gender value. Must be male, female, or other';
                return false;
            }

            if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be pending, approved, or disapproved';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} 
                    (officer_id, quarter, gender, full_name, occupation, contact, 
                     dob, age, disability_category, disability_type, gh_card_number, 
                     nhis_number, community_id, status, profile_image) 
                    VALUES 
                    (:officer_id, :quarter, :gender, :full_name, :occupation, :contact, 
                     :dob, :age, :disability_category, :disability_type, :gh_card_number, 
                     :nhis_number, :community_id, :status, :profile_image)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'officer_id' => $data['officer_id'],
                'quarter' => $data['quarter'],
                'gender' => $data['gender'],
                'full_name' => $data['full_name'],
                'occupation' => $data['occupation'],
                'contact' => $data['contact'],
                'dob' => $data['dob'],
                'age' => $data['age'],
                'disability_category' => $data['disability_category'],
                'disability_type' => $data['disability_type'],
                'gh_card_number' => $data['gh_card_number'],
                'nhis_number' => $data['nhis_number'] ?? null,
                'community_id' => $data['community_id'],
                'status' => $data['status'] ?? 'pending',
                'profile_image' => $data['profile_image']
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create PWD record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update PWD record
     */
    public function update(int $pwdId, array $data): bool
    {
        try {
            if (!$this->getByPWDId($pwdId)) {
                $this->lastError = 'PWD record not found';
                return false;
            }

            if (isset($data['quarter']) && !in_array($data['quarter'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return false;
            }

            if (isset($data['gender']) && !in_array($data['gender'], ['male', 'female', 'other'])) {
                $this->lastError = 'Invalid gender value. Must be male, female, or other';
                return false;
            }

            if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be pending, approved, or disapproved';
                return false;
            }

            $currentPwd = $this->getByPWDId($pwdId);

            $sql = "UPDATE {$this->tableName} SET ";
            $fields = [
                'officer_id',
                'quarter',
                'gender',
                'full_name',
                'occupation',
                'contact',
                'dob',
                'age',
                'disability_category',
                'disability_type',
                'gh_card_number',
                'nhis_number',
                'community_id',
                'status',
                'profile_image'
            ];

            $updateFields = [];
            $params = [];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                $this->lastError = 'No fields to update';
                return false;
            }

            $sql .= implode(', ', $updateFields);
            $sql .= " WHERE pwd_id = :pwd_id";
            $params['pwd_id'] = $pwdId;

            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update PWD record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update PWD status
     */
    public function updateStatus(int $pwdId, string $status): bool
    {
        try {
            if (!$this->getByPWDId($pwdId)) {
                $this->lastError = 'PWD record not found';
                return false;
            }

            if (!in_array($status, ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be pending, approved, or disapproved';
                return false;
            }

            $sql = "UPDATE {$this->tableName} SET status = :status WHERE pwd_id = :pwd_id";
            $stmt = $this->db->prepare($sql);

            return $this->executeQuery($stmt, [
                'status' => $status,
                'pwd_id' => $pwdId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update PWD status: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete PWD record
     */
    public function delete(int $pwdId): bool
    {
        try {
            if (!$this->getByPWDId($pwdId)) {
                $this->lastError = 'PWD record not found';
                return false;
            }

            // Check for related records that should be deleted first
            $relatedTables = ['pwd_guardians', 'pwd_education', 'pwd_support_needs'];
            foreach ($relatedTables as $table) {
                $sql = "DELETE FROM {$table} WHERE pwd_id = :pwd_id";
                $stmt = $this->db->prepare($sql);
                if (!$this->executeQuery($stmt, ['pwd_id' => $pwdId])) {
                    $this->lastError = "Failed to delete related records from {$table}";
                    return false;
                }
            }

            // Delete supporting documents related to this PWD
            $sql = "DELETE FROM supporting_documents WHERE related_type = 'pwd' AND related_id = :pwd_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['pwd_id' => $pwdId])) {
                $this->lastError = "Failed to delete related supporting documents";
                return false;
            }

            // Delete the PWD record
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE pwd_id = :pwd_id");
            return $this->executeQuery($stmt, ['pwd_id' => $pwdId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete PWD record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get statistics by quarter and year
     */
    public function getStatsByQuarterYear(string $quarter, int $year): array
    {
        try {
            if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return [];
            }

            $startDate = "{$year}-" . ($quarter == 'Q1' ? '01-01' : ($quarter == 'Q2' ? '04-01' : ($quarter == 'Q3' ? '07-01' : '10-01')));
            $endDate = "{$year}-" . ($quarter == 'Q1' ? '03-31' : ($quarter == 'Q2' ? '06-30' : ($quarter == 'Q3' ? '09-30' : '12-31')));

            $sql = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                      SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                      SUM(CASE WHEN status = 'disapproved' THEN 1 ELSE 0 END) as disapproved,
                      COUNT(DISTINCT community_id) as communities_count,
                      SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male_count,
                      SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female_count,
                      SUM(CASE WHEN gender = 'other' THEN 1 ELSE 0 END) as other_gender_count
                    FROM {$this->tableName}
                    WHERE quarter = :quarter
                      AND created_at BETWEEN :start_date AND :end_date";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])) {
                return [];
            }

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}
