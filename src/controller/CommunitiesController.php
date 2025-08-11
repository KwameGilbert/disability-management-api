<?php

declare(strict_types=1);

require_once MODEL . 'Communities.php';

/**
 * CommunitiesController
 *
 * Handles community CRUD operations
 */
class CommunitiesController
{
    protected Communities $communityModel;

    public function __construct()
    {
        $this->communityModel = new Communities();
    }

    /**
     * List all communities
     */
    public function listCommunities(): string
    {
        $communities = $this->communityModel->getAll();

        return json_encode([
            'status' => 'success',
            'data' => $communities,
            'message' => empty($communities) ? 'No communities found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get community by ID
     */
    public function getCommunityById(int $id): string
    {
        $community = $this->communityModel->getById($id);

        if (!$community) {
            return json_encode([
                'status' => 'error',
                'message' => "Community not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'data' => $community,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new community
     * Expected data: community_name
     */
    public function createCommunity(array $data): string
    {
        // Validate required fields
        if (empty($data['community_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Community name is required',
            ], JSON_PRETTY_PRINT);
        }

        // Check if community name already exists
        $existing = $this->communityModel->getByName($data['community_name']);
        if ($existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'A community with this name already exists',
            ], JSON_PRETTY_PRINT);
        }

        // Create the community
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
            'message' => 'Community created successfully',
            'data' => $community,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing community
     * Expected data: community_name
     */
    public function updateCommunity(int $id, array $data): string
    {
        // Check if community exists
        $existing = $this->communityModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Community not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Validate required fields
        if (empty($data['community_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Community name is required',
            ], JSON_PRETTY_PRINT);
        }

        // Check if the updated name already exists (excluding current community)
        if ($data['community_name'] !== $existing['community_name']) {
            $nameExists = $this->communityModel->getByName($data['community_name']);
            if ($nameExists) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'A community with this name already exists',
                ], JSON_PRETTY_PRINT);
            }
        }

        // Update the community
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
            'message' => 'Community updated successfully',
            'data' => $community,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a community
     */
    public function deleteCommunity(int $id): string
    {
        // Check if community exists
        $existing = $this->communityModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Community not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Delete the community
        $deleted = $this->communityModel->delete($id);

        if (!$deleted) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete community: ' . $this->communityModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Community deleted successfully',
        ], JSON_PRETTY_PRINT);
    }
}
