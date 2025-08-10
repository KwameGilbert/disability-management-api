<?php

declare(strict_types=1);

/**
 * SupportingDocumentController
 * 
 * Handles operations related to supporting documents management
 */
class SupportingDocumentController
{
    private SupportingDocument $documentModel;
    private ActivityLog $logModel;
    private $uploadsDir;

    public function __construct(
        SupportingDocument $documentModel,
        ActivityLog $logModel
    ) {
        $this->documentModel = $documentModel;
        $this->logModel = $logModel;
        $this->uploadsDir = __DIR__ . '/../uploads/';
    }

    /**
     * Get all supporting documents
     */
    public function index($request, $response)
    {

        $documents = $this->documentModel->getAll();
        $totalCount = $this->documentModel->getCount();

        $result = [
            'documents' => $documents,
            'total' => $totalCount,
           
        ];

        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get documents by entity type and ID
     */
    public function getByEntity($request, $response, $args)
    {
        $entityType = $args['entity_type'];
        $entityId = (int)$args['entity_id'];

        $documents = $this->documentModel->getByRelatedEntity($entityType, $entityId);

        $response->getBody()->write(json_encode(['documents' => $documents]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get document by ID
     */
    public function show($request, $response, $args)
    {
        $documentId = (int)$args['id'];
        $document = $this->documentModel->getById($documentId);

        if (!$document) {
            $response->getBody()->write(json_encode([
                'error' => 'Document not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($document));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Upload a new document
     */
    public function upload($request, $response)
    {
        $userId = $request->getAttribute('user_id');
        $uploadedFiles = $request->getUploadedFiles();
        $entityType = $request->getParsedBody()['entity_type'] ?? '';
        $entityId = (int)($request->getParsedBody()['entity_id'] ?? 0);
        $documentType = $request->getParsedBody()['document_type'] ?? '';

        if (empty($uploadedFiles['document']) || empty($entityType) || $entityId === 0 || empty($documentType)) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing required parameters: document, entity_type, entity_id, or document_type'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }

        $uploadedFile = $uploadedFiles['document'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode([
                'error' => 'Upload failed with error code ' . $uploadedFile->getError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
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
            $response->getBody()->write(json_encode([
                'error' => 'Failed to record document in database: ' . $this->documentModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

        $this->logModel->log($userId, "Uploaded document {$uploadedFile->getClientFilename()} for {$entityType} #{$entityId}");

        $document = $this->documentModel->getById($documentId);

        $response->getBody()->write(json_encode([
            'message' => 'Document uploaded successfully',
            'document' => $document
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Download a document
     */
    public function download($request, $response, $args)
    {
        $documentId = (int)$args['id'];
        $document = $this->documentModel->getById($documentId);

        if (!$document) {
            $response->getBody()->write(json_encode([
                'error' => 'Document not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $filePath = $this->uploadsDir . $document['file_path'];

        if (!file_exists($filePath)) {
            $response->getBody()->write(json_encode([
                'error' => 'Document file not found on server'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $userId = $request->getAttribute('user_id');
        $this->logModel->log($userId, "Downloaded document {$document['file_name']} (ID: {$documentId})");

        $fileStream = fopen($filePath, 'rb');

        return $response->withHeader('Content-Type', $document['mime_type'])
            ->withHeader('Content-Disposition', 'attachment; filename="' . $document['file_name'] . '"')
            ->withHeader('Content-Length', $document['file_size'])
            ->withBody(new \Slim\Psr7\Stream($fileStream));
    }

    /**
     * Delete a document
     */
    public function delete($request, $response, $args)
    {
        $documentId = (int)$args['id'];
        $document = $this->documentModel->getById($documentId);

        if (!$document) {
            $response->getBody()->write(json_encode([
                'error' => 'Document not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Delete the file if it exists
        $filePath = $this->uploadsDir . $document['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete record from database
        $success = $this->documentModel->delete($documentId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to delete document: ' . $this->documentModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

        $userId = $request->getAttribute('user_id');
        $this->logModel->log($userId, "Deleted document {$document['file_name']} (ID: {$documentId})");

        $response->getBody()->write(json_encode([
            'message' => 'Document deleted successfully'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
