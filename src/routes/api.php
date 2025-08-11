<?php
return function ($app): void {
    // Define API routes here. This file is responsible for registering all API endpoints.
    // Get the request URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Map route prefixes to their router files
    $routeMap = [
        '/v1/users' => ROUTE . '/v1/UsersRoute.php',
        '/v1/roles' => ROUTE . '/v1/RolesRoute.php',
        '/v1/communities' => ROUTE . '/v1/CommunitiesRoute.php',
        '/v1/statistics' => ROUTE . '/v1/QuarterlyStatisticsRoute.php',
        '/v1/pwd-records' => ROUTE . '/v1/PWDRecordsRoute.php',
        // Add more routes as needed
    ];

    $loaded = false;
    // Check if the request matches any of our defined prefixes
    foreach ($routeMap as $prefix => $routerFile) {
        if (strpos($requestUri, $prefix) === 0) {
            // Load only the matching router
            if (file_exists($routerFile)) {
                (require_once $routerFile)($app);
                $loaded = true;
            }
        }
    }

    // If no specific router was loaded, load all routers as fallback
    if (!$loaded) {
        foreach ($routeMap as $routerFile) {
            if (file_exists($routerFile)) {
                (require_once $routerFile)($app);
            }
        }
    };
};
