<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * DisabilityTypes Model
 * 
 * Handles database operations for disability_types table
 * Table structure:
 * - disability_types(type_id, category_id, type_name)
 * - Has foreign key relationship with disability_categories(category_id)
 */
class DisabilityTypes
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'disability_types';

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
     * Get all disability types
     * @param bool $includeCategory Whether to join with category data
     * @return array
     */
    public function getAll(bool $includeCategory = true): array
    {
        try {
            if ($includeCategory) {
                $query = "SELECT dt.*, dc.category_name 
                          FROM {$this->tableName} dt
                          JOIN disability_categories dc ON dt.category_id = dc.category_id
                          ORDER BY dt.type_name ASC";
            } else {
                $query = "SELECT * FROM {$this->tableName} ORDER BY type_name ASC";
            }
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability types: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get disability types by category ID
     */
    public function getByCategory(int $categoryId): array
    {
        try {
            $query = "SELECT dt.*, dc.category_name 
                      FROM {$this->tableName} dt
                      JOIN disability_categories dc ON dt.category_id = dc.category_id
                      WHERE dt.category_id = :category_id
                      ORDER BY dt.type_name ASC";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['category_id' => $categoryId])) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability types: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get disability type by ID
     * @param bool $includeCategory Whether to join with category data
     */
    public function getById(int $typeId, bool $includeCategory = true): ?array
    {
        try {
            if ($includeCategory) {
                $query = "SELECT dt.*, dc.category_name 
                          FROM {$this->tableName} dt
                          JOIN disability_categories dc ON dt.category_id = dc.category_id
                          WHERE dt.type_id = :type_id";
            } else {
                $query = "SELECT * FROM {$this->tableName} WHERE type_id = :type_id";
            }
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['type_id' => $typeId])) {
                return null;
            }
            
            $type = $stmt->fetch(PDO::FETCH_ASSOC);
            return $type ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability type: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Get disability type by name
     */
    public function getByName(string $typeName): ?array
    {
        try {
            $query = "SELECT dt.*, dc.category_name 
                      FROM {$this->tableName} dt
                      JOIN disability_categories dc ON dt.category_id = dc.category_id
                      WHERE dt.type_name = :type_name";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['type_name' => $typeName])) {
                return null;
            }
            
            $type = $stmt->fetch(PDO::FETCH_ASSOC);
            return $type ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability type: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create a new disability type
     * @param array{category_id:int, type_name:string} $data
     * @return int|false Inserted type_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            $query = "INSERT INTO {$this->tableName} (category_id, type_name) 
                      VALUES (:category_id, :type_name)";
            
            $stmt = $this->db->prepare($query);
            
            $params = [
                'category_id' => $data['category_id'],
                'type_name' => $data['type_name']
            ];
            
            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error creating disability type: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update disability type
     */
    public function update(int $typeId, array $data): bool
    {
        try {
            $updateFields = [];
            $params = ['type_id' => $typeId];
            
            // Only include fields that are provided
            if (isset($data['category_id'])) {
                $updateFields[] = "category_id = :category_id";
                $params['category_id'] = $data['category_id'];
            }
            
            if (isset($data['type_name'])) {
                $updateFields[] = "type_name = :type_name";
                $params['type_name'] = $data['type_name'];
            }
            
            if (empty($updateFields)) {
                return true; // Nothing to update
            }
            
            $query = "UPDATE {$this->tableName} SET " . implode(", ", $updateFields) . 
                     " WHERE type_id = :type_id";
            
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = "Error updating disability type: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete disability type
     */
    public function delete(int $typeId): bool
    {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE type_id = :type_id";
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, ['type_id' => $typeId]);
        } catch (PDOException $e) {
            $this->lastError = "Error deleting disability type: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Check if a disability type is in use
     * Returns the count of PWD records using this type
     */
    public function getUsageCount(int $typeId): int
    {
        try {
            $query = "SELECT COUNT(*) AS count FROM pwd_records 
                      WHERE disability_type_id = :type_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['type_id' => $typeId])) {
                return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = "Error checking disability type usage: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Verify that a category exists
     */
    public function categoryExists(int $categoryId): bool
    {
        try {
            $query = "SELECT category_id FROM disability_categories 
                      WHERE category_id = :category_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['category_id' => $categoryId])) {
                return false;
            }
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->lastError = "Error verifying category: " . $e->getMessage();
            return false;
        }
    }
}