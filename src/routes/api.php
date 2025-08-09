<?php
return function ($app): void {
    // Define API routes here. This file is responsible for registering all API endpoints.
    // Get the request URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Map route prefixes to their router files
    $routeMap = [
        // '/v1/auth' => ROUTES . '/v1/AuthRoute.php',
        // '/v1/users' => ROUTES . '/v1/UserRoute.php',
        // '/v1/hotels' => ROUTES . '/v1/HotelRoute.php',
        // 'v1/room-types' => ROUTES . '/v1/RoomTypeRoute.php',
        // '/v1/rooms' => ROUTES . '/v1/RoomRoute.php',
        // '/v1/customers' => ROUTES . '/v1/CustomerRoute.php',
        // '/v1/check-outs' => ROUTES . '/v1/CheckOutRoute.php',
        // '/v1/check-ins' => ROUTES . '/v1/CheckInRoute.php',
        // '/v1/bookings' => ROUTES . '/v1/BookingRoute.php',
        // '/v1/emergency-contacts' => ROUTES . '/v1/EmergencyContactRoute.php',
        // '/v1/payments' => ROUTES . '/v1/PaymentRoute.php',
        // '/v1/payment-methods' => ROUTES . '/v1/PaymentMethodRoute.php',
        // 'v1/settings' => ROUTES . '/v1/SettingsRoute.php',
        // 'v1/dashboard' => ROUTES . '/v1/DashboardRoute.php',
        // 'v1/notifications' => ROUTES . '/v1/NotificationRoute.php'

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
