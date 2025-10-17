<?php

declare(strict_types=1);

/**
 * Assistance Requests API Routes
 * 
 * These routes handle assistance requests management operations (CRUD)
 * Assistance requests track the process of providing assistance to PWDs
 */

require_once CONTROLLER . '/AssistanceRequestsController.php';

return function ($app): void {
    $assistanceRequestsController = new AssistanceRequestsController();

    // Get all assistance requests with pagination and optional filtering
    $app->get('/v1/assistance-requests', function ($request, $response) use ($assistanceRequestsController) {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        // Extract filter parameters
        $filters = [];
        $filterableFields = ['status', 'assistance_type_id', 'beneficiary_id', 'requested_by', 'search', 'beneficiary_name'];
        foreach ($filterableFields as $field) {
            if (isset($queryParams[$field]) && $queryParams[$field] !== '') {
                $filters[$field] = $queryParams[$field];
            }
        }

        $result = $assistanceRequestsController->listAssistanceRequests($page, $perPage, $filters);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get assistance request by ID
    $app->get('/v1/assistance-requests/{id}', function ($request, $response, $args) use ($assistanceRequestsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $assistanceRequestsController->getAssistanceRequestById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new assistance request
    // Expects: {"assistance_type_id":1, "beneficiary_id":1, "description":"...", "amount_value_cost":123.45, "user_id":1}
    $app->post('/v1/assistance-requests', function ($request, $response) use ($assistanceRequestsController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'user_id is required in the request body',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $result = $assistanceRequestsController->createAssistanceRequest($data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update an existing assistance request
    $app->patch('/v1/assistance-requests/{id}', function ($request, $response, $args) use ($assistanceRequestsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'user_id is required in the request body',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $result = $assistanceRequestsController->updateAssistanceRequest($id, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update assistance request status
    // Expects: {"status":"pending|review|ready_to_access|assessed|declined", "admin_notes":"...", "user_id":1}
    $app->patch('/v1/assistance-requests/{id}/status', function ($request, $response, $args) use ($assistanceRequestsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'User ID is required in the request body',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        // Check if user is admin - this should be handled by middleware
        require_once CONTROLLER . '/UsersController.php';
        $usersController = new UsersController();
    $user = $usersController->getUserById($id);
        $userRole = $user['role'];
        if ($userRole !== 'admin') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Only administrators can update request status',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        if (empty($data['status'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Status is required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $adminNotes = $data['admin_notes'] ?? null;
        $result = $assistanceRequestsController->updateRequestStatus($id, $data['status'], $adminNotes, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete assistance request
    $app->delete('/v1/assistance-requests/{id}', function ($request, $response, $args) use ($assistanceRequestsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'user_id is required in the request body',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        // Check if user is admin - this should be handled by middleware
        require_once CONTROLLER . '/UsersController.php';
        $usersController = new UsersController();
    $user = $usersController->getUserById($id);
        $userRole = $user['role'];
        if ($userRole !== 'admin') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Only administrators can update request status',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        $result = $assistanceRequestsController->deleteAssistanceRequest($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get assistance requests by beneficiary
    $app->get('/v1/assistance-requests/beneficiary/{beneficiaryId}', function ($request, $response, $args) use ($assistanceRequestsController) {
        $beneficiaryId = isset($args['beneficiaryId']) ? (int) $args['beneficiaryId'] : 0;
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        $result = $assistanceRequestsController->getRequestsByBeneficiary($beneficiaryId, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get assistance requests by requesting user
    $app->get('/v1/assistance-requests/user/{userId}', function ($request, $response, $args) use ($assistanceRequestsController) {
        $requestedBy = isset($args['userId']) ? (int) $args['userId'] : 0;
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        $result = $assistanceRequestsController->getRequestsByUser($requestedBy, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get assistance requests by status
    $app->get('/v1/assistance-requests/status/{status}', function ($request, $response, $args) use ($assistanceRequestsController) {
        $status = $args['status'] ?? '';
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 20;

        $result = $assistanceRequestsController->getRequestsByStatus($status, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

};
