<?php

declare(strict_types=1);

use App\Controller\LogsViewerController;
use App\Middleware\LogsViewerAuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

return function ($app): void {
    $app->group('/logs-viewer', function (RouteCollectorProxy $group) {
        // Authentication endpoint
        $group->post('/auth', LogsViewerController::class . ':authenticate');

        // Main interface endpoint - no authentication required for the UI itself
        $group->get('', LogsViewerController::class . ':renderInterface');

        // API endpoints - require authentication
        $group->get('/directories', LogsViewerController::class . ':listLogDirectories')->add(new LogsViewerAuthMiddleware());
        $group->get('/directories/{directory}/files', LogsViewerController::class . ':listLogFiles')->add(new LogsViewerAuthMiddleware());
        $group->get('/directories/{directory}/files/{file}', LogsViewerController::class . ':viewLogFile')->add(new LogsViewerAuthMiddleware());
    });
};
