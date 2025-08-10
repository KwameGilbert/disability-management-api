<?php

declare(strict_types=1);

require_once CONTROLLER . '/RolesController.php';

return function ($app): void {
    $rolesController = new RolesController();

    // Get all roles
    $app->get('/v1/roles', function ($request, $response) use ($rolesController) {
        $result = $rolesController->listRoles();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get role by ID
    $app->get('/v1/roles/{id}', function ($request, $response, $args) use ($rolesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $rolesController->getRoleById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get role by name
    $app->get('/v1/roles/name/{name}', function ($request, $response, $args) use ($rolesController) {
        $name = $args['name'] ?? '';
        $result = $rolesController->getRoleByName($name);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new role
    $app->post('/v1/roles', function ($request, $response) use ($rolesController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $rolesController->createRole($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update a role by ID
    $app->patch('/v1/roles/{id}', function ($request, $response, $args) use ($rolesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $rolesController->updateRole($id, $data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete a role by ID
    $app->delete('/v1/roles/{id}', function ($request, $response, $args) use ($rolesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $rolesController->deleteRole($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get roles with user count
    $app->get('/v1/roles-with-user-count', function ($request, $response) use ($rolesController) {
        $result = $rolesController->getRolesWithUserCount();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
