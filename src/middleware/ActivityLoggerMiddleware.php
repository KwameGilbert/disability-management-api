<?php

declare(strict_types=1);

require_once MODEL . 'ActivityLogs.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Activity Logger Middleware
 * 
 * Automatically logs certain API activities
 * Implements PSR-15 middleware interface
 */
class ActivityLoggerMiddleware implements MiddlewareInterface
{
    /**
     * Process a server request and return a response
     *
     * @param Request $request
     * @param RequestHandler $handler
     * 
     * @return Response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Process the request first
        $response = $handler->handle($request);

        // Only log write operations (POST, PUT, PATCH, DELETE)
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->logActivity($request);
        }

        return $response;
    }

    /**
     * Log the activity from the request
     * 
     * @param Request $request PSR-7 request
     */
    private function logActivity(Request $request): void
    {
        // In Slim 4, the user data is stored in a 'user' attribute as an array
        $user = $request->getAttribute('user');
        $userId = $user['user_id'] ?? 0;

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
        } elseif (strpos($path, '/v1/statistics') === 0) {
            $activity = "Statistics: {$activity}";
        }

        // Log the activity asynchronously
        try {
            $logModel = new ActivityLogs();
            $logModel->logActivity($userId, $activity);
        } catch (\Exception $e) {
            // Just suppress errors in the middleware to prevent
            // affecting the main request flow
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
