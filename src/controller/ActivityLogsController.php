<?php

declare(strict_types=1);

require_once MODEL . 'ActivityLogs.php';
require_once MODEL . 'Users.php';

/**
 * ActivityLogsController
 *
 * Handles activity logging and retrieval operations
 */
class ActivityLogsController
{
    protected ActivityLogs $logModel;
    protected Users $userModel;

    public function __construct()
    {
        $this->logModel = new ActivityLogs();
        $this->userModel = new Users();
    }

    /**
     * Log a new activity
     * 
     * @param int $userId The user performing the activity
     * @param string $activity Description of the activity
     * @return string JSON response
     */
    public function logActivity(int $userId, string $activity): string
    {
        // Verify user exists
        $user = $this->userModel->getById($userId);
        if (!$user) {
            return json_encode([
                'status' => 'error',
                'message' => "User not found with id {$userId}",
            ], JSON_PRETTY_PRINT);
        }
        
        $logId = $this->logModel->logActivity($userId, $activity);
        
        if ($logId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to log activity: ' . $this->logModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        return json_encode([
            'status' => 'success',
            'message' => 'Activity logged successfully',
            'data' => [
                'log_id' => $logId,
                'user_id' => $userId,
                'activity' => $activity,
                'timestamp' => date('Y-m-d H:i:s')
            ],
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get all activity logs with pagination
     * 
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getAllLogs(int $page = 1, int $perPage = 50): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $logs = $this->logModel->getAll($perPage, $offset);
        $totalLogs = $this->logModel->getCount();
        $totalPages = ceil($totalLogs / $perPage);
        
        return json_encode([
            'status' => 'success',
            'data' => $logs,
            'pagination' => [
                'total_records' => $totalLogs,
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ],
            'message' => empty($logs) ? 'No activity logs found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get activity logs for a specific user
     * 
     * @param int $userId The user ID to filter by
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getUserLogs(int $userId, int $page = 1, int $perPage = 50): string
    {
        // Verify user exists
        $user = $this->userModel->getById($userId);
        if (!$user) {
            return json_encode([
                'status' => 'error',
                'message' => "User not found with id {$userId}",
            ], JSON_PRETTY_PRINT);
        }
        
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $logs = $this->logModel->getByUserId($userId, $perPage, $offset);
        
        return json_encode([
            'status' => 'success',
            'data' => $logs,
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'message' => empty($logs) ? 'No activity logs found for this user' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get activity logs by date range
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getLogsByDateRange(string $startDate, string $endDate, int $page = 1, int $perPage = 50): string
    {
        // Validate date format
        if (!$this->validateDateFormat($startDate) || !$this->validateDateFormat($endDate)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid date format. Use YYYY-MM-DD format.',
            ], JSON_PRETTY_PRINT);
        }
        
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $logs = $this->logModel->getByDateRange($startDate, $endDate, $perPage, $offset);
        
        return json_encode([
            'status' => 'success',
            'data' => $logs,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'message' => empty($logs) ? 'No activity logs found in this date range' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Search activity logs
     * 
     * @param string $searchTerm Text to search for
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function searchLogs(string $searchTerm, int $page = 1, int $perPage = 50): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $logs = $this->logModel->searchLogs($searchTerm, $perPage, $offset);
        
        return json_encode([
            'status' => 'success',
            'data' => $logs,
            'search' => [
                'term' => $searchTerm
            ],
            'message' => empty($logs) ? 'No matching activity logs found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get a specific log entry by ID
     * 
     * @param int $logId The log ID to retrieve
     * @return string JSON response
     */
    public function getLogById(int $logId): string
    {
        $log = $this->logModel->getById($logId);
        
        if (!$log) {
            return json_encode([
                'status' => 'error',
                'message' => "Activity log not found with id {$logId}",
            ], JSON_PRETTY_PRINT);
        }
        
        return json_encode([
            'status' => 'success',
            'data' => $log,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete old activity logs
     * 
     * @param int $days Delete logs older than this many days
     * @return string JSON response
     */
    public function cleanupOldLogs(int $days): string
    {
        if ($days < 30) {
            return json_encode([
                'status' => 'error',
                'message' => 'Cannot delete logs less than 30 days old for audit purposes',
            ], JSON_PRETTY_PRINT);
        }
        
        $deleted = $this->logModel->deleteOlderThan($days);
        
        return json_encode([
            'status' => 'success',
            'message' => "{$deleted} activity logs older than {$days} days deleted successfully",
            'data' => [
                'deleted_count' => $deleted,
                'days_threshold' => $days
            ],
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date Date string to validate
     * @return bool True if valid, false otherwise
     */
    private function validateDateFormat(string $date): bool
    {
        $format = 'Y-m-d';
        $dateTime = \DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }
}