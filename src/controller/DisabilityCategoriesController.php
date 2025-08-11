<?php

declare(strict_types=1);

require_once MODEL . 'DisabilityCategories.php';

/**
 * DisabilityCategoriesController
 *
 * Handles disability category CRUD operations
 */
class DisabilityCategoriesController
{
    protected DisabilityCategories $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new DisabilityCategories();
    }

    /**
     * List all disability categories
     */
    public function listCategories(): string
    {
        $categories = $this->categoryModel->getAll();

        return json_encode([
            'status' => 'success',
            'data' => $categories,
            'message' => empty($categories) ? 'No disability categories found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get disability category by ID
     */
    public function getCategoryById(int $id): string
    {
        $category = $this->categoryModel->getById($id);

        if (!$category) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability category not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'data' => $category,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new disability category
     * Expected data: category_name
     */
    public function createCategory(array $data): string
    {
        // Validate required fields
        if (empty($data['category_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Category name is required',
            ], JSON_PRETTY_PRINT);
        }

        // Check if category name already exists
        $existing = $this->categoryModel->getByName($data['category_name']);
        if ($existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'A disability category with this name already exists',
            ], JSON_PRETTY_PRINT);
        }

        // Create the category
        $categoryId = $this->categoryModel->create($data);

        if ($categoryId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create disability category: ' . $this->categoryModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $category = $this->categoryModel->getById((int) $categoryId);

        return json_encode([
            'status' => 'success',
            'message' => 'Disability category created successfully',
            'data' => $category,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing disability category
     * Expected data: category_name
     */
    public function updateCategory(int $id, array $data): string
    {
        // Check if category exists
        $existing = $this->categoryModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability category not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Validate required fields
        if (empty($data['category_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Category name is required',
            ], JSON_PRETTY_PRINT);
        }

        // Check if the updated name already exists (excluding current category)
        if ($data['category_name'] !== $existing['category_name']) {
            $nameExists = $this->categoryModel->getByName($data['category_name']);
            if ($nameExists) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'A disability category with this name already exists',
                ], JSON_PRETTY_PRINT);
            }
        }

        // Update the category
        $updated = $this->categoryModel->update($id, $data);

        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update disability category: ' . $this->categoryModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $category = $this->categoryModel->getById($id);

        return json_encode([
            'status' => 'success',
            'message' => 'Disability category updated successfully',
            'data' => $category,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a disability category
     */
    public function deleteCategory(int $id): string
    {
        // Check if category exists
        $existing = $this->categoryModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability category not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        // Check for associated disability types
        $types = $this->categoryModel->getAssociatedDisabilityTypes($id);
        if (!empty($types)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Cannot delete category because it has associated disability types. Remove these types first.',
                'data' => [
                    'associated_types' => $types
                ]
            ], JSON_PRETTY_PRINT);
        }

        // Delete the category
        $deleted = $this->categoryModel->delete($id);

        if (!$deleted) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete disability category: ' . $this->categoryModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'message' => 'Disability category deleted successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get disability types associated with this category
     */
    public function getCategoryTypes(int $id): string
    {
        // Check if category exists
        $existing = $this->categoryModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Disability category not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }

        $types = $this->categoryModel->getAssociatedDisabilityTypes($id);

        return json_encode([
            'status' => 'success',
            'data' => $types,
            'message' => empty($types) ? 'No disability types found for this category' : null,
            'category' => $existing
        ], JSON_PRETTY_PRINT);
    }
}
