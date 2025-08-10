<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * ActivityLog Model
 *
 * Handles database operations for the activity_logs table.
 * Schema: activity_logs(log_id, user_id, activity, timestamp)
 */
class ActivityLog
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
     * Log an activity
     * 
     * @param int $userId User ID performing the activity
     * @param string $activity Description of the activity
     * @return int|false Inserted log_id or false on failure
     */
    public function log(int $userId, string $activity): int|false
    {
        try {
            if (empty($activity)) {
                $this->lastError = 'Activity description is required';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (user_id, activity) VALUES (:user_id, :activity)";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [
                'user_id' => $userId,
                'activity' => $activity
            ])) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to log activity: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get all activity logs with pagination
     * 
     * @param int|null $limit Optional limit on number of logs returned
     * @param int|null $offset Optional offset for pagination
     * @return array Array of activity logs
     */
    public function getAll(?int $limit = null, ?int $offset = null): array
    {
        try {
            $sql = "SELECT a.log_id, a.user_id, a.activity, a.timestamp, u.username 
                    FROM {$this->tableName} a
                    JOIN users u ON a.user_id = u.user_id
                    ORDER BY a.timestamp DESC";

            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->db->prepare($sql);

            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                if ($offset !== null) {
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                }
            }

            if (!$this->executeQuery($stmt, [])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get activity logs: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get logs for a specific user
     * 
     * @param int $userId User ID to get logs for
     * @param int|null $limit Optional limit on number of logs returned
     * @return array Array of activity logs for the user
     */
    public function getByUserId(int $userId, ?int $limit = null): array
    {
        try {
            $sql = "SELECT a.log_id, a.user_id, a.activity, a.timestamp, u.username 
                    FROM {$this->tableName} a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.user_id = :user_id
                    ORDER BY a.timestamp DESC";

            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }

            if (!$this->executeQuery($stmt, [])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get user activity logs: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Search logs by activity description
     */
    public function searchByActivity(string $searchTerm): array
    {
        try {
            $sql = "SELECT a.log_id, a.user_id, a.activity, a.timestamp, u.username 
                    FROM {$this->tableName} a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.activity LIKE :search_term
                    ORDER BY a.timestamp DESC";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, ['search_term' => '%' . $searchTerm . '%'])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to search activity logs: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get logs by date range
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT a.log_id, a.user_id, a.activity, a.timestamp, u.username 
                    FROM {$this->tableName} a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.timestamp BETWEEN :start_date AND :end_date
                    ORDER BY a.timestamp DESC";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get logs by date range: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Delete logs older than a specified date
     */
    public function deleteOlderThan(string $date): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE timestamp < :date";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, ['date' => $date]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete old logs: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
