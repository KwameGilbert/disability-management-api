<?php
declare(strict_types=1);

/**
 * Disability Categories API Routes
 * 
 * These routes handle disability category management operations (CRUD)
 * Disability categories table structure: category_id, category_name
 */

require_once CONTROLLER . '/DisabilityCategoriesController.php';

return function ($app): void {
    $categoriesController = new DisabilityCategoriesController();

    // Get all disability categories
    $app->get('/v1/disability-categories', function ($request, $response) use ($categoriesController) {
        $result = $categoriesController->listCategories();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get disability category by ID
    $app->get('/v1/disability-categories/{id}', function ($request, $response, $args) use ($categoriesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $categoriesController->getCategoryById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get disability types associated with a category
    $app->get('/v1/disability-categories/{id}/types', function ($request, $response, $args) use ($categoriesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $categoriesController->getCategoryTypes($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new disability category
    // Expects: {"category_name":"..."}
    $app->post('/v1/disability-categories', function ($request, $response) use ($categoriesController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $categoriesController->createCategory($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update disability category by ID
    // Expects: {"category_name":"..."}
    $app->patch('/v1/disability-categories/{id}', function ($request, $response, $args) use ($categoriesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $categoriesController->updateCategory($id, $data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete disability category by ID
    $app->delete('/v1/disability-categories/{id}', function ($request, $response, $args) use ($categoriesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $categoriesController->deleteCategory($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
