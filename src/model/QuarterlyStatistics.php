<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * QuarterlyStatistics Model
 *
 * Handles database operations for the quarterly_statistics table.
 * Schema: quarterly_statistics(stat_id, quarter, year, total_registered_pwd, total_assessed, pending)
 */
class QuarterlyStatistics
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'quarterly_statistics';

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
     * Create or update statistics for a quarter
     * 
     * @param array{quarter:string, year:int, total_registered_pwd:int, total_assessed:int, pending:int} $data
     * @return int|false Inserted stat_id or false on failure
     */
    public function saveQuarterStats(array $data): int|false
    {
        try {
            if (!isset($data['quarter'], $data['year'], $data['total_registered_pwd'], $data['total_assessed'], $data['pending'])) {
                $this->lastError = 'Missing required fields in quarterly statistics data';
                return false;
            }

            if (!in_array($data['quarter'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return false;
            }

            // Check if stats for this quarter/year already exist
            $existingSql = "SELECT stat_id FROM {$this->tableName} 
                            WHERE quarter = :quarter AND year = :year";
            $existingStmt = $this->db->prepare($existingSql);
            $this->executeQuery($existingStmt, [
                'quarter' => $data['quarter'],
                'year' => $data['year']
            ]);

            $existingRecord = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                // Update existing stats
                $sql = "UPDATE {$this->tableName} 
                        SET total_registered_pwd = :total_registered_pwd,
                            total_assessed = :total_assessed,
                            pending = :pending 
                        WHERE stat_id = :stat_id";
                $stmt = $this->db->prepare($sql);

                if (!$this->executeQuery($stmt, [
                    'total_registered_pwd' => $data['total_registered_pwd'],
                    'total_assessed' => $data['total_assessed'],
                    'pending' => $data['pending'],
                    'stat_id' => $existingRecord['stat_id']
                ])) {
                    return false;
                }

                return (int)$existingRecord['stat_id'];
            } else {
                // Insert new stats
                $sql = "INSERT INTO {$this->tableName} 
                        (quarter, year, total_registered_pwd, total_assessed, pending) 
                        VALUES 
                        (:quarter, :year, :total_registered_pwd, :total_assessed, :pending)";
                $stmt = $this->db->prepare($sql);

                if (!$this->executeQuery($stmt, [
                    'quarter' => $data['quarter'],
                    'year' => $data['year'],
                    'total_registered_pwd' => $data['total_registered_pwd'],
                    'total_assessed' => $data['total_assessed'],
                    'pending' => $data['pending']
                ])) {
                    return false;
                }

                return (int)$this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            $this->lastError = 'Failed to save quarterly statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get statistics for a specific quarter and year
     */
    public function getByQuarterYear(string $quarter, int $year): ?array
    {
        try {
            if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return null;
            }

            $sql = "SELECT stat_id, quarter, year, total_registered_pwd, total_assessed, pending 
                    FROM {$this->tableName} 
                    WHERE quarter = :quarter AND year = :year";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [
                'quarter' => $quarter,
                'year' => $year
            ])) {
                return null;
            }

            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            return $stats ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get quarterly statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get all statistics for a specific year
     */
    public function getByYear(int $year): array
    {
        try {
            $sql = "SELECT stat_id, quarter, year, total_registered_pwd, total_assessed, pending 
                    FROM {$this->tableName} 
                    WHERE year = :year 
                    ORDER BY quarter";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, ['year' => $year])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get yearly statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get all statistics
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT stat_id, quarter, year, total_registered_pwd, total_assessed, pending 
                    FROM {$this->tableName} 
                    ORDER BY year DESC, quarter";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get all statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Generate statistics from PWD records for a specific quarter and year
     */
    public function generateStatistics(string $quarter, int $year): ?array
    {
        try {
            if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return null;
            }

            // Determine date range for the quarter
            $startDate = $year . '-' . ($quarter == 'Q1' ? '01-01' : ($quarter == 'Q2' ? '04-01' : ($quarter == 'Q3' ? '07-01' : '10-01')));
            $endDate = $year . '-' . ($quarter == 'Q1' ? '03-31' : ($quarter == 'Q2' ? '06-30' : ($quarter == 'Q3' ? '09-30' : '12-31')));

            // Query to get statistics from pwd_records
            $sql = "SELECT 
                      COUNT(*) as total_registered_pwd,
                      SUM(CASE WHEN status = 'approved' OR status = 'disapproved' THEN 1 ELSE 0 END) as total_assessed,
                      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                    FROM pwd_records
                    WHERE quarter = :quarter
                      AND created_at BETWEEN :start_date AND :end_date";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])) {
                return null;
            }

            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stats) {
                return [
                    'quarter' => $quarter,
                    'year' => $year,
                    'total_registered_pwd' => 0,
                    'total_assessed' => 0,
                    'pending' => 0
                ];
            }

            // Save the statistics
            $result = $this->saveQuarterStats([
                'quarter' => $quarter,
                'year' => $year,
                'total_registered_pwd' => (int)$stats['total_registered_pwd'],
                'total_assessed' => (int)$stats['total_assessed'],
                'pending' => (int)$stats['pending']
            ]);

            if (!$result) {
                return null;
            }

            // Return the saved statistics
            return $this->getByQuarterYear($quarter, $year);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to generate statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Delete statistics for a specific quarter and year
     */
    public function delete(string $quarter, int $year): bool
    {
        try {
            if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $this->lastError = 'Invalid quarter value. Must be Q1, Q2, Q3, or Q4';
                return false;
            }

            $sql = "DELETE FROM {$this->tableName} WHERE quarter = :quarter AND year = :year";
            $stmt = $this->db->prepare($sql);

            return $this->executeQuery($stmt, [
                'quarter' => $quarter,
                'year' => $year
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
