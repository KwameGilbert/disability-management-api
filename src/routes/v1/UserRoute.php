<?php
declare(strict_types=1);

require_once CONTROLLER . '/UsersController.php';

return function ($app): void {
    $userController = new UsersController();

    // Get all users
    $app->get('/v1/users', function ($request, $response) use ($userController) {
        $result = $userController->getProfile();
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get user by ID
    $app->get('/v1/users/{id}', function ($request, $response, $args) use ($userController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $userController->getUserById($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get user by email
    $app->get('/v1/users/email/{email}', function ($request, $response, $args) use ($userController) {
        $email = $args['email'] ?? '';
        $result = $userController->getUserByEmail($email);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Get user by username
    $app->get('/v1/users/username/{username}', function ($request, $response, $args) use ($userController) {
        $username = $args['username'] ?? '';
        $result = $userController->getUserByUsername($username);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Create a new user
    $app->post('/v1/users', function ($request, $response) use ($userController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $userController->createUser($data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update user by ID
    $app->patch('/v1/users/{id}', function ($request, $response, $args) use ($userController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $result = $userController->updateUser($id, $data);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Delete user by ID
    $app->delete('/v1/users/{id}', function ($request, $response, $args) use ($userController) {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $userController->deleteUser($id);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Login
    $app->post('/v1/users/login', function ($request, $response) use ($userController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $usernameOrEmail = (string) ($data['username'] ?? $data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $result = $userController->login($usernameOrEmail, $password);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Request password reset (generate OTP)
    $app->post('/v1/users/password/request-reset', function ($request, $response) use ($userController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $email = (string) ($data['email'] ?? '');
        $ttl = isset($data['ttl_minutes']) ? (int) $data['ttl_minutes'] : 15;
        $result = $userController->requestPasswordReset($email, $ttl);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Verify OTP
    $app->post('/v1/users/password/verify-otp', function ($request, $response) use ($userController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $otp = (string) ($data['otp'] ?? '');
        $result = $userController->verifyOtp($otp);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Reset password with OTP
    $app->post('/v1/users/password/reset', function ($request, $response) use ($userController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $otp = (string) ($data['otp'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $result = $userController->resetPasswordWithOtp($otp, $newPassword);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Update password with current password confirmation
    $app->post('/v1/users/password/update', function ($request, $response) use ($userController) {
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $userId = (int) ($data['user_id'] ?? 0);
        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $result = $userController->updatePasswordWithConfirmation($userId, $currentPassword, $newPassword);
        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', 'application/json');
    });
};


