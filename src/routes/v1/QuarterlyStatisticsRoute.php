<?php

declare(strict_types=1);

/**
 * Quarterly Statistics API Routes
 * 
 * These routes handle statistical data from the quarterly_statistics view
 * View structure: period_id, quarter, year, total_registered_pwd, total_assessed, pending
 */

require_once CONTROLLER . 'QuarterlyStatisticsController.php';
require_once MODEL . 'QuarterlyStatistics.php';
require_once MODEL . 'ActivityLogs.php';

return function ($app): void {
    // Get Database connection
    $db = require_once CONFIG . 'Database.php';

    // Initialize models
    $quarterlyStatsModel = new App\Model\QuarterlyStatistics($db);
    $activityLogsModel = new App\Model\ActivityLogs($db);

    // Initialize controller with models
    $statisticsController = new App\Controller\QuarterlyStatisticsController(
        $quarterlyStatsModel,
        $activityLogsModel
    );

    // Get all statistics
    $app->get('/v1/statistics', function ($request, $response) use ($statisticsController) {
        return $statisticsController->getAllStatistics($request, $response);
    });

    // Get statistics by specific quarter and year
    $app->get('/v1/statistics/{quarter}/{year}', function ($request, $response, $args) use ($statisticsController) {
        return $statisticsController->getStatisticsByPeriod($request, $response, $args);
    });

    // Get statistics grouped by year
    $app->get('/v1/statistics/yearly', function ($request, $response) use ($statisticsController) {
        return $statisticsController->getStatisticsByYear($request, $response);
    });

    // Get current year statistics
    $app->get('/v1/statistics/current-year', function ($request, $response) use ($statisticsController) {
        return $statisticsController->getCurrentYearStatistics($request, $response);
    });

    // Get comparative statistics for multiple years
    $app->get('/v1/statistics/compare', function ($request, $response) use ($statisticsController) {
        return $statisticsController->getComparativeStatistics($request, $response);
    });
};
