<?php
// declare(strict_types=0);
require_once BASE . 'vendor/autoload.php';
use App\Middleware\LogsViewerAuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

require_once CONTROLLER . "LogsViewerController.php";
require_once MIDDLEWARE . "LogsViewerAuthMiddleware.php";
$logsAuth = new LogsViewerAuthMiddleware();

return function ($app): void {
        // Authentication endpoint
        $app->post('/logs-viewer/auth', LogsViewerController::class . ':authenticate');

        // Main interface endpoint - no authentication required for the UI itself
        $app->get('', LogsViewerController::class . ':renderInterface');

        // API endpoints - require authentication
        $app->get('/logs-viewer/directories', LogsViewerController::class . ':listLogDirectories')->add($logsAuth);
        $app->get('/logs-viewer/directories/{directory}/files', LogsViewerController::class . ':listLogFiles')->add($logsAuth);
        $app->get('/logs-viewer/directories/{directory}/files/{file}', LogsViewerController::class . ':viewLogFile')->add($logsAuth);
};
