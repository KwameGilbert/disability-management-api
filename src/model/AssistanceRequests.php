<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * AssistanceRequests Model
 * 
 * Handles database operations for assistance_requests table
 * Table structure:
 * - assistance_requests(request_id, assistance_type_id, beneficiary_id, requested_by, 
 *   description, amount_value_cost, admin_review_notes, status, created_at, updated_at)
 * 
 * Has foreign key relationships with:
 * - assistance_types(assistance_type_id)
 * - pwd_records(pwd_id) via beneficiary_id
 * - users(user_id) via requested_by
 */
class AssistanceRequests
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'assistance_requests';

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
     * Get all assistance requests with related data
     * 
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @param array $filters Optional array of filter conditions
     * @return array List of assistance requests
     */
    public function getAll(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        try {
            $query = "SELECT 
                        ar.*,
                        at.assistance_type_name,
                        p.full_name AS beneficiary_name,
                        u.username AS requested_by_username
                      FROM {$this->tableName} ar
                      LEFT JOIN assistance_types at ON ar.assistance_type_id = at.assistance_type_id
                      LEFT JOIN pwd_records p ON ar.beneficiary_id = p.pwd_id
                      LEFT JOIN users u ON ar.requested_by = u.user_id";
            
            $whereConditions = [];
            $params = [];
            
            // Apply filters if provided
            if (!empty($filters)) {
                foreach ($filters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'search') {
                            // Special case for search
                            $whereConditions[] = "(p.full_name LIKE :search OR ar.description LIKE :search)";
                            $params['search'] = "%{$value}%";
                        } elseif ($column === 'beneficiary_name') {
                            $whereConditions[] = "p.full_name LIKE :beneficiary_name";
                            $params['beneficiary_name'] = "%{$value}%";
                        } else {
                            $whereConditions[] = "ar.{$column} = :{$column}";
                            $params[$column] = $value;
                        }
                    }
                }
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $query .= " ORDER BY ar.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            // Bind limit and offset params
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Bind filter params if any
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance requests: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get total count of assistance requests
     * 
     * @param array $filters Optional array of filter conditions
     * @return int Count of records
     */
    public function getCount(array $filters = []): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->tableName} ar";
            
            // Join tables if needed for filtering
            if (!empty($filters) && (isset($filters['search']) || isset($filters['beneficiary_name']))) {
                $query .= " LEFT JOIN pwd_records p ON ar.beneficiary_id = p.pwd_id";
            }
            
            $whereConditions = [];
            $params = [];
            
            // Apply filters if provided
            if (!empty($filters)) {
                foreach ($filters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'search') {
                            // Special case for search
                            $whereConditions[] = "(p.full_name LIKE :search OR ar.description LIKE :search)";
                            $params['search'] = "%{$value}%";
                        } elseif ($column === 'beneficiary_name') {
                            $whereConditions[] = "p.full_name LIKE :beneficiary_name";
                            $params['beneficiary_name'] = "%{$value}%";
                        } else {
                            $whereConditions[] = "ar.{$column} = :{$column}";
                            $params[$column] = $value;
                        }
                    }
                }
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $stmt = $this->db->prepare($query);
            
            // Bind filter params if any
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing count query: " . implode(" ", $stmt->errorInfo());
                return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = "Error counting assistance requests: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Get assistance request by ID with related data
     * 
     * @param int $requestId Assistance request ID to retrieve
     * @return array|null Assistance request or null if not found
     */
    public function getById(int $requestId): ?array
    {
        try {
            $query = "SELECT 
                        ar.*,
                        at.assistance_type_name,
                        p.full_name AS beneficiary_name,
                        p.contact AS beneficiary_contact,
                        p.community_id,
                        c.community_name,
                        u.username AS requested_by_username,
                        u.role AS requested_by_role
                      FROM {$this->tableName} ar
                      LEFT JOIN assistance_types at ON ar.assistance_type_id = at.assistance_type_id
                      LEFT JOIN pwd_records p ON ar.beneficiary_id = p.pwd_id
                      LEFT JOIN communities c ON p.community_id = c.community_id
                      LEFT JOIN users u ON ar.requested_by = u.user_id
                      WHERE ar.request_id = :request_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['request_id' => $requestId])) {
                return null;
            }
            
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            return $request ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance request: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create a new assistance request
     * 
     * @param array $data Assistance request data
     * @return int|false Inserted request ID or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            // Prepare query dynamically based on available fields
            $columns = [];
            $placeholders = [];
            $params = [];
            
            $allowedFields = [
                'assistance_type_id', 'beneficiary_id', 'requested_by', 
                'description', 'amount_value_cost', 'admin_review_notes', 'status'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $columns[] = $field;
                    $placeholders[] = ":{$field}";
                    $params[$field] = $data[$field];
                }
            }
            
            $query = "INSERT INTO {$this->tableName} (" . implode(", ", $columns) . ") 
                      VALUES (" . implode(", ", $placeholders) . ")";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing insert: " . implode(" ", $stmt->errorInfo());
                return false;
            }
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error creating assistance request: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update an existing assistance request
     * 
     * @param int $requestId Assistance request ID to update
     * @param array $data Assistance request data
     * @return bool True on success, false on failure
     */
    public function update(int $requestId, array $data): bool
    {
        try {
            // Prepare query dynamically based on available fields
            $setStatements = [];
            $params = ['request_id' => $requestId];
            
            $allowedFields = [
                'assistance_type_id', 'beneficiary_id', 'requested_by', 
                'description', 'amount_value_cost', 'admin_review_notes', 'status'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $setStatements[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($setStatements)) {
                return true; // Nothing to update
            }
            
            $query = "UPDATE {$this->tableName} SET " . implode(", ", $setStatements) . " 
                      WHERE request_id = :request_id";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue(":{$param}", $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = "Error updating assistance request: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete an assistance request
     * 
     * @param int $requestId Assistance request ID to delete
     * @return bool True on success, false on failure
     */
    public function delete(int $requestId): bool
    {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE request_id = :request_id";
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, ['request_id' => $requestId]);
        } catch (PDOException $e) {
            $this->lastError = "Error deleting assistance request: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update status of an assistance request
     * 
     * @param int $requestId Assistance request ID
     * @param string $status New status ('pending','review','ready_to_access','assessed','declined')
     * @param string|null $adminNotes Optional admin review notes
     * @return bool True on success, false on failure
     */
    public function updateStatus(int $requestId, string $status, ?string $adminNotes = null): bool
    {
        try {
            $validStatuses = ['pending', 'review', 'ready_to_access', 'assessed', 'declined'];
            if (!in_array($status, $validStatuses)) {
                $this->lastError = "Invalid status value: {$status}";
                return false;
            }
            
            $query = "UPDATE {$this->tableName} SET status = :status";
            $params = [
                'request_id' => $requestId,
                'status' => $status
            ];
            
            if ($adminNotes !== null) {
                $query .= ", admin_review_notes = :admin_notes";
                $params['admin_notes'] = $adminNotes;
            }
            
            $query .= " WHERE request_id = :request_id";
            
            $stmt = $this->db->prepare($query);
            
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = "Error updating assistance request status: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get assistance requests by beneficiary ID (PWD ID)
     * 
     * @param int $beneficiaryId PWD ID
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array List of assistance requests
     */
    public function getByBeneficiary(int $beneficiaryId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT 
                        ar.*,
                        at.assistance_type_name,
                        u.username AS requested_by_username
                      FROM {$this->tableName} ar
                      LEFT JOIN assistance_types at ON ar.assistance_type_id = at.assistance_type_id
                      LEFT JOIN users u ON ar.requested_by = u.user_id
                      WHERE ar.beneficiary_id = :beneficiary_id
                      ORDER BY ar.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':beneficiary_id', $beneficiaryId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance requests by beneficiary: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get assistance requests by requested_by user ID
     * 
     * @param int $userId User ID who requested the assistance
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array List of assistance requests
     */
    public function getByRequestedUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT 
                        ar.*,
                        at.assistance_type_name,
                        p.full_name AS beneficiary_name
                      FROM {$this->tableName} ar
                      LEFT JOIN assistance_types at ON ar.assistance_type_id = at.assistance_type_id
                      LEFT JOIN pwd_records p ON ar.beneficiary_id = p.pwd_id
                      WHERE ar.requested_by = :user_id
                      ORDER BY ar.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance requests by user: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get assistance requests by status
     * 
     * @param string $status Status to filter by
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array List of assistance requests
     */
    public function getByStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        try {
            $validStatuses = ['pending', 'review', 'ready_to_access', 'assessed', 'declined'];
            if (!in_array($status, $validStatuses)) {
                $this->lastError = "Invalid status value: {$status}";
                return [];
            }
            
            $query = "SELECT 
                        ar.*,
                        at.assistance_type_name,
                        p.full_name AS beneficiary_name,
                        u.username AS requested_by_username
                      FROM {$this->tableName} ar
                      LEFT JOIN assistance_types at ON ar.assistance_type_id = at.assistance_type_id
                      LEFT JOIN pwd_records p ON ar.beneficiary_id = p.pwd_id
                      LEFT JOIN users u ON ar.requested_by = u.user_id
                      WHERE ar.status = :status
                      ORDER BY ar.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->lastError = "Error executing query: " . implode(" ", $stmt->errorInfo());
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching assistance requests by status: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Verify foreign keys exist
     * 
     * @param array $data Data to validate
     * @return array Array of error messages, empty if all validations pass
     */
    public function validateForeignKeys(array $data): array
    {
        $errors = [];
        
        // Check assistance_type_id exists
        if (isset($data['assistance_type_id'])) {
            $query = "SELECT assistance_type_id FROM assistance_types WHERE assistance_type_id = :assistance_type_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':assistance_type_id', $data['assistance_type_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Assistance type ID {$data['assistance_type_id']} does not exist";
            }
        }
        
        // Check beneficiary_id exists
        if (isset($data['beneficiary_id'])) {
            $query = "SELECT pwd_id FROM pwd_records WHERE pwd_id = :beneficiary_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':beneficiary_id', $data['beneficiary_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "Beneficiary ID {$data['beneficiary_id']} does not exist";
            }
        }
        
        // Check requested_by user exists
        if (isset($data['requested_by'])) {
            $query = "SELECT user_id FROM users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['requested_by'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errors[] = "User ID {$data['requested_by']} does not exist";
            }
        }
        
        return $errors;
    }
}