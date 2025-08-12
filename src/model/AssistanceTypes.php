<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * AssistanceTypes Model
 * 
 * Handles database operations for assistance_types table
 * Table structure:
 * - assistance_types(assistance_type_id, assistance_type_name)
 * 
 * This table is referenced by:
 * - pwd_records(assistance_type_needed_id)
 * - assistance_requests(assistance_type_id)
 */
class AssistanceTypes
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'assistance_types';

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
     * Get all assistance types
     */
    public function getAll(): array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} ORDER BY assistance_type_name ASC";
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance types: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get assistance type by ID
     */
    public function getById(int $assistanceTypeId): ?array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE assistance_type_id = :assistance_type_id";
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['assistance_type_id' => $assistanceTypeId])) {
                return null;
            }
            
            $assistanceType = $stmt->fetch(PDO::FETCH_ASSOC);
            return $assistanceType ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance type: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Get assistance type by name
     */
    public function getByName(string $assistanceTypeName): ?array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE assistance_type_name = :assistance_type_name";
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['assistance_type_name' => $assistanceTypeName])) {
                return null;
            }
            
            $assistanceType = $stmt->fetch(PDO::FETCH_ASSOC);
            return $assistanceType ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance type: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create a new assistance type
     * @param array{assistance_type_name:string} $data
     * @return int|false Inserted assistance_type_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            $query = "INSERT INTO {$this->tableName} (assistance_type_name) VALUES (:assistance_type_name)";
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['assistance_type_name' => $data['assistance_type_name']])) {
                return false;
            }
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error creating assistance type: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update assistance type
     */
    public function update(int $assistanceTypeId, array $data): bool
    {
        try {
            $query = "UPDATE {$this->tableName} SET assistance_type_name = :assistance_type_name 
                      WHERE assistance_type_id = :assistance_type_id";
            $stmt = $this->db->prepare($query);
            
            $params = [
                'assistance_type_name' => $data['assistance_type_name'],
                'assistance_type_id' => $assistanceTypeId
            ];
            
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = "Error updating assistance type: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete assistance type
     */
    public function delete(int $assistanceTypeId): bool
    {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE assistance_type_id = :assistance_type_id";
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, ['assistance_type_id' => $assistanceTypeId]);
        } catch (PDOException $e) {
            $this->lastError = "Error deleting assistance type: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Check if an assistance type is in use in PWD records
     */
    public function getUsageCountInPwdRecords(int $assistanceTypeId): int
    {
        try {
            $query = "SELECT COUNT(*) AS count FROM pwd_records 
                      WHERE assistance_type_needed_id = :assistance_type_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['assistance_type_id' => $assistanceTypeId])) {
                return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = "Error checking assistance type usage in PWD records: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Check if an assistance type is in use in assistance requests
     */
    public function getUsageCountInAssistanceRequests(int $assistanceTypeId): int
    {
        try {
            $query = "SELECT COUNT(*) AS count FROM assistance_requests 
                      WHERE assistance_type_id = :assistance_type_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['assistance_type_id' => $assistanceTypeId])) {
                return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = "Error checking assistance type usage in requests: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Get total usage count of an assistance type
     */
    public function getTotalUsageCount(int $assistanceTypeId): int
    {
        return $this->getUsageCountInPwdRecords($assistanceTypeId) + 
               $this->getUsageCountInAssistanceRequests($assistanceTypeId);
    }
}