<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtAuthMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

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

        // Get JWT from Authorization header
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
            $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_jwt_secret_should_be_changed';

            // Decode token
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

            if (empty($decoded->user_id)) {
                throw new Exception('Invalid token payload');
            }

            // Check role if restricted
            if (!empty($this->allowedRoles) && !in_array($decoded->role, $this->allowedRoles)) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Access denied. Insufficient permissions.'
                ]));

                return $response
                    ->withStatus(403)
                    ->withHeader('Content-Type', 'application/json');
            }

            // Add user to request attributes
            $userData = [
                'user_id' => $decoded->user_id,
                'role' => $decoded->role,
                'username' => $decoded->username ?? null
            ];

            $request = $request->withAttribute('user', $userData);

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
