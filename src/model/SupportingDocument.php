<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * SupportingDocument Model
 *
 * Handles database operations for the supporting_documents table.
 * Schema: supporting_documents(document_id, related_type, related_id, file_name, uploaded_at)
 */
class SupportingDocument
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'supporting_documents';

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
     * Get all documents
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT document_id, related_type, related_id, file_name, uploaded_at FROM {$this->tableName} ORDER BY uploaded_at DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get documents: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Get document by ID
     */
    public function getById(int $documentId): ?array
    {
        try {
            $sql = "SELECT document_id, related_type, related_id, file_name, uploaded_at FROM {$this->tableName} WHERE document_id = :document_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['document_id' => $documentId])) {
                return null;
            }
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            return $document ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get document by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Get documents by related entity
     */
    public function getByRelatedEntity(string $relatedType, int $relatedId): array
    {
        try {
            if (!in_array($relatedType, ['pwd', 'assistance'])) {
                $this->lastError = 'Invalid related type. Must be "pwd" or "assistance"';
                return [];
            }

            $sql = "SELECT document_id, related_type, related_id, file_name, uploaded_at 
                    FROM {$this->tableName} 
                    WHERE related_type = :related_type AND related_id = :related_id
                    ORDER BY uploaded_at DESC";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, [
                'related_type' => $relatedType,
                'related_id' => $relatedId
            ])) {
                return [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get documents by related entity: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    /**
     * Create a new document record
     * @param array{related_type:string, related_id:int, file_name:string} $data
     * @return int|false Inserted document_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (
                !isset($data['related_type'], $data['related_id'], $data['file_name']) ||
                $data['file_name'] === '' ||
                !in_array($data['related_type'], ['pwd', 'assistance'])
            ) {
                $this->lastError = 'Missing or invalid required fields: related_type, related_id, file_name';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (related_type, related_id, file_name) 
                    VALUES (:related_type, :related_id, :file_name)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'related_type' => $data['related_type'],
                'related_id' => $data['related_id'],
                'file_name' => $data['file_name']
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create document record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update document record
     */
    public function update(int $documentId, array $data): bool
    {
        try {
            if (!$this->getById($documentId)) {
                $this->lastError = 'Document not found';
                return false;
            }

            if (isset($data['related_type']) && !in_array($data['related_type'], ['pwd', 'assistance'])) {
                $this->lastError = 'Invalid related type. Must be "pwd" or "assistance"';
                return false;
            }

            $sql = "UPDATE {$this->tableName} 
                    SET related_type = :related_type, related_id = :related_id, file_name = :file_name 
                    WHERE document_id = :document_id";
            $stmt = $this->db->prepare($sql);

            $params = [
                'related_type' => $data['related_type'] ?? null,
                'related_id' => $data['related_id'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'document_id' => $documentId
            ];

            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update document record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete document record
     */
    public function delete(int $documentId): bool
    {
        try {
            if (!$this->getById($documentId)) {
                $this->lastError = 'Document not found';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE document_id = :document_id");
            return $this->executeQuery($stmt, ['document_id' => $documentId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete document record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete all documents related to a specific entity
     */
    public function deleteByRelatedEntity(string $relatedType, int $relatedId): bool
    {
        try {
            if (!in_array($relatedType, ['pwd', 'assistance'])) {
                $this->lastError = 'Invalid related type. Must be "pwd" or "assistance"';
                return false;
            }

            $sql = "DELETE FROM {$this->tableName} WHERE related_type = :related_type AND related_id = :related_id";
            $stmt = $this->db->prepare($sql);

            return $this->executeQuery($stmt, [
                'related_type' => $relatedType,
                'related_id' => $relatedId
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete documents by related entity: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
