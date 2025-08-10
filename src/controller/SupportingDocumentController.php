<?php

declare(strict_types=1);

require_once MODEL . 'SupportingDocument.php';
require_once MODEL . 'ActivityLog.php';

/**
 * SupportingDocumentController
 * 
 * Handles operations related to supporting documents management
 */
class SupportingDocumentController
{
    protected SupportingDocument $documentModel;
    protected ActivityLog $logModel;
    protected string $uploadsDir;

    public function __construct()
    {
        $this->documentModel = new SupportingDocument();
        $this->logModel = new ActivityLog();
        $this->uploadsDir = __DIR__ . '/../uploads/';
    }

    /**
     * Get all supporting documents
     */
    public function index(): string
    {
        $documents = $this->documentModel->getAll();
        $totalCount = count($documents);

        $result = [
            'status' => 'success',
            'message' => null,
            'data' => [
                'documents' => $documents,
                'total' => $totalCount
            ]
        ];

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Get documents by entity type and ID
     */
    public function getByEntity(string $entityType, int $entityId): string
    {
        $documents = $this->documentModel->getByRelatedEntity($entityType, $entityId);

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => [
                'documents' => $documents
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get document by ID
     */
    public function show(int $documentId): string
    {
        $document = $this->documentModel->getById($documentId);

        if (!$document) {
            return json_encode([
                'status' => 'error',
                'message' => 'Document not found'
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'message' => null,
            'data' => $document
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Upload a new document
     */
    public function upload(array $uploadedFiles, array $data, ?int $userId = null): string
    {
        $entityType = $data['entity_type'] ?? '';
        $entityId = (int)($data['entity_id'] ?? 0);
        $documentType = $data['document_type'] ?? '';

        if (empty($uploadedFiles['document']) || empty($entityType) || $entityId === 0 || empty($documentType)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Missing required parameters: document, entity_type, entity_id, or document_type'
            ], JSON_PRETTY_PRINT);
        }

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }

        $uploadedFile = $uploadedFiles['document'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return json_encode([
                'status' => 'error',
                'message' => 'Upload failed with error code ' . $uploadedFile->getError()
            ], JSON_PRETTY_PRINT);
        }

        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($this->uploadsDir . $filename);

        $documentData = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'document_type' => $documentType,
            'file_name' => $uploadedFile->getClientFilename(),
            'file_path' => $filename,
            'mime_type' => $uploadedFile->getClientMediaType(),
            'file_size' => $uploadedFile->getSize(),
            'uploaded_by' => $userId,
            'upload_date' => date('Y-m-d H:i:s')
        ];

        $documentId = $this->documentModel->create($documentData);

        if (!$documentId) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to record document in database: ' . $this->documentModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Uploaded document {$uploadedFile->getClientFilename()} for {$entityType} #{$entityId}");
        }

        $document = $this->documentModel->getById($documentId);

        return json_encode([
            'status' => 'success',
            'message' => 'Document uploaded successfully',
            'data' => $document
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get document download information
     * Note: This method returns download info instead of actual file streaming
     * as that requires direct response manipulation
     */
    public function getDownloadInfo(int $documentId, ?int $userId = null): string
    {
        $document = $this->documentModel->getById($documentId);

        if (!$document) {
            return json_encode([
                'status' => 'error',
                'message' => 'Document not found'
            ], JSON_PRETTY_PRINT);
        }

        $filePath = $this->uploadsDir . $document['file_path'];

        if (!file_exists($filePath)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Document file not found on server'
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Downloaded document {$document['file_name']} (ID: {$documentId})");
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Document download info retrieved successfully',
            'data' => [
                'document_id' => $documentId,
                'file_name' => $document['file_name'],
                'mime_type' => $document['mime_type'],
                'file_size' => $document['file_size'],
                'download_url' => "/api/v1/documents/{$documentId}/download"
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a document
     */
    public function delete(int $documentId, ?int $userId = null): string
    {
        $document = $this->documentModel->getById($documentId);

        if (!$document) {
            return json_encode([
                'status' => 'error',
                'message' => 'Document not found'
            ], JSON_PRETTY_PRINT);
        }

        // Delete the file if it exists
        $filePath = $this->uploadsDir . $document['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete record from database
        $success = $this->documentModel->delete($documentId);

        if (!$success) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete document: ' . $this->documentModel->getLastError()
            ], JSON_PRETTY_PRINT);
        }

        if ($userId) {
            $this->logModel->log($userId, "Deleted document {$document['file_name']} (ID: {$documentId})");
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Document deleted successfully'
        ], JSON_PRETTY_PRINT);
    }
}
