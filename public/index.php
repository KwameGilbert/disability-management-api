<?php
define('BASE', __DIR__ . '/../');
define('ROUTE', BASE . 'src/routes/');
define('MODEL', BASE . 'src/model/');
define('CONTROLLER', BASE . 'src/controller/');
define('CONFIG', BASE . 'src/config/');
define('HELPER', BASE . 'src/helper/');
define('MIDDLEWARE', BASE .'/src/middleware/');

require_once BASE . 'vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Slim\Middleware\ContentLengthMiddleware;

require_once MIDDLEWARE . 'RequestResponseLoggerMiddleware.php';
require_once MIDDLEWARE . 'ActivityLoggerMiddleware.php';
require_once HELPER . 'ErrorHandler.php';
require_once HELPER . 'LoggerFactory.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(BASE);
$dotenv->load();

// Create Container using PHP-DI
$container = new Container();

if (class_exists(LoggerFactory::class)) {
    $loggerFactory = new LoggerFactory('App');
    // Set up application logger
    $container->set('logger', $loggerFactory->getLogger());
    // Set up HTTP logger specifically for requests/responses
    $container->set('httpLogger', $loggerFactory->getHttpLogger());
}

// Set the container on AppFactory
AppFactory::setContainer($container);

// Create Slim App instance
$app = AppFactory::create();
// Get environment setting
$environment = $_ENV['APP_ENV'] ?? 'production';
// Add custom error handling middleware
$errorHandler = new ErrorHandler(
    $container->get('logger'),
    $environment
);

// Configure error middleware with custom handler
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: $environment === 'production',
    logErrors: true,
    logErrorDetails: $environment === 'development',
    logger: $container->get('logger')
);

// Set custom error handler
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Add HTTP logger middleware if httpLogger exists
if ($container->has('httpLogger')) {
    $app->add(new RequestResponseLoggerMiddleware($container->get('httpLogger')));
}

// Register activity logger middleware for certain routes
$app->add(new ActivityLoggerMiddleware()); // Make sure auth middleware runs first

// Add CORS middleware FIRST, before anything else
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->withHeader('Access-Control-Max-Age', '86400');
});

// Handle preflight OPTIONS requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->withHeader('Access-Control-Max-Age', '86400');
});

// Add content length middleware
$app->add(new ContentLengthMiddleware());

// Default welcome route
$app->get('/', function ($request, $response) {
    $data = ['message' => 'Welcome to Persons With Disability Management System API', 'status' => 'running'];
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/hello', function ($request, $response, $args) {
    $data = ['message' => 'This is a hello route.'];
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// Include routes
(require_once ROUTE . 'api.php')($app);

// Add Not Found Handler - this must be added after all other routes are defined
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $data = [
        'error' => 'Not Found',
        'message' => 'The requested route does not exist.',
        'status' => 404
    ];
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
});

// Run the application
$app->run();