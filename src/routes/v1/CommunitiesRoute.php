<?php

declare(strict_types=1);

require_once CONTROLLER . '/CommunitiesController.php';

return function ($app): void {
    $communitiesController = new CommunitiesController();

    // Get all communities
    $app->get('/v1/communities', function ($request, $response) use ($communitiesController) {
        $result = $communitiesController->listCommunities();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get community by ID
    $app->get('/v1/communities/{id}', function ($request, $response, $args) use ($communitiesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $communitiesController->getCommunityById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get community by name
    $app->get('/v1/communities/name/{name}', function ($request, $response, $args) use ($communitiesController) {
        $name = $args['name'] ?? '';
        $result = $communitiesController->getCommunityByName($name);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new community
    $app->post('/v1/communities', function ($request, $response) use ($communitiesController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $communitiesController->createCommunity($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update a community by ID
    $app->patch('/v1/communities/{id}', function ($request, $response, $args) use ($communitiesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $communitiesController->updateCommunity($id, $data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete a community by ID
    $app->delete('/v1/communities/{id}', function ($request, $response, $args) use ($communitiesController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $communitiesController->deleteCommunity($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
