<?php

declare(strict_types=1);

require_once CONTROLLER . 'PWDEducationController.php';

return function ($app): void {
    $educationController = new PWDEducationController();

    // Get all education records for a specific PWD
    $app->get('/v1/pwd/{pwd_id}/education', function ($request, $response, $args) use ($educationController) {
        $pwdId = isset($args['pwd_id']) ? (int) $args['pwd_id'] : 0;
        $result = $educationController->getByPWD($pwdId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get education record by ID
    $app->get('/v1/pwd-education/{id}', function ($request, $response, $args) use ($educationController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $educationController->show($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new education record
    $app->post('/v1/pwd-education', function ($request, $response) use ($educationController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $educationController->create($data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update education record by ID
    $app->patch('/v1/pwd-education/{id}', function ($request, $response, $args) use ($educationController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $educationController->update($id, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete education record by ID
    $app->delete('/v1/pwd-education/{id}', function ($request, $response, $args) use ($educationController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $userId = $request->getAttribute('user_id');
        $result = $educationController->delete($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
