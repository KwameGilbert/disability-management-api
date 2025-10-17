    // Assistance Distribution Report (for PDF)
    $app->get('/v1/assistance-types/report', function ($request, $response) use ($assistanceTypesController) {
        $result = $assistanceTypesController->getAssistanceDistributionReport();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
<?php

declare(strict_types=1);

/**
 * Assistance Types API Routes
 * 
 * These routes handle assistance type management operations (CRUD)
 * Assistance types table structure: assistance_type_id, assistance_type_name
 * 
 * This table is referenced by:
 * - pwd_records(assistance_type_needed_id)
 * - assistance_requests(assistance_type_id)
 */

require_once CONTROLLER . '/AssistanceTypesController.php';

return function ($app): void {
    $assistanceTypesController = new AssistanceTypesController();

    // Get all assistance types
    $app->get('/v1/assistance-types', function ($request, $response) use ($assistanceTypesController) {
        $result = $assistanceTypesController->listAssistanceTypes();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get assistance type by ID
    $app->get('/v1/assistance-types/{id}', function ($request, $response, $args) use ($assistanceTypesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $assistanceTypesController->getAssistanceTypeById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new assistance type
    // Expects: {"assistance_type_name":"..."}
    $app->post('/v1/assistance-types', function ($request, $response) use ($assistanceTypesController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $assistanceTypesController->createAssistanceType($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update assistance type by ID
    // Expects: {"assistance_type_name":"..."}
    $app->patch('/v1/assistance-types/{id}', function ($request, $response, $args) use ($assistanceTypesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $assistanceTypesController->updateAssistanceType($id, $data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete assistance type by ID
    $app->delete('/v1/assistance-types/{id}', function ($request, $response, $args) use ($assistanceTypesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $assistanceTypesController->deleteAssistanceType($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
