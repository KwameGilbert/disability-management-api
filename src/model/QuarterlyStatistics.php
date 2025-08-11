<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * QuarterlyStatistics Model
 * 
 * Handles data operations for the quarterly_statistics view:
 * Fields: period_id, quarter, year, total_registered_pwd, total_assessed, pending
 */
class QuarterlyStatistics
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $viewName = 'quarterly_statistics';

    /** @var string */
    private string $lastError = '';

    public function __construct()
    {
        try {
            $database = new Database();
            $connection = $database->getConnection();
            if (!$connection) {
                throw new \PDOException('Database connection failed');
            }
            $this->db = $connection;
        } catch (\PDOException $e) {
            $this->lastError = 'Database connection failed: ' . $e->getMessage();
            error_log($this->lastError);
            throw $e;
        }
    }

    /**
     * Get last error message
     * 
     * @return string The last error message
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Execute a prepared statement with error handling
     * 
     * @param \PDOStatement $statement The prepared statement to execute
     * @param array $params Parameters for the statement
     * @return bool True if successful, false otherwise
     */
    protected function executeQuery(\PDOStatement $statement, array $params = []): bool
    {
        try {
            return $statement->execute($params);
        } catch (\PDOException $e) {
            $this->lastError = 'Query execution failed: ' . $e->getMessage();
            error_log($this->lastError . ' - SQL: ' . $statement->queryString);
            return false;
        }
    }

    /**
     * Get all quarterly statistics
     * 
     * @return array The array of statistics by quarter
     */
    public function getAllStatistics(): array
    {
        try {
            $sql = "SELECT * FROM {$this->viewName} ORDER BY year DESC, quarter DESC";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->lastError = 'Failed to get quarterly statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get statistics for a specific quarter and year
     * 
     * @param string $quarter The quarter (Q1, Q2, Q3, Q4)
     * @param int $year The year
     * @return array|null The statistics for the specified period or null if not found
     */
    public function getStatisticsByPeriod(string $quarter, int $year): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->viewName} WHERE quarter = :quarter AND year = :year";
            $stmt = $this->db->prepare($sql);

            $params = [
                ':quarter' => $quarter,
                ':year' => $year
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return null;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : null;
        } catch (\PDOException $e) {
            $this->lastError = 'Failed to get statistics for period: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get statistics grouped by year
     * 
     * @return array The yearly statistics
     */
    public function getStatisticsByYear(): array
    {
        try {
            $sql = "SELECT 
                      year, 
                      SUM(total_registered_pwd) as total_registered_pwd,
                      SUM(total_assessed) as total_assessed,
                      SUM(pending) as pending
                    FROM 
                      {$this->viewName}
                    GROUP BY 
                      year 
                    ORDER BY 
                      year DESC";

            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->lastError = 'Failed to get yearly statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get statistics for the current year
     * 
     * @return array The current year's statistics by quarter
     */
    public function getCurrentYearStatistics(): array
    {
        try {
            $currentYear = date('Y');
            $sql = "SELECT * FROM {$this->viewName} WHERE year = :year ORDER BY quarter";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [':year' => $currentYear])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->lastError = 'Failed to get current year statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get statistics for multiple years (comparative)
     * 
     * @param array $years Array of years to compare
     * @return array Comparative statistics by year
     */
    public function getComparativeStatistics(array $years): array
    {
        try {
            if (empty($years)) {
                $this->lastError = 'No years provided for comparison';
                return [];
            }

            $placeholders = str_repeat('?,', count($years) - 1) . '?';
            $sql = "SELECT 
                      year, 
                      SUM(total_registered_pwd) as total_registered_pwd,
                      SUM(total_assessed) as total_assessed,
                      SUM(pending) as pending
                    FROM 
                      {$this->viewName} 
                    WHERE 
                      year IN ($placeholders)
                    GROUP BY 
                      year 
                    ORDER BY 
                      year";

            $stmt = $this->db->prepare($sql);

            // Bind each year to its placeholder
            foreach ($years as $index => $year) {
                $stmt->bindValue($index + 1, $year, \PDO::PARAM_INT);
            }

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->lastError = 'Failed to get comparative statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}
