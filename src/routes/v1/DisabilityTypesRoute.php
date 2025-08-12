<?php

declare(strict_types=1);

/**
 * Disability Types API Routes
 * 
 * These routes handle disability type management operations (CRUD)
 * Disability types table structure: type_id, category_id, type_name
 * Has foreign key relationship with disability_categories(category_id)
 */

require_once CONTROLLER . '/DisabilityTypesController.php';

return function ($app): void {
    $typesController = new DisabilityTypesController();

    // Get all disability types
    $app->get('/v1/disability-types', function ($request, $response) use ($typesController) {
        $result = $typesController->listTypes();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get disability types by category
    $app->get('/v1/disability-types/category/{categoryId}', function ($request, $response, $args) use ($typesController) {
        $categoryId = isset($args['categoryId']) ? (int) $args['categoryId'] : 0;
        $result = $typesController->listTypesByCategory($categoryId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get disability type by ID
    $app->get('/v1/disability-types/{id}', function ($request, $response, $args) use ($typesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $typesController->getTypeById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new disability type
    // Expects: {"category_id":1, "type_name":"Type Name"}
    $app->post('/v1/disability-types', function ($request, $response) use ($typesController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $typesController->createType($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update disability type by ID
    // Expects: {"category_id":1, "type_name":"Updated Type Name"} (all fields optional)
    $app->patch('/v1/disability-types/{id}', function ($request, $response, $args) use ($typesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $typesController->updateType($id, $data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete disability type by ID
    $app->delete('/v1/disability-types/{id}', function ($request, $response, $args) use ($typesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $typesController->deleteType($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};