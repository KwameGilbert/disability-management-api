<?php
declare(strict_types=1);
require_once BASE . 'vendor/autoload.php';
use Slim\Routing\RouteCollectorProxy;

require_once CONTROLLER . "LogsViewerController.php";
require_once MIDDLEWARE . "LogsViewerAuthMiddleware.php";
use LogsViewerController;
use LogsViewerAuthMiddleware;

return function ($app): void {
        // Authentication endpoint
        $app->post('/logs-viewer/auth', LogsViewerController::class . ':authenticate');

        // Main interface endpoint - no authentication required for the UI itself
        $app->get('', LogsViewerController::class . ':renderInterface');

        // API endpoints - require authentication
        $app->get('/logs-viewer/directories', LogsViewerController::class . ':listLogDirectories')->add(new LogsViewerAuthMiddleware());
        $app->get('/logs-viewer/directories/{directory}/files', LogsViewerController::class . ':listLogFiles')->add(new LogsViewerAuthMiddleware());
        $app->get('/logs-viewer/directories/{directory}/files/{file}', LogsViewerController::class . ':viewLogFile')->add(new LogsViewerAuthMiddleware());
 
};
