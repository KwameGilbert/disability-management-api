<?php

declare(strict_types=1);

require_once CONTROLLER . 'PWDRecordsController.php';

return function ($app): void {
    $pwdController = new PWDRecordsController();

    // Get all PWD records with pagination
    $app->get('/v1/pwd-records', function ($request, $response) use ($pwdController) {
        $queryParams = $request->getQueryParams();
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : null;
        $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : null;

        $result = $pwdController->index($limit, $offset);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD record by ID
    $app->get('/v1/pwd-records/{id}', function ($request, $response, $args) use ($pwdController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $pwdController->show($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD records by status
    $app->get('/v1/pwd-records/status/{status}', function ($request, $response, $args) use ($pwdController) {
        $status = $args['status'] ?? '';
        $result = $pwdController->getByStatus($status);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD records by community
    $app->get('/v1/pwd-records/community/{community_id}', function ($request, $response, $args) use ($pwdController) {
        $communityId = isset($args['community_id']) ? (int) $args['community_id'] : 0;
        $result = $pwdController->getByCommunity($communityId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Search PWD records
    $app->get('/v1/pwd-records/search/{term}', function ($request, $response, $args) use ($pwdController) {
        $term = $args['term'] ?? '';
        $result = $pwdController->search($term);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new PWD record
    $app->post('/v1/pwd-records', function ($request, $response) use ($pwdController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $pwdController->create($data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update PWD record by ID
    $app->patch('/v1/pwd-records/{id}', function ($request, $response, $args) use ($pwdController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $pwdController->update($id, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update PWD status
    $app->patch('/v1/pwd-records/{id}/status', function ($request, $response, $args) use ($pwdController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $status = $data['status'] ?? '';
        $result = $pwdController->updateStatus($id, $status, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete PWD record by ID
    $app->delete('/v1/pwd-records/{id}', function ($request, $response, $args) use ($pwdController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $userId = $request->getAttribute('user_id');
        $result = $pwdController->delete($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get statistics by quarter and year
    $app->get('/v1/pwd-records/statistics/{quarter}/{year}', function ($request, $response, $args) use ($pwdController) {
        $quarter = $args['quarter'] ?? '';
        $year = isset($args['year']) ? (int) $args['year'] : 0;
        $result = $pwdController->getStatistics($quarter, $year);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
