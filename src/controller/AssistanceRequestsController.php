<?php

declare(strict_types=1);

require_once MODEL . 'AssistanceRequests.php';
require_once MODEL . 'ActivityLogs.php';

/**
 * AssistanceRequestsController
 *
 * Handles assistance requests CRUD operations and related processes
 */
class AssistanceRequestsController
{
    protected AssistanceRequests $requestModel;
    protected ActivityLogs $logModel;

    public function __construct()
    {
        $this->requestModel = new AssistanceRequests();
        $this->logModel = new ActivityLogs();
    }

    /**
     * List all assistance requests with pagination and optional filtering
     * 
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param array $filters Optional filters to apply
     * @return string JSON response
     */
    public function listAssistanceRequests(int $page = 1, int $perPage = 20, array $filters = []): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $requests = $this->requestModel->getAll($perPage, $offset, $filters);
        $totalRequests = $this->requestModel->getCount($filters);
        $totalPages = ceil($totalRequests / $perPage);
        
        return json_encode([
            'status' => 'success',
            'data' => $requests,
            'pagination' => [
                'total_records' => $totalRequests,
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ],
            'filters' => $filters,
            'message' => empty($requests) ? 'No assistance requests found' : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get assistance request by ID
     * 
     * @param int $id Assistance request ID to retrieve
     * @return string JSON response
     */
    public function getAssistanceRequestById(int $id): string
    {
        $request = $this->requestModel->getById($id);
        
        if (!$request) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance request not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        return json_encode([
            'status' => 'success',
            'data' => $request,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new assistance request
     * 
     * @param array $data Assistance request data
     * @param int $userId ID of user creating the request
     * @return string JSON response
     */
    public function createAssistanceRequest(array $data, int $userId): string
    {
        // Validate required fields
        $requiredFields = ['assistance_type_id', 'beneficiary_id', 'description'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Missing required fields: ' . implode(', ', $missingFields),
            ], JSON_PRETTY_PRINT);
        }
        
        // Set the user ID of the creator as the requester
        $data['requested_by'] = $userId;
        
        // If amount_value_cost is empty but provided, set it to null
        if (isset($data['amount_value_cost']) && $data['amount_value_cost'] === '') {
            $data['amount_value_cost'] = null;
        }
        
        // Validate foreign key relationships
        $validationErrors = $this->requestModel->validateForeignKeys($data);
        
        if (!empty($validationErrors)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Validation errors: ' . implode(', ', $validationErrors),
            ], JSON_PRETTY_PRINT);
        }
        
        // Create the assistance request
        $requestId = $this->requestModel->create($data);
        
        if ($requestId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create assistance request: ' . $this->requestModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        // Log the activity
        $this->logModel->logActivity($userId, "Created new assistance request with ID {$requestId} for beneficiary ID {$data['beneficiary_id']}");
        
        $request = $this->requestModel->getById((int) $requestId);
        
        return json_encode([
            'status' => 'success',
            'message' => 'Assistance request created successfully',
            'data' => $request,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing assistance request
     * 
     * @param int $id Assistance request ID to update
     * @param array $data Assistance request data
     * @param int $userId ID of user updating the request
     * @return string JSON response
     */
    public function updateAssistanceRequest(int $id, array $data, int $userId): string
    {
        // Check if request exists
        $existing = $this->requestModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance request not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        // If amount_value_cost is empty but provided, set it to null
        if (isset($data['amount_value_cost']) && $data['amount_value_cost'] === '') {
            $data['amount_value_cost'] = null;
        }
        
        // Validate foreign key relationships
        $validationErrors = $this->requestModel->validateForeignKeys($data);
        
        if (!empty($validationErrors)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Validation errors: ' . implode(', ', $validationErrors),
            ], JSON_PRETTY_PRINT);
        }
        
        // Update the assistance request
        $updated = $this->requestModel->update($id, $data);
        
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update assistance request: ' . $this->requestModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        // Log the activity
        $this->logModel->logActivity($userId, "Updated assistance request with ID {$id} for beneficiary {$existing['beneficiary_name']}");
        
        $request = $this->requestModel->getById($id);
        
        return json_encode([
            'status' => 'success',
            'message' => 'Assistance request updated successfully',
            'data' => $request,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete an assistance request
     * 
     * @param int $id Assistance request ID to delete
     * @param int $userId ID of user deleting the request
     * @return string JSON response
     */
    public function deleteAssistanceRequest(int $id, int $userId): string
    {
        // Check if request exists
        $existing = $this->requestModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance request not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        // Delete the assistance request
        $deleted = $this->requestModel->delete($id);
        
        if (!$deleted) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to delete assistance request: ' . $this->requestModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        // Log the activity
        $this->logModel->logActivity($userId, "Deleted assistance request with ID {$id} for beneficiary {$existing['beneficiary_name']}");
        
        return json_encode([
            'status' => 'success',
            'message' => 'Assistance request deleted successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update assistance request status
     * 
     * @param int $id Assistance request ID
     * @param string $status New status ('pending','review','ready_to_access','assessed','declined')
     * @param string|null $adminNotes Optional admin review notes
     * @param int $userId ID of user updating the status
     * @return string JSON response
     */
    public function updateRequestStatus(int $id, string $status, ?string $adminNotes, int $userId): string
    {
        // Check if request exists
        $existing = $this->requestModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => "Assistance request not found with id {$id}",
            ], JSON_PRETTY_PRINT);
        }
        
        // Validate status
        $validStatuses = ['pending', 'review', 'ready_to_access', 'assessed', 'declined'];
        if (!in_array($status, $validStatuses)) {
            return json_encode([
                'status' => 'error',
                'message' => "Invalid status. Must be one of: " . implode(', ', $validStatuses),
            ], JSON_PRETTY_PRINT);
        }
        
        // Update the status
        $updated = $this->requestModel->updateStatus($id, $status, $adminNotes);
        
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update assistance request status: ' . $this->requestModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        
        // Log the activity
        $this->logModel->logActivity($userId, "Updated status of assistance request ID {$id} to '{$status}' for beneficiary {$existing['beneficiary_name']}");
        
        $request = $this->requestModel->getById($id);
        
        return json_encode([
            'status' => 'success',
            'message' => "Assistance request status updated to '{$status}'",
            'data' => $request,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get assistance requests by beneficiary (PWD)
     * 
     * @param int $beneficiaryId PWD ID
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getRequestsByBeneficiary(int $beneficiaryId, int $page = 1, int $perPage = 20): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $requests = $this->requestModel->getByBeneficiary($beneficiaryId, $perPage, $offset);
        
        // Get beneficiary details - we could fetch more details if needed
        $beneficiaryName = '';
        if (!empty($requests)) {
            $beneficiaryName = $requests[0]['beneficiary_name'] ?? '';
        }
        
        return json_encode([
            'status' => 'success',
            'data' => $requests,
            'beneficiary' => [
                'id' => $beneficiaryId,
                'name' => $beneficiaryName
            ],
            'message' => empty($requests) ? "No assistance requests found for beneficiary ID {$beneficiaryId}" : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get assistance requests by user who requested them
     * 
     * @param int $userId User ID
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getRequestsByUser(int $userId, int $page = 1, int $perPage = 20): string
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $requests = $this->requestModel->getByRequestedUser($userId, $perPage, $offset);
        
        return json_encode([
            'status' => 'success',
            'data' => $requests,
            'user' => [
                'id' => $userId
            ],
            'message' => empty($requests) ? "No assistance requests found for user ID {$userId}" : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get assistance requests by status
     * 
     * @param string $status Status to filter by
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @return string JSON response
     */
    public function getRequestsByStatus(string $status, int $page = 1, int $perPage = 20): string
    {
        // Validate status
        $validStatuses = ['pending', 'review', 'ready_to_access', 'assessed', 'declined'];
        if (!in_array($status, $validStatuses)) {
            return json_encode([
                'status' => 'error',
                'message' => "Invalid status. Must be one of: " . implode(', ', $validStatuses),
            ], JSON_PRETTY_PRINT);
        }
        
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;
        
        $requests = $this->requestModel->getByStatus($status, $perPage, $offset);
        
        return json_encode([
            'status' => 'success',
            'data' => $requests,
            'status_filter' => $status,
            'message' => empty($requests) ? "No assistance requests found with status '{$status}'" : null,
        ], JSON_PRETTY_PRINT);
    }
}