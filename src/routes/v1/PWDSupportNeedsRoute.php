<?php

declare(strict_types=1);

require_once CONTROLLER . 'PWDSupportNeedsController.php';

return function ($app): void {
    $supportNeedsController = new PWDSupportNeedsController();

    // Get all support needs for a specific PWD
    $app->get('/v1/pwd/{pwd_id}/support-needs', function ($request, $response, $args) use ($supportNeedsController) {
        $pwdId = isset($args['pwd_id']) ? (int) $args['pwd_id'] : 0;
        $result = $supportNeedsController->getByPWD($pwdId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get support need by ID
    $app->get('/v1/pwd-support-needs/{id}', function ($request, $response, $args) use ($supportNeedsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $supportNeedsController->show($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new support need
    $app->post('/v1/pwd-support-needs', function ($request, $response) use ($supportNeedsController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $supportNeedsController->create($data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update support need by ID
    $app->patch('/v1/pwd-support-needs/{id}', function ($request, $response, $args) use ($supportNeedsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $supportNeedsController->update($id, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete support need by ID
    $app->delete('/v1/pwd-support-needs/{id}', function ($request, $response, $args) use ($supportNeedsController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $userId = $request->getAttribute('user_id');
        $result = $supportNeedsController->delete($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
