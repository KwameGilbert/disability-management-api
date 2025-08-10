<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Assistance Model
 *
 * Handles database operations for the assistance table.
 * Schema: assistance(assistance_id, admin_id, assistance_type, date_of_support, beneficiary_id, 
 *          pre_assessment, status, assessment_notes, created_at)
 */
class Assistance
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'assistance';

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
     * Get all assistance records
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT 
                      a.assistance_id, a.admin_id, a.assistance_type, a.date_of_support,
                      a.beneficiary_id, a.pre_assessment, a.status, a.assessment_notes,
                      a.created_at, u.username as admin_name, p.full_name as beneficiary_name
                    FROM {$this->tableName} a
                    JOIN users u ON a.admin_id = u.user_id
                    JOIN pwd_records p ON a.beneficiary_id = p.pwd_id
                    ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance records: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get assistance by ID
     */
    public function getById(int $assistanceId): ?array
    {
        try {
            $sql = "SELECT 
                      a.assistance_id, a.admin_id, a.assistance_type, a.date_of_support,
                      a.beneficiary_id, a.pre_assessment, a.status, a.assessment_notes,
                      a.created_at, u.username as admin_name, p.full_name as beneficiary_name
                    FROM {$this->tableName} a
                    JOIN users u ON a.admin_id = u.user_id
                    JOIN pwd_records p ON a.beneficiary_id = p.pwd_id
                    WHERE a.assistance_id = :assistance_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['assistance_id' => $assistanceId])) {
                return null;
            }
            $assistance = $stmt->fetch(PDO::FETCH_ASSOC);
            return $assistance ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get assistance records by admin ID
     */
    public function getByAdminId(int $adminId): array
    {
        try {
            $sql = "SELECT 
                      a.assistance_id, a.admin_id, a.assistance_type, a.date_of_support,
                      a.beneficiary_id, a.pre_assessment, a.status, a.assessment_notes,
                      a.created_at, u.username as admin_name, p.full_name as beneficiary_name
                    FROM {$this->tableName} a
                    JOIN users u ON a.admin_id = u.user_id
                    JOIN pwd_records p ON a.beneficiary_id = p.pwd_id
                    WHERE a.admin_id = :admin_id
                    ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['admin_id' => $adminId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance records by admin ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get assistance records by beneficiary ID
     */
    public function getByBeneficiaryId(int $beneficiaryId): array
    {
        try {
            $sql = "SELECT 
                      a.assistance_id, a.admin_id, a.assistance_type, a.date_of_support,
                      a.beneficiary_id, a.pre_assessment, a.status, a.assessment_notes,
                      a.created_at, u.username as admin_name, p.full_name as beneficiary_name
                    FROM {$this->tableName} a
                    JOIN users u ON a.admin_id = u.user_id
                    JOIN pwd_records p ON a.beneficiary_id = p.pwd_id
                    WHERE a.beneficiary_id = :beneficiary_id
                    ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['beneficiary_id' => $beneficiaryId])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance records by beneficiary ID: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get assistance records by status
     */
    public function getByStatus(string $status): array
    {
        try {
            if (!in_array($status, ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be "pending", "approved", or "disapproved"';
                return [];
            }

            $sql = "SELECT 
                      a.assistance_id, a.admin_id, a.assistance_type, a.date_of_support,
                      a.beneficiary_id, a.pre_assessment, a.status, a.assessment_notes,
                      a.created_at, u.username as admin_name, p.full_name as beneficiary_name
                    FROM {$this->tableName} a
                    JOIN users u ON a.admin_id = u.user_id
                    JOIN pwd_records p ON a.beneficiary_id = p.pwd_id
                    WHERE a.status = :status
                    ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['status' => $status])) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance records by status: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new assistance record
     * @param array{admin_id:int, assistance_type:string, date_of_support:string, beneficiary_id:int, 
     *              pre_assessment:bool, status:string, assessment_notes:?string} $data
     * @return int|false Inserted assistance_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (
                !isset($data['admin_id'], $data['assistance_type'], $data['date_of_support'], $data['beneficiary_id']) ||
                $data['assistance_type'] === ''
            ) {
                $this->lastError = 'Missing required fields: admin_id, assistance_type, date_of_support, or beneficiary_id';
                return false;
            }

            if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be "pending", "approved", or "disapproved"';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} 
                    (admin_id, assistance_type, date_of_support, beneficiary_id, pre_assessment, status, assessment_notes) 
                    VALUES 
                    (:admin_id, :assistance_type, :date_of_support, :beneficiary_id, :pre_assessment, :status, :assessment_notes)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'admin_id' => $data['admin_id'],
                'assistance_type' => $data['assistance_type'],
                'date_of_support' => $data['date_of_support'],
                'beneficiary_id' => $data['beneficiary_id'],
                'pre_assessment' => isset($data['pre_assessment']) ? (int)$data['pre_assessment'] : 0,
                'status' => $data['status'] ?? 'pending',
                'assessment_notes' => $data['assessment_notes'] ?? null
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create assistance record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update assistance record
     */
    public function update(int $assistanceId, array $data): bool
    {
        try {
            if (!$this->getById($assistanceId)) {
                $this->lastError = 'Assistance record not found';
                return false;
            }

            if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be "pending", "approved", or "disapproved"';
                return false;
            }

            $sql = "UPDATE {$this->tableName} 
                    SET admin_id = :admin_id,
                        assistance_type = :assistance_type,
                        date_of_support = :date_of_support,
                        beneficiary_id = :beneficiary_id,
                        pre_assessment = :pre_assessment,
                        status = :status,
                        assessment_notes = :assessment_notes
                    WHERE assistance_id = :assistance_id";
            $stmt = $this->db->prepare($sql);

            $current = $this->getById($assistanceId);

            $params = [
                'admin_id' => $data['admin_id'] ?? $current['admin_id'],
                'assistance_type' => $data['assistance_type'] ?? $current['assistance_type'],
                'date_of_support' => $data['date_of_support'] ?? $current['date_of_support'],
                'beneficiary_id' => $data['beneficiary_id'] ?? $current['beneficiary_id'],
                'pre_assessment' => isset($data['pre_assessment']) ? (int)$data['pre_assessment'] : $current['pre_assessment'],
                'status' => $data['status'] ?? $current['status'],
                'assessment_notes' => $data['assessment_notes'] ?? $current['assessment_notes'],
                'assistance_id' => $assistanceId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update assistance record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update assistance status
     */
    public function updateStatus(int $assistanceId, string $status, ?string $assessmentNotes = null): bool
    {
        try {
            if (!$this->getById($assistanceId)) {
                $this->lastError = 'Assistance record not found';
                return false;
            }

            if (!in_array($status, ['pending', 'approved', 'disapproved'])) {
                $this->lastError = 'Invalid status value. Must be "pending", "approved", or "disapproved"';
                return false;
            }

            $sql = "UPDATE {$this->tableName} 
                    SET status = :status" .
                ($assessmentNotes !== null ? ", assessment_notes = :assessment_notes" : "") .
                " WHERE assistance_id = :assistance_id";
            $stmt = $this->db->prepare($sql);

            $params = ['status' => $status, 'assistance_id' => $assistanceId];
            if ($assessmentNotes !== null) {
                $params['assessment_notes'] = $assessmentNotes;
            }

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update assistance status: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete assistance record
     */
    public function delete(int $assistanceId): bool
    {
        try {
            if (!$this->getById($assistanceId)) {
                $this->lastError = 'Assistance record not found';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE assistance_id = :assistance_id");
            return $this->executeQuery($stmt, ['assistance_id' => $assistanceId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete assistance record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get assistance statistics
     */
    public function getStatistics(): array
    {
        try {
            $stats = [];

            // Get total count
            $sql = "SELECT COUNT(*) as total FROM {$this->tableName}";
            $stmt = $this->db->prepare($sql);
            if ($this->executeQuery($stmt)) {
                $stats['total'] = (int)$stmt->fetchColumn();
            }

            // Get count by status
            $sql = "SELECT status, COUNT(*) as count FROM {$this->tableName} GROUP BY status";
            $stmt = $this->db->prepare($sql);
            if ($this->executeQuery($stmt)) {
                $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $stats['pending'] = (int)($statusCounts['pending'] ?? 0);
                $stats['approved'] = (int)($statusCounts['approved'] ?? 0);
                $stats['disapproved'] = (int)($statusCounts['disapproved'] ?? 0);
            }

            // Get count by type
            $sql = "SELECT assistance_type, COUNT(*) as count FROM {$this->tableName} GROUP BY assistance_type";
            $stmt = $this->db->prepare($sql);
            if ($this->executeQuery($stmt)) {
                $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }

            return $stats;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get assistance statistics: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }
}
