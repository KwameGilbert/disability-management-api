<?php

declare(strict_types=1);

/**
 * Activity Logs API Routes
 * 
 * These routes handle system activity logging and retrieval
 * Activity logs table structure: log_id, user_id, activity, timestamp
 * Has foreign key relationship with users(user_id)
 */

require_once CONTROLLER . 'ActivityLogsController.php';

return function ($app): void {
    $logsController = new ActivityLogsController();

    // Log a new activity
    // Expects: {"activity":"User performed an action"}
    $app->post('/v1/logs', function ($request, $response) use ($logsController) {
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
        
        $data = json_decode((string) $request->getBody(), true) ?? [];
        
        if (empty($data['activity'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Activity description is required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(400);
        }
        
        $result = $logsController->logActivity($userId, $data['activity']);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get all activity logs (paginated)
    $app->get('/v1/logs', function ($request, $response) use ($logsController) {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 50;
        
        $result = $logsController->getAllLogs($page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get activity logs for a specific user
    $app->get('/v1/logs/user/{userId}', function ($request, $response, $args) use ($logsController) {
        $userId = isset($args['userId']) ? (int) $args['userId'] : 0;
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 50;
        
        $result = $logsController->getUserLogs($userId, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get logs by date range
    $app->get('/v1/logs/date-range', function ($request, $response) use ($logsController) {
        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start_date'] ?? '';
        $endDate = $queryParams['end_date'] ?? '';
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 50;
        
        if (empty($startDate) || empty($endDate)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Start date and end date are required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(400);
        }
        
        $result = $logsController->getLogsByDateRange($startDate, $endDate, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Search logs
    $app->get('/v1/logs/search', function ($request, $response) use ($logsController) {
        $queryParams = $request->getQueryParams();
        $searchTerm = $queryParams['q'] ?? '';
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int) $queryParams['per_page'] : 50;
        
        if (empty($searchTerm)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Search term is required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(400);
        }
        
        $result = $logsController->searchLogs($searchTerm, $page, $perPage);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get specific log by ID
    $app->get('/v1/logs/{id}', function ($request, $response, $args) use ($logsController) {
        $logId = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $logsController->getLogById($logId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete old logs (admin only)
    $app->delete('/v1/logs/cleanup/{days}', function ($request, $response, $args) use ($logsController) {
        // Verify user is admin
        $userRole = $request->getAttribute('user_role') ?? '';
        
        if ($userRole !== 'admin') {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Administrator privileges required',
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(403);
        }
        
        $days = isset($args['days']) ? (int) $args['days'] : 0;
        $result = $logsController->cleanupOldLogs($days);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};