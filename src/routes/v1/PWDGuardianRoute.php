<?php
declare(strict_types=1);

require_once CONTROLLER . 'PWDGuardianController.php';

return function ($app): void {
    $guardianController = new PWDGuardianController();

    // Get all guardians for a specific PWD
    $app->get('/v1/pwd/{pwd_id}/guardians', function ($request, $response, $args) use ($guardianController) {
        $pwdId = isset($args['pwd_id']) ? (int) $args['pwd_id'] : 0;
        $result = $guardianController->getByPWD($pwdId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get guardian by ID
    $app->get('/v1/pwd-guardians/{id}', function ($request, $response, $args) use ($guardianController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $guardianController->show($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new guardian
    $app->post('/v1/pwd-guardians', function ($request, $response) use ($guardianController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $guardianController->create($data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update guardian by ID
    $app->patch('/v1/pwd-guardians/{id}', function ($request, $response, $args) use ($guardianController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = $request->getAttribute('user_id');
        $result = $guardianController->update($id, $data, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete guardian by ID
    $app->delete('/v1/pwd-guardians/{id}', function ($request, $response, $args) use ($guardianController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $userId = $request->getAttribute('user_id');
        $result = $guardianController->delete($id, $userId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // Get guardian statistics
    $app->get('/v1/pwd-guardians/statistics', function ($request, $response) use ($guardianController) {
        $queryParams = $request->getQueryParams();
        $communityId = isset($queryParams['community_id']) ? (int) $queryParams['community_id'] : null;
        $result = $guardianController->getStatistics($communityId);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
