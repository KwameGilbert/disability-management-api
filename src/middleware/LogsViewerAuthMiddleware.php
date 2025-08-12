<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Exception;

class LogsViewerAuthMiddleware
{
    /**
     * Middleware invokable class
     *
     * @param Request $request PSR-7 request
     * @param RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = new Response();

        // Get token from Authorization header
        $header = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($header)) {
            if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
                $token = $matches[1];
            }
        }

        if ($token === null) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Authentication token required'
            ]));

            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            // In a real application, you would validate this token against stored tokens
            // For this simple implementation, we just check if it's a valid token format
            if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
                throw new Exception('Invalid token format');
            }

            // Call next middleware or route handler
            return $handler->handle($request);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ]));

            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
