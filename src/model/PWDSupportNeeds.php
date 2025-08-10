<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * PWDSupportNeeds Model
 *
 * Handles database operations for the pwd_support_needs table.
 * Schema: pwd_support_needs(need_id, pwd_id, assistance_needed)
 */
class PWDSupportNeeds
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'pwd_support_needs';

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
     * Get all support needs records
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT n.need_id, n.pwd_id, n.assistance_needed, p.full_name as pwd_name 
                   FROM {$this->tableName} n
                   JOIN pwd_records p ON n.pwd_id = p.pwd_id
                   ORDER BY n.need_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get support needs records: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get support needs record by ID
     */
    public function getById(int $needId): ?array
    {
        try {
            $sql = "SELECT n.need_id, n.pwd_id, n.assistance_needed, p.full_name as pwd_name 
                   FROM {$this->tableName} n
                   JOIN pwd_records p ON n.pwd_id = p.pwd_id
                   WHERE n.need_id = :need_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['need_id' => $needId])) {
                return null;
            }
            $need = $stmt->fetch(PDO::FETCH_ASSOC);
            return $need ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get support needs by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get all support needs records for a specific PWD
     */
    public function getByPWDId(int $pwdId): array
    {
        try {
            $sql = "SELECT n.need_id, n.pwd_id, n.assistance_needed, p.full_name as pwd_name 
                   FROM {$this->tableName} n
                   JOIN pwd_records p ON n.pwd_id = p.pwd_id
                   WHERE n.pwd_id = :pwd_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['pwd_id' => $pwdId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get support needs records by PWD ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new support needs record
     * @param array{pwd_id:int, assistance_needed:string} $data
     * @return int|false Inserted need_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (!isset($data['pwd_id'], $data['assistance_needed']) || $data['assistance_needed'] === '') {
                $this->lastError = 'Missing required fields: pwd_id or assistance_needed';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (pwd_id, assistance_needed) 
                    VALUES (:pwd_id, :assistance_needed)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'pwd_id' => $data['pwd_id'],
                'assistance_needed' => $data['assistance_needed']
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create support needs record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update support needs record
     */
    public function update(int $needId, array $data): bool
    {
        try {
            if (!$this->getById($needId)) {
                $this->lastError = 'Support needs record not found';
                return false;
            }

            if (!isset($data['assistance_needed']) || $data['assistance_needed'] === '') {
                $this->lastError = 'Assistance needed is required for update';
                return false;
            }

            $sql = "UPDATE {$this->tableName} 
                    SET pwd_id = :pwd_id, assistance_needed = :assistance_needed 
                    WHERE need_id = :need_id";
            $stmt = $this->db->prepare($sql);

            $params = [
                'pwd_id' => $data['pwd_id'] ?? null,
                'assistance_needed' => $data['assistance_needed'],
                'need_id' => $needId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update support needs record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete support needs record
     */
    public function delete(int $needId): bool
    {
        try {
            if (!$this->getById($needId)) {
                $this->lastError = 'Support needs record not found';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE need_id = :need_id");
            return $this->executeQuery($stmt, ['need_id' => $needId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete support needs record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete all support needs for a specific PWD
     */
    public function deleteByPWDId(int $pwdId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE pwd_id = :pwd_id");
            return $this->executeQuery($stmt, ['pwd_id' => $pwdId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete support needs for PWD: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Search for support needs by assistance needed text
     */
    public function searchByNeed(string $searchTerm): array
    {
        try {
            $sql = "SELECT n.need_id, n.pwd_id, n.assistance_needed, p.full_name as pwd_name 
                   FROM {$this->tableName} n
                   JOIN pwd_records p ON n.pwd_id = p.pwd_id
                   WHERE n.assistance_needed LIKE :search_term
                   ORDER BY n.need_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['search_term' => '%' . $searchTerm . '%'])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to search support needs: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}
