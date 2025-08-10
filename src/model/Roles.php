<?php
declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Roles Model
 * 
 * Handles database operations for the roles table.
 * Schema: roles(role_id, role_name)
 */
class Roles
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'roles';

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
     * Get all roles
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT role_id, role_name FROM {$this->tableName} ORDER BY role_name";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get roles: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get role by ID
     */
    public function getById(int $roleId): ?array
    {
        try {
            $sql = "SELECT role_id, role_name FROM {$this->tableName} WHERE role_id = :role_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['role_id' => $roleId])) {
                return null;
            }
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get role by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get role by name
     */
    public function getByName(string $roleName): ?array
    {
        try {
            $sql = "SELECT role_id, role_name FROM {$this->tableName} WHERE role_name = :role_name";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['role_name' => $roleName])) {
                return null;
            }
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get role by name: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Create a new role
     * @param array{role_name: string} $data
     * @return int|false Inserted role_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            // Validate required fields
            if (!isset($data['role_name']) || $data['role_name'] === '') {
                $this->lastError = 'Missing required field: role_name';
                return false;
            }

            // Check if role already exists
            if ($this->getByName($data['role_name'])) {
                $this->lastError = 'Role already exists with this name';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (role_name) VALUES (:role_name)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'role_name' => $data['role_name']
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create role: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update a role
     */
    public function update(int $roleId, array $data): bool
    {
        try {
            if (!$this->getById($roleId)) {
                $this->lastError = 'Role not found';
                return false;
            }

            if (!isset($data['role_name']) || $data['role_name'] === '') {
                $this->lastError = 'Role name is required for update';
                return false;
            }

            // Check if another role with the same name exists
            $existing = $this->getByName($data['role_name']);
            if ($existing && (int) $existing['role_id'] !== $roleId) {
                $this->lastError = 'Role name already in use by another role';
                return false;
            }

            $sql = "UPDATE {$this->tableName} SET role_name = :role_name WHERE role_id = :role_id";
            $stmt = $this->db->prepare($sql);

            return $this->executeQuery($stmt, [
                'role_name' => $data['role_name'],
                'role_id' => $roleId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update role: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a role
     */
    public function delete(int $roleId): bool
    {
        try {
            if (!$this->getById($roleId)) {
                $this->lastError = 'Role not found';
                return false;
            }

            // Check if role is being used by any users
            $stmt = $this->db->prepare("SELECT COUNT(*) as user_count FROM users WHERE role_id = :role_id");
            if (!$this->executeQuery($stmt, ['role_id' => $roleId])) {
                return false;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && (int) $result['user_count'] > 0) {
                $this->lastError = 'Cannot delete role: it is being used by one or more users';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE role_id = :role_id");
            return $this->executeQuery($stmt, ['role_id' => $roleId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete role: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get users count for each role
     */
    public function getRolesWithUserCount(): array
    {
        try {
            $sql = "SELECT r.role_id, r.role_name, COUNT(u.user_id) as user_count 
                    FROM {$this->tableName} r 
                    LEFT JOIN users u ON r.role_id = u.role_id 
                    GROUP BY r.role_id, r.role_name 
                    ORDER BY r.role_name";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get roles with user count: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}

