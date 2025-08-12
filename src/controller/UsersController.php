<?php

declare(strict_types=1);

require_once MODEL . 'Users.php';

/**
 * UsersController
 *
 * Handles user CRUD, authentication, and password reset flows.
 * Works with Users model that has 'role' as an ENUM('admin','officer')
 */
class UsersController
{
    protected Users $userModel;

    public function __construct()
    {
        $this->userModel = new Users();
    }

    /**
     * Compatibility wrapper matching prior naming (returns all users)
     */
    public function getProfile(): string
    {
        return $this->listUsers();
    }

    /**
     * List all users
     */
    public function listUsers(): string
    {
        $users = $this->userModel->getAll();
        return json_encode([
            'status' => !empty($users) ? 'success' : 'error',
            'users' => $users,
            'message' => empty($users) ? 'No users found' : null,
        ], JSON_PRETTY_PRINT);
    }

    public function getUserById(int $id): string
    {
        $user = $this->userModel->getById($id);
        return json_encode([
            'status' => $user ? 'success' : 'error',
            'user' => $user,
            'message' => $user ? null : "User not found with id {$id}",
        ], JSON_PRETTY_PRINT);
    }

    public function getUserByEmail(string $email): string
    {
        $user = $this->userModel->getByEmail($email);
        return json_encode([
            'status' => $user ? 'success' : 'error',
            'user' => $user,
            'message' => $user ? null : 'User not found with this email',
        ], JSON_PRETTY_PRINT);
    }

    public function getUserByUsername(string $username): string
    {
        $user = $this->userModel->getByUsername($username);
        return json_encode([
            'status' => $user ? 'success' : 'error',
            'user' => $user,
            'message' => $user ? null : 'User not found with this username',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new user
     * Expected data: role(string), username, email, password, profile_image(optional)
     */
    public function createUser(array $data): string
    {
        $required = ['role', 'username', 'email', 'password'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Missing required fields: ' . implode(', ', $missing),
            ], JSON_PRETTY_PRINT);
        }

        // Validate role is either 'admin' or 'officer'
        if (!in_array($data['role'], ['admin', 'officer'])) {
            return json_encode([
                'status' => 'error',
                'field' => 'role',
                'message' => "Role must be either 'admin' or 'officer'",
            ], JSON_PRETTY_PRINT);
        }

        $violation = $this->checkUniqueConstraints($data, null);
        if ($violation) {
            return json_encode([
                'status' => 'error',
                'field' => $violation['field'],
                'message' => $violation['message'],
            ], JSON_PRETTY_PRINT);
        }

        $userId = $this->userModel->create($data);
        if ($userId === false) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to create user: ' . $this->userModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $user = $this->userModel->getById((int) $userId);
        return json_encode([
            'status' => 'success',
            'user' => $user,
            'message' => 'User created successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing user
     * Allowed fields: role, username, email, profile_image
     */
    public function updateUser(int $id, array $data): string
    {
        $existing = $this->userModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'User not found',
            ], JSON_PRETTY_PRINT);
        }

        // Validate role if it's being updated
        if (isset($data['role']) && !in_array($data['role'], ['admin', 'officer'])) {
            return json_encode([
                'status' => 'error',
                'field' => 'role',
                'message' => "Role must be either 'admin' or 'officer'",
            ], JSON_PRETTY_PRINT);
        }

        $violation = $this->checkUniqueConstraints($data, $id);
        if ($violation) {
            return json_encode([
                'status' => 'error',
                'field' => $violation['field'],
                'message' => $violation['message'],
            ], JSON_PRETTY_PRINT);
        }

        $updated = $this->userModel->update($id, $data);
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'message' => 'Failed to update user: ' . $this->userModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $user = $this->userModel->getById($id);
        return json_encode([
            'status' => 'success',
            'user' => $user,
            'message' => 'User updated successfully',
        ], JSON_PRETTY_PRINT);
    }

    public function deleteUser(int $id): string
    {
        $existing = $this->userModel->getById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'message' => 'User not found',
            ], JSON_PRETTY_PRINT);
        }

        $deleted = $this->userModel->delete($id);
        return json_encode([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'User deleted successfully' : ('Failed to delete user: ' . $this->userModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Login with username/email and password
     */
    public function login(string $usernameOrEmail, string $password): string
    {
        $user = $this->userModel->login($usernameOrEmail, $password);
        return json_encode([
            'status' => $user ? 'success' : 'error',
            'user' => $user,
            'message' => $user ? 'Login successful' : ('Login failed: ' . $this->userModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Request password reset: generate OTP and store with TTL
     */
    public function requestPasswordReset(string $email, int $ttlMinutes = 15): string
    {
        $user = $this->userModel->getByEmail($email);
        if (!$user) {
            return json_encode([
                'status' => 'error',
                'message' => 'User not found with this email',
            ], JSON_PRETTY_PRINT);
        }

        $otp = $this->userModel->generateOtp();
        $expiresAt = date('Y-m-d H:i:s', time() + ($ttlMinutes * 60));
        $ok = $this->userModel->setOtpCode($email, $otp, $expiresAt);

        return json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'OTP generated and stored' : ('Failed to set OTP: ' . $this->userModel->getLastError()),
            'otp' => $ok ? $otp : null, // expose here for development; hide in production
            'expires_at' => $ok ? $expiresAt : null,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Verify OTP returns basic user info if valid
     */
    public function verifyOtp(string $otp): string
    {
        $user = $this->userModel->findByOtpCode($otp);
        return json_encode([
            'status' => $user ? 'success' : 'error',
            'user' => $user,
            'message' => $user ? 'OTP is valid' : 'Invalid or expired OTP',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Reset password via OTP
     */
    public function resetPasswordWithOtp(string $otp, string $newPassword): string
    {
        $user = $this->userModel->findByOtpCode($otp);
        if (!$user) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired OTP',
            ], JSON_PRETTY_PRINT);
        }

        $ok = $this->userModel->updatePassword((int) $user['user_id'], $newPassword);
        if ($ok) {
            $this->userModel->markOtpAsUsed((int) $user['user_id'], $otp);
        }

        return json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Password updated successfully' : ('Failed to update password: ' . $this->userModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update password with confirmation of current password
     */
    public function updatePasswordWithConfirmation(int $userId, string $currentPassword, string $newPassword): string
    {
        $ok = $this->userModel->updatePasswordWithConfirmation($userId, $currentPassword, $newPassword);
        return json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Password updated successfully' : ('Failed to update password: ' . $this->userModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Enforce unique username/email. $currentUserId excludes that user when updating.
     * @return array{field:string,message:string}|null
     */
    private function checkUniqueConstraints(array $data, ?int $currentUserId = null): ?array
    {
        if (!empty($data['username'])) {
            $existing = $this->userModel->getByUsername($data['username']);
            if ($existing && (!isset($existing['user_id']) || (int) $existing['user_id'] !== (int) ($currentUserId ?? -1))) {
                return ['field' => 'username', 'message' => 'Username already in use by another account'];
            }
        }

        if (!empty($data['email'])) {
            $existing = $this->userModel->getByEmail($data['email']);
            if ($existing && (!isset($existing['user_id']) || (int) $existing['user_id'] !== (int) ($currentUserId ?? -1))) {
                return ['field' => 'email', 'message' => 'Email already in use by another account'];
            }
        }

        return null;
    }
}

// Backwards-compatibility alias if older code expects UserController
if (!class_exists('UserController', false)) {
    class_alias('UsersController', 'UserController');
}
