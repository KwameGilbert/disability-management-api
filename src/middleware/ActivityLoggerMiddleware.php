<?php

declare(strict_types=1);

require_once MODEL . 'ActivityLogs.php';

/**
 * Activity Logger Middleware
 * 
 * Automatically logs certain API activities
 */
class ActivityLoggerMiddleware
{
    /**
     * Log activity after the route is processed
     * 
     * @param \Slim\Psr7\Request $request
     * @param \Slim\Psr7\Response $response
     * @param callable $next
     * @return \Slim\Psr7\Response
     */
    public function __invoke($request, $response, $next)
    {
        // Process the request first
        $response = $next($request, $response);
        
        // Only log write operations (POST, PUT, PATCH, DELETE)
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->logActivity($request);
        }
        
        return $response;
    }
    
    /**
     * Log the activity from the request
     */
    private function logActivity($request): void
    {
        $userId = $request->getAttribute('user_id') ?? 0;
        
        // Don't log if there's no authenticated user
        if (!$userId) {
            return;
        }
        
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $activity = "{$method} request to {$path}";
        
        // Add more context based on the path
        if (strpos($path, '/v1/users') === 0) {
            $activity = "User management: {$activity}";
        } elseif (strpos($path, '/v1/pwd-records') === 0) {
            $activity = "PWD record: {$activity}";
        } elseif (strpos($path, '/v1/assistance-requests') === 0) {
            $activity = "Assistance request: {$activity}";
        }
        
        // Log the activity asynchronously
        try {
            $logModel = new ActivityLogs();
            $logModel->logActivity($userId, $activity);
        } catch (\Exception $e) {
            // Just suppress errors in the middleware to prevent
            // affecting the main request flow
        }
    }
}