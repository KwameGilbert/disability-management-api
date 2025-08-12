<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Communities Model
 * 
 * Handles database operations for communities table
 * Table structure:
 * - communities(community_id, community_name)
 */
class Communities
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'communities';

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
     * Get all communities
     */
    public function getAll(): array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} ORDER BY community_name ASC";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching communities: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get community by ID
     */
    public function getById(int $communityId): ?array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE community_id = :community_id";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt, ['community_id' => $communityId])) {
                return null;
            }

            $community = $stmt->fetch(PDO::FETCH_ASSOC);
            return $community ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching community: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Get community by name
     */
    public function getByName(string $communityName): ?array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE community_name = :community_name";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt, ['community_name' => $communityName])) {
                return null;
            }

            $community = $stmt->fetch(PDO::FETCH_ASSOC);
            return $community ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching community: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create a new community
     * @param array{community_name:string} $data
     * @return int|false Inserted community_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            $query = "INSERT INTO {$this->tableName} (community_name) VALUES (:community_name)";
            $stmt = $this->db->prepare($query);

            if (!$this->executeQuery($stmt, ['community_name' => $data['community_name']])) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error creating community: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update community
     */
    public function update(int $communityId, array $data): bool
    {
        try {
            $query = "UPDATE {$this->tableName} SET community_name = :community_name WHERE community_id = :community_id";
            $stmt = $this->db->prepare($query);

            $params = [
                'community_name' => $data['community_name'],
                'community_id' => $communityId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = "Error updating community: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete community
     */
    public function delete(int $communityId): bool
    {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE community_id = :community_id";
            $stmt = $this->db->prepare($query);

            return $this->executeQuery($stmt, ['community_id' => $communityId]);
        } catch (PDOException $e) {
            $this->lastError = "Error deleting community: " . $e->getMessage();
            return false;
        }
    }
}
