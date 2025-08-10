<?php

declare(strict_types=1);

/**
 * PWDSupportNeedsController
 * 
 * Handles operations related to PWD support needs
 */
class PWDSupportNeedsController
{
    private PWDSupportNeeds $supportNeedsModel;
    private PWDRecords $pwdModel;
    private ActivityLog $logModel;

    public function __construct(
        PWDSupportNeeds $supportNeedsModel,
        PWDRecords $pwdModel,
        ActivityLog $logModel
    ) {
        $this->supportNeedsModel = $supportNeedsModel;
        $this->pwdModel = $pwdModel;
        $this->logModel = $logModel;
    }

    /**
     * Get support needs for a specific PWD
     */
    public function getByPWD($request, $response, $args)
    {
        $pwdId = (int)$args['pwd_id'];

        // Check if PWD exists
        $pwd = $this->pwdModel->getById($pwdId);
        if (!$pwd) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $supportNeeds = $this->supportNeedsModel->getByPWDId($pwdId);

        $response->getBody()->write(json_encode([
            'pwd' => $pwd,
            'support_needs' => $supportNeeds
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get a specific support need
     */
    public function show($request, $response, $args)
    {
        $needId = (int)$args['id'];
        $need = $this->supportNeedsModel->getById($needId);

        if (!$need) {
            $response->getBody()->write(json_encode([
                'error' => 'Support need not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($need));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Create a new support need
     */
    public function create($request, $response)
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        if (!isset($data['pwd_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD ID is required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // Check if PWD exists
        $pwd = $this->pwdModel->getById($data['pwd_id']);
        if (!$pwd) {
            $response->getBody()->write(json_encode([
                'error' => 'PWD record not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $needId = $this->supportNeedsModel->create($data);

        if (!$needId) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to create support need: ' . $this->supportNeedsModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Added support need for PWD #{$data['pwd_id']}");

        $need = $this->supportNeedsModel->getById($needId);

        $response->getBody()->write(json_encode([
            'message' => 'Support need created successfully',
            'support_need' => $need
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Update an existing support need
     */
    public function update($request, $response, $args)
    {
        $needId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Check if support need exists
        $existingNeed = $this->supportNeedsModel->getById($needId);
        if (!$existingNeed) {
            $response->getBody()->write(json_encode([
                'error' => 'Support need not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // If PWD ID is being changed, check if the new PWD exists
        if (isset($data['pwd_id']) && $data['pwd_id'] != $existingNeed['pwd_id']) {
            $pwd = $this->pwdModel->getById($data['pwd_id']);
            if (!$pwd) {
                $response->getBody()->write(json_encode([
                    'error' => 'PWD record not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
        }

        $success = $this->supportNeedsModel->update($needId, $data);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to update support need: ' . $this->supportNeedsModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Updated support need #{$needId}");

        $need = $this->supportNeedsModel->getById($needId);

        $response->getBody()->write(json_encode([
            'message' => 'Support need updated successfully',
            'support_need' => $need
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Delete a support need
     */
    public function delete($request, $response, $args)
    {
        $needId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        // Check if support need exists
        $existingNeed = $this->supportNeedsModel->getById($needId);
        if (!$existingNeed) {
            $response->getBody()->write(json_encode([
                'error' => 'Support need not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $success = $this->supportNeedsModel->delete($needId);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to delete support need: ' . $this->supportNeedsModel->getLastError()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logModel->log($userId, "Deleted support need #{$needId}");

        $response->getBody()->write(json_encode([
            'message' => 'Support need deleted successfully'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Get statistics on common support needs
     */
    public function getStatistics($request, $response)
    {
        $queryParams = $request->getQueryParams();
        $communityId = isset($queryParams['community_id']) ? (int)$queryParams['community_id'] : null;

        if ($communityId) {
            $stats = $this->supportNeedsModel->getStatsByCommunity($communityId);
        } else {
            $stats = $this->supportNeedsModel->getStatsOverall();
        }

        $response->getBody()->write(json_encode($stats));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
