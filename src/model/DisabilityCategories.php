<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * DisabilityCategories Model
 * 
 * Handles database operations for disability_categories table
 * Table structure:
 * - disability_categories(category_id, category_name)
 */
class DisabilityCategories
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'disability_categories';

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
     * Get all disability categories
     */
    public function getAll(): array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} ORDER BY category_name ASC";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability categories: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get disability category by ID
     */
    public function getById(int $categoryId): ?array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE category_id = :category_id";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt, ['category_id' => $categoryId])) {
                return null;
            }

            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            return $category ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability category: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Get disability category by name
     */
    public function getByName(string $categoryName): ?array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE category_name = :category_name";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt, ['category_name' => $categoryName])) {
                return null;
            }

            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            return $category ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching disability category: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create a new disability category
     * @param array{category_name:string} $data
     * @return int|false Inserted category_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            $query = "INSERT INTO {$this->tableName} (category_name) VALUES (:category_name)";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt, ['category_name' => $data['category_name']])) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error creating disability category: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update disability category
     */
    public function update(int $categoryId, array $data): bool
    {
        try {
            $query = "UPDATE {$this->tableName} SET category_name = :category_name WHERE category_id = :category_id";
            $stmt = $this->db->prepare($query);

            $params = [
                'category_name' => $data['category_name'],
                'category_id' => $categoryId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = "Error updating disability category: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete disability category
     */
    public function delete(int $categoryId): bool
    {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE category_id = :category_id";
            $stmt = $this->db->prepare($query);

            return $this->executeQuery($stmt, ['category_id' => $categoryId]);
        } catch (PDOException $e) {
            $this->lastError = "Error deleting disability category: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get disability types associated with this category
     */
    public function getAssociatedDisabilityTypes(int $categoryId): array
    {
        try {
            $query = "SELECT * FROM disability_types WHERE category_id = :category_id ORDER BY type_name ASC";
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
}
