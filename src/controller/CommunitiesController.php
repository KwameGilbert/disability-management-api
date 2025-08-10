<?php

declare(strict_types=1);

require_once MODEL . 'Communities.php';

class CommunitiesController
{
    protected Communities $communityModel;

    public function __construct()
    {
        $this->communityModel = new Communities();
    }

    public function listCommunities(): string
    {
        $communities = $this->communityModel->getAll();
        return json_encode([
            'status' => !empty($communities) ? 'success' : 'error',
            'communities' => $communities,
            'message' => empty($communities) ? 'No communities found' : null,
        ], JSON_PRETTY_PRINT);
    }

    public function getCommunityById(int $id): string
    {
        $community = $this->communityModel->getById($id);
        return json_encode([
            'status' => $community ? 'success' : 'error',
            'community' => $community,
            'message' => $community ? null : "Community not found with id {$id}",
        ], JSON_PRETTY_PRINT);
    }

    public function getCommunityByName(string $name): string
    {
        $community = $this->communityModel->getByName($name);
        return json_encode([
            'status' => $community ? 'success' : 'error',
            'community' => $community,
            'message' => $community ? null : 'Community not found with this name',
        ], JSON_PRETTY_PRINT);
    }

    public function createCommunity(array $data): string
    {
        if (empty($data['community_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Missing required field: community_name',
            ], JSON_PRETTY_PRINT);
        }

        if ($this->communityModel->getByName($data['community_name'])) {
            return json_encode([
                'status' => 'error',
                'field' => 'community_name',
                'message' => 'Community name already exists',
            ], JSON_PRETTY_PRINT);
        }

        $communityId = $this->communityModel->create($data);
        if ($communityId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create community: ' . $this->communityModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $community = $this->communityModel->getById((int) $communityId);
        return json_encode([
            'status' => 'success',
            'community' => $community,
            'message' => 'Community created successfully',
        ], JSON_PRETTY_PRINT);
    }

    public function updateCommunity(int $id, array $data): string
    {
        $existing = $this->communityModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'Community not found',
            ], JSON_PRETTY_PRINT);
        }

        if (!empty($data['community_name'])) {
            $byName = $this->communityModel->getByName($data['community_name']);
            if ($byName && (int) $byName['community_id'] !== $id) {
                return json_encode([
                    'status' => 'error',
                    'field' => 'community_name',
                    'message' => 'Community name already exists',
                ], JSON_PRETTY_PRINT);
            }
        }

        $updated = $this->communityModel->update($id, $data);
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update community: ' . $this->communityModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $community = $this->communityModel->getById($id);
        return json_encode([
            'status' => 'success',
            'community' => $community,
            'message' => 'Community updated successfully',
        ], JSON_PRETTY_PRINT);
    }

    public function deleteCommunity(int $id): string
    {
        $existing = $this->communityModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'Community not found',
            ], JSON_PRETTY_PRINT);
        }

        $deleted = $this->communityModel->delete($id);
        return json_encode([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Community deleted successfully' : ('Failed to delete community: ' . $this->communityModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }
}
