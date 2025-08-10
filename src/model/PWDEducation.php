<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * PWDEducation Model
 *
 * Handles database operations for the pwd_education table.
 * Schema: pwd_education(education_id, pwd_id, education_level, school_name)
 */
class PWDEducation
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'pwd_education';

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
     * Get all education records
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT education_id, pwd_id, education_level, school_name FROM {$this->tableName} ORDER BY education_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get education records: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get education record by ID
     */
    public function getById(int $educationId): ?array
    {
        try {
            $sql = "SELECT education_id, pwd_id, education_level, school_name FROM {$this->tableName} WHERE education_id = :education_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['education_id' => $educationId])) {
                return null;
            }
            $education = $stmt->fetch(PDO::FETCH_ASSOC);
            return $education ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get education by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get all education records for a specific PWD
     */
    public function getByPWDId(int $pwdId): array
    {
        try {
            $sql = "SELECT education_id, pwd_id, education_level, school_name FROM {$this->tableName} WHERE pwd_id = :pwd_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['pwd_id' => $pwdId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get education records by PWD ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new education record
     * @param array{pwd_id:int, education_level:string, school_name:string} $data
     * @return int|false Inserted education_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (!isset($data['pwd_id'], $data['education_level']) || $data['education_level'] === '') {
                $this->lastError = 'Missing required fields: pwd_id or education_level';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (pwd_id, education_level, school_name) 
                    VALUES (:pwd_id, :education_level, :school_name)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'pwd_id' => $data['pwd_id'],
                'education_level' => $data['education_level'],
                'school_name' => $data['school_name'] ?? null
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create education record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update education record
     */
    public function update(int $educationId, array $data): bool
    {
        try {
            if (!$this->getById($educationId)) {
                $this->lastError = 'Education record not found';
                return false;
            }

            if (!isset($data['education_level']) || $data['education_level'] === '') {
                $this->lastError = 'Education level is required for update';
                return false;
            }

            $sql = "UPDATE {$this->tableName} 
                    SET pwd_id = :pwd_id, education_level = :education_level, school_name = :school_name 
                    WHERE education_id = :education_id";
            $stmt = $this->db->prepare($sql);

            $params = [
                'pwd_id' => $data['pwd_id'] ?? null,
                'education_level' => $data['education_level'],
                'school_name' => $data['school_name'] ?? null,
                'education_id' => $educationId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update education record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete education record
     */
    public function delete(int $educationId): bool
    {
        try {
            if (!$this->getById($educationId)) {
                $this->lastError = 'Education record not found';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE education_id = :education_id");
            return $this->executeQuery($stmt, ['education_id' => $educationId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete education record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete all education records for a specific PWD
     */
    public function deleteByPWDId(int $pwdId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE pwd_id = :pwd_id");
            return $this->executeQuery($stmt, ['pwd_id' => $pwdId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete education records for PWD: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get statistics of education levels
     */
    public function getStatsOverall(): array
    {
        try {
            $sql = "SELECT 
                      education_level, 
                      COUNT(*) as count 
                    FROM {$this->tableName}
                    GROUP BY education_level
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get education statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get statistics of education levels by community
     */
    public function getStatsByCommunity(int $communityId): array
    {
        try {
            $sql = "SELECT 
                      e.education_level, 
                      COUNT(*) as count 
                    FROM {$this->tableName} e
                    JOIN pwd_records p ON e.pwd_id = p.pwd_id
                    WHERE p.community_id = :community_id
                    GROUP BY e.education_level
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['community_id' => $communityId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get community education statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}
