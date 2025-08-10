<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Communities Model
 *
 * Handles database operations for the communities table.
 * Schema: communities(community_id, community_name)
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
     * Fetch all communities
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT community_id, community_name FROM {$this->tableName} ORDER BY community_name";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get communities: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get a community by ID
     */
    public function getById(int $communityId): ?array
    {
        try {
            $sql = "SELECT community_id, community_name FROM {$this->tableName} WHERE community_id = :community_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['community_id' => $communityId])) {
                return null;
            }
            $community = $stmt->fetch(PDO::FETCH_ASSOC);
            return $community ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get community by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get a community by name
     */
    public function getByName(string $communityName): ?array
    {
        try {
            $sql = "SELECT community_id, community_name FROM {$this->tableName} WHERE community_name = :community_name";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['community_name' => $communityName])) {
                return null;
            }
            $community = $stmt->fetch(PDO::FETCH_ASSOC);
            return $community ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get community by name: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Create a new community
     * @param array{community_name: string} $data
     * @return int|false Inserted community_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (!isset($data['community_name']) || $data['community_name'] === '') {
                $this->lastError = 'Missing required field: community_name';
                return false;
            }

            if ($this->getByName($data['community_name'])) {
                $this->lastError = 'Community already exists with this name';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (community_name) VALUES (:community_name)";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['community_name' => $data['community_name']])) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create community: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update a community
     */
    public function update(int $communityId, array $data): bool
    {
        try {
            if (!$this->getById($communityId)) {
                $this->lastError = 'Community not found';
                return false;
            }

            if (!isset($data['community_name']) || $data['community_name'] === '') {
                $this->lastError = 'Community name is required for update';
                return false;
            }

            $existing = $this->getByName($data['community_name']);
            if ($existing && (int) $existing['community_id'] !== $communityId) {
                $this->lastError = 'Community name already in use by another community';
                return false;
            }

            $sql = "UPDATE {$this->tableName} SET community_name = :community_name WHERE community_id = :community_id";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, [
                'community_name' => $data['community_name'],
                'community_id' => $communityId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update community: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a community
     */
    public function delete(int $communityId): bool
    {
        try {
            if (!$this->getById($communityId)) {
                $this->lastError = 'Community not found';
                return false;
            }

            $sql = "DELETE FROM {$this->tableName} WHERE community_id = :community_id";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, ['community_id' => $communityId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete community: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
