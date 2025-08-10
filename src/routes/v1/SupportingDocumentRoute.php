<?php

declare(strict_types=1);

require_once CONTROLLER . 'SupportingDocumentController.php';

return function ($app): void {
    $documentController = new SupportingDocumentController();

    // Get all supporting documents
    $app->get('/v1/documents', function ($request, $response) use ($documentController) {
        $result = $documentController->index();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get documents by entity type and ID
    $app->get('/v1/documents/{entity_type}/{entity_id}', function ($request, $response, $args) use ($documentController) {
        $entityType = $args['entity_type'] ?? '';
        $entityId = isset($args['entity_id']) ? (int) $args['entity_id'] : 0;
        $result = $documentController->getByEntity($entityType, $entityId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get document by ID
    $app->get('/v1/documents/{id}', function ($request, $response, $args) use ($documentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $documentController->show($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Upload a new document
    $app->post('/v1/documents', function ($request, $response) use ($documentController) {
        $userId = $request->getAttribute('user_id');
        $uploadedFiles = $request->getUploadedFiles();
        $data = $request->getParsedBody() ?? [];
        $result = $documentController->upload($uploadedFiles, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete document by ID
    $app->delete('/v1/documents/{id}', function ($request, $response, $args) use ($documentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $userId = $request->getAttribute('user_id');
        $result = $documentController->delete($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get document download info
    $app->get('/v1/documents/{id}/download-info', function ($request, $response, $args) use ($documentController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $userId = $request->getAttribute('user_id');
        $result = $documentController->getDownloadInfo($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
