<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * AssistanceDistribution Model
 *
 * Handles database operations for the assistance_distribution table.
 * Schema: assistance_distribution(dist_id, assistance_type, count)
 */
class AssistanceDistribution
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'assistance_distribution';

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
     * Create or update assistance distribution record
     * 
     * @param array{assistance_type:string, count:int} $data
     * @return int|false Inserted dist_id or false on failure
     */
    public function saveDistribution(array $data): int|false
    {
        try {
            if (!isset($data['assistance_type'], $data['count'])) {
                $this->lastError = 'Missing required fields: assistance_type or count';
                return false;
            }

            // Check if distribution for this type already exists
            $existingSql = "SELECT dist_id FROM {$this->tableName} 
                            WHERE assistance_type = :assistance_type";
            $existingStmt = $this->db->prepare($existingSql);
            $this->executeQuery($existingStmt, [
                'assistance_type' => $data['assistance_type']
            ]);

            $existingRecord = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                // Update existing distribution
                $sql = "UPDATE {$this->tableName} 
                        SET count = :count 
                        WHERE dist_id = :dist_id";
                $stmt = $this->db->prepare($sql);

                if (!$this->executeQuery($stmt, [
                    'count' => $data['count'],
                    'dist_id' => $existingRecord['dist_id']
                ])) {
                    return false;
                }

                return (int)$existingRecord['dist_id'];
            } else {
                // Insert new distribution
                $sql = "INSERT INTO {$this->tableName} 
                        (assistance_type, count) 
                        VALUES 
                        (:assistance_type, :count)";
                $stmt = $this->db->prepare($sql);

                if (!$this->executeQuery($stmt, [
                    'assistance_type' => $data['assistance_type'],
                    'count' => $data['count']
                ])) {
                    return false;
                }

                return (int)$this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            $this->lastError = 'Failed to save assistance distribution: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Increment count for a specific assistance type
     */
    public function incrementCount(string $assistanceType, int $amount = 1): bool
    {
        try {
            // Check if distribution for this type already exists
            $existingSql = "SELECT dist_id, count FROM {$this->tableName} 
                            WHERE assistance_type = :assistance_type";
            $existingStmt = $this->db->prepare($existingSql);
            $this->executeQuery($existingStmt, [
                'assistance_type' => $assistanceType
            ]);

            $existingRecord = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                // Update existing distribution
                $sql = "UPDATE {$this->tableName} 
                        SET count = count + :amount 
                        WHERE dist_id = :dist_id";
                $stmt = $this->db->prepare($sql);

                return $this->executeQuery($stmt, [
                    'amount' => $amount,
                    'dist_id' => $existingRecord['dist_id']
                ]);
            } else {
                // Insert new distribution
                $sql = "INSERT INTO {$this->tableName} 
                        (assistance_type, count) 
                        VALUES 
                        (:assistance_type, :count)";
                $stmt = $this->db->prepare($sql);

                return $this->executeQuery($stmt, [
                    'assistance_type' => $assistanceType,
                    'count' => $amount
                ]);
            }
        } catch (PDOException $e) {
            $this->lastError = 'Failed to increment assistance count: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get distribution for a specific assistance type
     */
    public function getByType(string $assistanceType): ?array
    {
        try {
            $sql = "SELECT dist_id, assistance_type, count 
                    FROM {$this->tableName} 
                    WHERE assistance_type = :assistance_type";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, ['assistance_type' => $assistanceType])) {
                return null;
            }

            $distribution = $stmt->fetch(PDO::FETCH_ASSOC);
            return $distribution ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance distribution: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get all distribution records
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT dist_id, assistance_type, count 
                    FROM {$this->tableName} 
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt)) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get all assistance distributions: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Generate distribution statistics from assistance table
     */
    public function generateFromAssistance(): bool
    {
        try {
            // Begin transaction
            $this->db->beginTransaction();

            // Clear existing distribution data
            $clearSql = "DELETE FROM {$this->tableName}";
            $clearStmt = $this->db->prepare($clearSql);
            if (!$this->executeQuery($clearStmt)) {
                $this->db->rollBack();
                return false;
            }

            // Get distribution data from assistance table
            $sql = "SELECT assistance_type, COUNT(*) as count 
                   FROM assistance 
                   GROUP BY assistance_type";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                $this->db->rollBack();
                return false;
            }

            $distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Insert new distribution data
            foreach ($distributions as $dist) {
                $insertSql = "INSERT INTO {$this->tableName} (assistance_type, count) 
                             VALUES (:assistance_type, :count)";
                $insertStmt = $this->db->prepare($insertSql);
                if (!$this->executeQuery($insertStmt, [
                    'assistance_type' => $dist['assistance_type'],
                    'count' => $dist['count']
                ])) {
                    $this->db->rollBack();
                    return false;
                }
            }

            // Commit transaction
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->lastError = 'Failed to generate assistance distribution: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a distribution record
     */
    public function delete(int $distId): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE dist_id = :dist_id";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, ['dist_id' => $distId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete distribution record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete a distribution record by type
     */
    public function deleteByType(string $assistanceType): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE assistance_type = :assistance_type";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, ['assistance_type' => $assistanceType]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete distribution record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Reset all distribution counts
     */
    public function resetCounts(): bool
    {
        try {
            $sql = "UPDATE {$this->tableName} SET count = 0";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to reset distribution counts: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
