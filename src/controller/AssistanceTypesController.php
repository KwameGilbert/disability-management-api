<?php

declare(strict_types=1);

require_once MODEL . 'AssistanceTypes.php';

/**
 * AssistanceTypesController
 *
 * Handles assistance type CRUD operations
 */
class AssistanceTypesController
{
    protected AssistanceTypes $assistanceTypeModel;

    public function __construct()
    {
        $this->assistanceTypeModel = new AssistanceTypes();
    }

    /**
     * List all assistance types
     */
    public function listAssistanceTypes(): string
    {
        $assistanceTypes = $this->assistanceTypeModel->getAll();
        
        return json_encode([
            'status' => 'success',
            'data' => $assistanceTypes,
            'message' => empty($assistanceTypes) ? 'No assistance types found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get assistance type by ID
     */
    public function getAssistanceTypeById(int $id): string
    {
        $assistanceType = $this->assistanceTypeModel->getById($id);
        
        if (!$assistanceType) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance type not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        return json_encode([
            'status' => 'success',
            'data' => $assistanceType,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new assistance type
     * Expected data: assistance_type_name
     */
    public function createAssistanceType(array $data): string
    {
        // Validate required fields
        if (empty($data['assistance_type_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Assistance type name is required',
            ], JSON_PRETTY_PRINT);
        }
        
        // Check if assistance type name already exists
        $existing = $this->assistanceTypeModel->getByName($data['assistance_type_name']);
        if ($existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'An assistance type with this name already exists',
            ], JSON_PRETTY_PRINT);
        }
        
        // Create the assistance type
        $assistanceTypeId = $this->assistanceTypeModel->create($data);
        
        if ($assistanceTypeId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create assistance type: ' . $this->assistanceTypeModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        $assistanceType = $this->assistanceTypeModel->getById((int) $assistanceTypeId);
        
        return json_encode([
            'status' => 'success',
            'message' => 'Assistance type created successfully',
            'data' => $assistanceType,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing assistance type
     * Expected data: assistance_type_name
     */
    public function updateAssistanceType(int $id, array $data): string
    {
        // Check if assistance type exists
        $existing = $this->assistanceTypeModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance type not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        // Validate required fields
        if (empty($data['assistance_type_name'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Assistance type name is required',
            ], JSON_PRETTY_PRINT);
        }
        
        // Check if the updated name already exists (excluding current assistance type)
        if ($data['assistance_type_name'] !== $existing['assistance_type_name']) {
            $nameExists = $this->assistanceTypeModel->getByName($data['assistance_type_name']);
            if ($nameExists) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'An assistance type with this name already exists',
                ], JSON_PRETTY_PRINT);
            }
        }
        
        // Update the assistance type
        $updated = $this->assistanceTypeModel->update($id, $data);
        
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update assistance type: ' . $this->assistanceTypeModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        $assistanceType = $this->assistanceTypeModel->getById($id);
        
        return json_encode([
            'status' => 'success',
            'message' => 'Assistance type updated successfully',
            'data' => $assistanceType,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete an assistance type
     */
    public function deleteAssistanceType(int $id): string
    {
        // Check if assistance type exists
        $existing = $this->assistanceTypeModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance type not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        // Check if assistance type is in use in PWD records or assistance requests
        $usageCount = $this->assistanceTypeModel->getTotalUsageCount($id);
        if ($usageCount > 0) {
            $pwdCount = $this->assistanceTypeModel->getUsageCountInPwdRecords($id);
            $requestCount = $this->assistanceTypeModel->getUsageCountInAssistanceRequests($id);
            
            return json_encode([
                'status' => 'error',
                'message' => "Cannot delete this assistance type because it is in use",
                'data' => [
                    'total_usage' => $usageCount,
                    'pwd_records_count' => $pwdCount,
                    'assistance_requests_count' => $requestCount
                ]
            ], JSON_PRETTY_PRINT);
        }
        
        // Delete the assistance type
        $deleted = $this->assistanceTypeModel->delete($id);
        
        if (!$deleted) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete assistance type: ' . $this->assistanceTypeModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        return json_encode([
            'status' => 'success',
            'message' => 'Assistance type deleted successfully',
        ], JSON_PRETTY_PRINT);
    }
}