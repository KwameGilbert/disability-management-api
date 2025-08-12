<?php

declare(strict_types=1);

require_once MODEL . 'DisabilityTypes.php';
require_once MODEL . 'DisabilityCategories.php';

/**
 * DisabilityTypesController
 *
 * Handles disability type CRUD operations
 */
class DisabilityTypesController
{
    protected DisabilityTypes $typeModel;
    protected DisabilityCategories $categoryModel;

    public function __construct()
    {
        $this->typeModel = new DisabilityTypes();
        $this->categoryModel = new DisabilityCategories();
    }

    /**
     * List all disability types
     */
    public function listTypes(): string
    {
        $types = $this->typeModel->getAll();

        return json_encode([
            'status' => 'success',
            'data' => $types,
            'message' => empty($types) ? 'No disability types found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * List disability types by category
     */
    public function listTypesByCategory(int $categoryId): string
    {
        // Verify category exists
        $category = $this->categoryModel->getById($categoryId);
        if (!$category) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability category not found with id {$categoryId}",
            ], JSON_PRETTY_PRINT);
        }

        $types = $this->typeModel->getByCategory($categoryId);

        return json_encode([
            'status' => 'success',
            'data' => $types,
            'message' => empty($types) ? 'No disability types found for this category' : null,
            'category' => $category
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get disability type by ID
     */
    public function getTypeById(int $id): string
    {
        $type = $this->typeModel->getById($id);

        if (!$type) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability type not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'data' => $type,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new disability type
     * Expected data: category_id, type_name
     */
    public function createType(array $data): string
    {
        // Validate required fields
        if (!isset($data['category_id']) || !isset($data['type_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Category ID and type name are required',
            ], JSON_PRETTY_PRINT);
        }

        if (empty($data['type_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Type name cannot be empty',
            ], JSON_PRETTY_PRINT);
        }

        // Validate category exists
        if (!$this->typeModel->categoryExists((int) $data['category_id'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'The specified category does not exist',
            ], JSON_PRETTY_PRINT);
        }

        // Check if type name already exists
        $existing = $this->typeModel->getByName($data['type_name']);
        if ($existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'A disability type with this name already exists',
            ], JSON_PRETTY_PRINT);
        }

        // Create the type
        $typeId = $this->typeModel->create([
            'category_id' => (int) $data['category_id'],
            'type_name' => $data['type_name']
        ]);

        if ($typeId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create disability type: ' . $this->typeModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $type = $this->typeModel->getById((int) $typeId);

        return json_encode([
            'status' => 'success',
            'message' => 'Disability type created successfully',
            'data' => $type,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing disability type
     * Possible data: category_id, type_name
     */
    public function updateType(int $id, array $data): string
    {
        // Check if type exists
        $existing = $this->typeModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability type not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        $updateData = [];

        // Validate and add category_id if provided
        if (isset($data['category_id'])) {
            if (!$this->typeModel->categoryExists((int) $data['category_id'])) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'The specified category does not exist',
                ], JSON_PRETTY_PRINT);
            }
            $updateData['category_id'] = (int) $data['category_id'];
        }

        // Validate and add type_name if provided
        if (isset($data['type_name'])) {
            if (empty($data['type_name'])) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'Type name cannot be empty',
                ], JSON_PRETTY_PRINT);
            }

            // Check for name conflict only if name is changing
            if ($data['type_name'] !== $existing['type_name']) {
                $nameExists = $this->typeModel->getByName($data['type_name']);
                if ($nameExists) {
                    return json_encode([
                        'status' => 'error',
                        'message' => 'A disability type with this name already exists',
                    ], JSON_PRETTY_PRINT);
                }
            }

            $updateData['type_name'] = $data['type_name'];
        }

        // If there's nothing to update, return early
        if (empty($updateData)) {
            return json_encode([
                'status' => 'success',
                'message' => 'No changes required',
                'data' => $existing,
            ], JSON_PRETTY_PRINT);
        }

        // Update the type
        $updated = $this->typeModel->update($id, $updateData);

        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update disability type: ' . $this->typeModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $type = $this->typeModel->getById($id);

        return json_encode([
            'status' => 'success',
            'message' => 'Disability type updated successfully',
            'data' => $type,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a disability type
     */
    public function deleteType(int $id): string
    {
        // Check if type exists
        $existing = $this->typeModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability type not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Check if this disability type is in use
        $usageCount = $this->typeModel->getUsageCount($id);
        if ($usageCount > 0) {
            return json_encode([
                'status' => 'error',
                'message' => "Cannot delete this disability type because it is in use by {$usageCount} PWD records",
            ], JSON_PRETTY_PRINT);
        }

        // Delete the type
        $deleted = $this->typeModel->delete($id);

        if (!$deleted) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete disability type: ' . $this->typeModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Disability type deleted successfully',
        ], JSON_PRETTY_PRINT);
    }
}
