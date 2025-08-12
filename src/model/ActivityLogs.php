<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * ActivityLogs Model
 * 
 * Handles database operations for activity_logs table
 * Table structure:
 * - activity_logs(log_id, user_id, activity, timestamp)
 * - Has foreign key relationship with users(user_id)
 */
class ActivityLogs
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'activity_logs';

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
     * Log a new activity
     * 
     * @param int $userId The user who performed the activity
     * @param string $activity Description of the activity
     * @return int|false The log_id of the new record or false on failure
     */
    public function logActivity(int $userId, string $activity): int|false
    {
        try {
            $query = "INSERT INTO {$this->tableName} (user_id, activity) VALUES (:user_id, :activity)";
            $stmt = $this->db->prepare($query);
            
            $params = [
                'user_id' => $userId,
                'activity' => $activity
            ];
            
            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = "Error logging activity: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get all activity logs with user information
     * 
     * @param int $limit Maximum number of logs to return
     * @param int $offset Offset for pagination
     * @return array List of activity logs
     */
    public function getAll(int $limit = 100, int $offset = 0): array
    {
        try {
            $query = "SELECT al.*, u.username, u.email, u.role 
                      FROM {$this->tableName} al
                      JOIN users u ON al.user_id = u.user_id
                      ORDER BY al.timestamp DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$this->executeQuery($stmt, [])) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching activity logs: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get total count of activity logs
     */
    public function getCount(): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM {$this->tableName}";
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt)) {
                return 0;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            $this->lastError = "Error counting activity logs: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Get activity logs for a specific user
     * 
     * @param int $userId The user ID to filter by
     * @param int $limit Maximum number of logs to return
     * @param int $offset Offset for pagination
     * @return array List of activity logs for the user
     */
    public function getByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT al.*, u.username, u.email, u.role 
                      FROM {$this->tableName} al
                      JOIN users u ON al.user_id = u.user_id
                      WHERE al.user_id = :user_id
                      ORDER BY al.timestamp DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$this->executeQuery($stmt, [])) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching user activity logs: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get activity logs by date range
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param int $limit Maximum number of logs to return
     * @param int $offset Offset for pagination
     * @return array List of activity logs in the date range
     */
    public function getByDateRange(string $startDate, string $endDate, int $limit = 100, int $offset = 0): array
    {
        try {
            $query = "SELECT al.*, u.username, u.email, u.role 
                      FROM {$this->tableName} al
                      JOIN users u ON al.user_id = u.user_id
                      WHERE DATE(al.timestamp) BETWEEN :start_date AND :end_date
                      ORDER BY al.timestamp DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$this->executeQuery($stmt, [])) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error fetching activity logs by date range: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Search activity logs by text in the activity field
     * 
     * @param string $searchTerm Text to search for
     * @param int $limit Maximum number of logs to return
     * @param int $offset Offset for pagination
     * @return array List of matching activity logs
     */
    public function searchLogs(string $searchTerm, int $limit = 100, int $offset = 0): array
    {
        try {
            $query = "SELECT al.*, u.username, u.email, u.role 
                      FROM {$this->tableName} al
                      JOIN users u ON al.user_id = u.user_id
                      WHERE al.activity LIKE :search_term
                      ORDER BY al.timestamp DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $searchParam = '%' . $searchTerm . '%';
            $stmt->bindParam(':search_term', $searchParam);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            if (!$this->executeQuery($stmt, [])) {
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = "Error searching activity logs: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get a specific log entry by ID
     * 
     * @param int $logId The log ID to retrieve
     * @return array|null The log entry or null if not found
     */
    public function getById(int $logId): ?array
    {
        try {
            $query = "SELECT al.*, u.username, u.email, u.role 
                      FROM {$this->tableName} al
                      JOIN users u ON al.user_id = u.user_id
                      WHERE al.log_id = :log_id";
            
            $stmt = $this->db->prepare($query);
            
            if (!$this->executeQuery($stmt, ['log_id' => $logId])) {
                return null;
            }
            
            $log = $stmt->fetch(PDO::FETCH_ASSOC);
            return $log ?: null;
        } catch (PDOException $e) {
            $this->lastError = "Error fetching activity log: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Delete activity logs older than a specified number of days
     * 
     * @param int $days Delete logs older than this many days
     * @return int Number of logs deleted
     */
    public function deleteOlderThan(int $days): int
    {
        try {
            $query = "DELETE FROM {$this->tableName} 
                      WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            
            if (!$this->executeQuery($stmt, [])) {
                return 0;
            }
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->lastError = "Error deleting old activity logs: " . $e->getMessage();
            return 0;
        }
    }
}