<?php

declare(strict_types=1);

/**
 * PWD Records API Routes
 * 
 * These routes handle PWD records management operations (CRUD)
 * PWD records table has complex relationships with multiple other tables
 */

require_once CONTROLLER . 'PWDRecordsController.php';

return function ($app): void {
    $pwdRecordsController = new PwdRecordsController();

    // Get all PWD records with pagination and optional filtering
    $app->get('/v1/pwd-records', function ($request, $response) use ($pwdRecordsController) {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        // Extract filter parameters
        $filters = [];
        $filterableFields = ['quarter', 'year', 'status', 'community_id', 'disability_category_id', 'search'];
        foreach ($filterableFields as $field) {
            if (isset($queryParams[$field]) && $queryParams[$field] !== '') {
                $filters[$field] = $queryParams[$field];
            }
        }

        $result = $pwdRecordsController->listPwdRecords($page, $perPage, $filters);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD record by ID
    $app->get('/v1/pwd-records/{id}', function ($request, $response, $args) use ($pwdRecordsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $pwdRecordsController->getPwdRecordById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new PWD record
    // Complex request with many fields, see schema.sql for complete field list
    $app->post('/v1/pwd-records', function ($request, $response) use ($pwdRecordsController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $pwdRecordsController->createPwdRecord($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update an existing PWD record
    $app->patch('/v1/pwd-records/{id}', function ($request, $response, $args) use ($pwdRecordsController) {
        // Get authenticated user from JWT token or session
        $userId = $request->getAttribute('user_id') ?? 0;

        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Authentication required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $pwdRecordsController->updatePwdRecord($id, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update PWD record status
    // Expects: {"status":"pending|approved|declined"}
    $app->patch('/v1/pwd-records/{id}/status', function ($request, $response, $args) use ($pwdRecordsController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        // Get authenticated user from JWT token or session
        $userId = $data['user_id'];

        if(!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Authentication required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $id = isset($args['id']) ? (int) $args['id'] : 0;
        
        if (empty($data['status'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Status is required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $result = $pwdRecordsController->updatePwdStatus($id, $data['status'], $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete PWD record
    $app->delete('/v1/pwd-records/{id}', function ($request, $response, $args) use ($pwdRecordsController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $data['user_id'];

        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Authentication required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $pwdRecordsController->deletePwdRecord($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD records by quarter and year
    $app->get('/v1/pwd-records/quarterly/{quarter}/{year}', function ($request, $response, $args) use ($pwdRecordsController) {
        $quarter = $args['quarter'] ?? '';
        $year = isset($args['year']) ? (int) $args['year'] : 0;
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        $result = $pwdRecordsController->getRecordsByQuarterAndYear($quarter, $year, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD records by disability category
    $app->get('/v1/pwd-records/category/{categoryId}', function ($request, $response, $args) use ($pwdRecordsController) {
        $categoryId = isset($args['categoryId']) ? (int) $args['categoryId'] : 0;
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        $result = $pwdRecordsController->getRecordsByCategory($categoryId, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get PWD records by community
    $app->get('/v1/pwd-records/community/{communityId}', function ($request, $response, $args) use ($pwdRecordsController) {
        $communityId = isset($args['communityId']) ? (int) $args['communityId'] : 0;
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        $result = $pwdRecordsController->getRecordsByCommunity($communityId, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
