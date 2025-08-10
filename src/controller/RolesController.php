<?php

declare(strict_types=1);

require_once MODEL . 'Roles.php';

/**
 * RolesController
 *
 * Handles CRUD operations for roles.
 */
class RolesController
{
    protected Roles $roleModel;

    public function __construct()
    {
        $this->roleModel = new Roles();
    }

    /**
     * List all roles
     */
    public function listRoles(): string
    {
        $roles = $this->roleModel->getAll();
        return json_encode([
            'status' => !empty($roles) ? 'success' : 'error',
            'roles' => $roles,
            'message' => empty($roles) ? 'No roles found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get a role by ID
     */
    public function getRoleById(int $id): string
    {
        $role = $this->roleModel->getById($id);
        return json_encode([
            'status' => $role ? 'success' : 'error',
            'role' => $role,
            'message' => $role ? null : "Role not found with id {$id}",
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get a role by name
     */
    public function getRoleByName(string $name): string
    {
        $role = $this->roleModel->getByName($name);
        return json_encode([
            'status' => $role ? 'success' : 'error',
            'role' => $role,
            'message' => $role ? null : "Role not found with name {$name}",
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): string
    {
        if (empty($data['role_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Missing required field: role_name',
            ], JSON_PRETTY_PRINT);
        }

        $roleId = $this->roleModel->create($data);
        if ($roleId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create role: ' . $this->roleModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $role = $this->roleModel->getById($roleId);
        return json_encode([
            'status' => 'success',
            'role' => $role,
            'message' => 'Role created successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing role
     */
    public function updateRole(int $id, array $data): string
    {
        $existing = $this->roleModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'Role not found',
            ], JSON_PRETTY_PRINT);
        }

        if (empty($data['role_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Role name is required for update',
            ], JSON_PRETTY_PRINT);
        }

        $updated = $this->roleModel->update($id, $data);
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update role: ' . $this->roleModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $role = $this->roleModel->getById($id);
        return json_encode([
            'status' => 'success',
            'role' => $role,
            'message' => 'Role updated successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a role
     */
    public function deleteRole(int $id): string
    {
        $existing = $this->roleModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'Role not found',
            ], JSON_PRETTY_PRINT);
        }

        $deleted = $this->roleModel->delete($id);
        return json_encode([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Role deleted successfully' : ('Failed to delete role: ' . $this->roleModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get roles with user count
     */
    public function getRolesWithUserCount(): string
    {
        $roles = $this->roleModel->getRolesWithUserCount();
        return json_encode([
            'status' => !empty($roles) ? 'success' : 'error',
            'roles' => $roles,
            'message' => empty($roles) ? 'No roles found' : null,
        ], JSON_PRETTY_PRINT);
    }
}

// Backwards-compatibility alias if older code expects RoleController
if (!class_exists('RoleController', false)) {
    class_alias('RolesController', 'RoleController');
}
