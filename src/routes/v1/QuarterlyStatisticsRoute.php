<?php
declare(strict_types=1);

    // Quarterly Registration Report for current year (for PDF)
    $app->get('/v1/quarterly-statistics/report', function ($request, $response) use ($statisticsController) {
        $result = $statisticsController->getQuarterlyRegistrationReport();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

/**
 * Quarterly Statistics API Routes
 * 
 * These routes handle statistical data from the quarterly_statistics view
 * View structure: period_id, quarter, year, total_registered_pwd, total_assessed, pending
 */

require_once CONTROLLER . 'QuarterlyStatisticsController.php';

return function ($app): void {
    $statisticsController = new QuarterlyStatisticsController();

    // Get all statistics
    $app->get('/v1/statistics', function ($request, $response) use ($statisticsController) {
        $result = $statisticsController->getAllStatistics();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get statistics grouped by year (this route must come before the next one to avoid conflicts)
    $app->get('/v1/statistics/yearly', function ($request, $response) use ($statisticsController) {
        $result = $statisticsController->getStatisticsByYear();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get current year statistics
    $app->get('/v1/statistics/current-year', function ($request, $response) use ($statisticsController) {
        $result = $statisticsController->getCurrentYearStatistics();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get comparative statistics for multiple years
    $app->get('/v1/statistics/compare', function ($request, $response, $args) use ($statisticsController) {
        $params = $request->getQueryParams();
        $yearsParam = $params['years'] ?? '';
        $userId = isset($request->getAttribute('user')['user_id']) ? $request->getAttribute('user')['user_id'] : 0;
        $result = $statisticsController->getComparativeStatistics($yearsParam, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get statistics by specific quarter and year (this route should be last to avoid conflicts)
    $app->get('/v1/statistics/{quarter}/{year}', function ($request, $response, $args) use ($statisticsController) {
        $quarter = $args['quarter'] ?? '';
        $year = $args['year'] ?? '';
        $result = $statisticsController->getStatisticsByPeriod($quarter, $year);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
