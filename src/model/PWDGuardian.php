<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * PWDGuardian Model
 *
 * Handles database operations for the pwd_guardians table.
 * Schema: pwd_guardians(guardian_id, pwd_id, name, occupation, phone, relationship)
 */
class PWDGuardian
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'pwd_guardians';

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
     * Get all guardians
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT guardian_id, pwd_id, name, occupation, phone, relationship FROM {$this->tableName} ORDER BY name";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get guardians: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get guardian by ID
     */
    public function getById(int $guardianId): ?array
    {
        try {
            $sql = "SELECT guardian_id, pwd_id, name, occupation, phone, relationship FROM {$this->tableName} WHERE guardian_id = :guardian_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['guardian_id' => $guardianId])) {
                return null;
            }
            $guardian = $stmt->fetch(PDO::FETCH_ASSOC);
            return $guardian ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get guardian by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Create a new guardian
     * @param array{pwd_id:int, name:string, occupation:string, phone:string, relationship:string} $data
     * @return int|false Inserted guardian_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (!isset($data['pwd_id'], $data['name']) || $data['name'] === '') {
                $this->lastError = 'Missing required fields: pwd_id or name';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (pwd_id, name, occupation, phone, relationship) 
                    VALUES (:pwd_id, :name, :occupation, :phone, :relationship)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'pwd_id' => $data['pwd_id'],
                'name' => $data['name'],
                'occupation' => $data['occupation'] ?? null,
                'phone' => $data['phone'] ?? null,
                'relationship' => $data['relationship'] ?? null
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create guardian: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update guardian record
     */
    public function update(int $guardianId, array $data): bool
    {
        try {
            if (!$this->getById($guardianId)) {
                $this->lastError = 'Guardian not found';
                return false;
            }

            if (!isset($data['name']) || $data['name'] === '') {
                $this->lastError = 'Name is required for update';
                return false;
            }

            $sql = "UPDATE {$this->tableName} 
                    SET pwd_id = :pwd_id, name = :name, occupation = :occupation, phone = :phone, relationship = :relationship 
                    WHERE guardian_id = :guardian_id";
            $stmt = $this->db->prepare($sql);

            $params = [
                'pwd_id' => $data['pwd_id'] ?? null,
                'name' => $data['name'],
                'occupation' => $data['occupation'] ?? null,
                'phone' => $data['phone'] ?? null,
                'relationship' => $data['relationship'] ?? null,
                'guardian_id' => $guardianId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update guardian: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete guardian record
     */
    public function delete(int $guardianId): bool
    {
        try {
            if (!$this->getById($guardianId)) {
                $this->lastError = 'Guardian not found';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE guardian_id = :guardian_id");
            return $this->executeQuery($stmt, ['guardian_id' => $guardianId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete guardian: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
