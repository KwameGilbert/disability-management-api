<?php
declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Users Model aligned to database/schema.sql
 *
 * Tables used:
 * - users(user_id, role_id, username, email, password_hash, profile_image, created_at, updated_at)
 * - roles(role_id, role_name)
 * - password_resets(reset_id, user_id, otp, expires_at, used)
 */
class Users
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'users';

    /** @var string */
    private string $lastError = '';

    public function __construct()
    {
        try {
            $database = new Database();
            $connection = $database->getConnection();
            if (!$connection) {
                throw new PDOException('Database connection is null');
            }
            $this->db = $connection;
        } catch (PDOException $e) {
            $this->lastError = 'Database connection failed: ' . $e->getMessage();
            error_log($this->lastError);
            throw $e;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Execute a prepared statement with error handling
     */
    protected function executeQuery(\PDOStatement $statement, array $params = []): bool
    {
        try {
            return $statement->execute($params);
        } catch (PDOException $e) {
            $this->lastError = 'Query execution failed: ' . $e->getMessage();
            error_log($this->lastError . ' - SQL: ' . $statement->queryString);
            return false;
        }
    }

    /**
     * List all users with their role names
     */
    public function getAll(): array
    {
        try {
            $sql = "SELECT u.user_id, u.role_id, r.role_name, u.username, u.email, u.profile_image, u.created_at, u.updated_at
                    FROM {$this->tableName} u
                    LEFT JOIN roles r ON r.role_id = u.role_id
                    ORDER BY u.user_id DESC";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt)) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get users: ' . $e->getMessage();
            error_log($this->lastError);
            return [];
        }
    }

    public function getById(int $userId): ?array
    {
        try {
            $sql = "SELECT u.user_id, u.role_id, r.role_name, u.username, u.email, u.profile_image, u.created_at, u.updated_at
                    FROM {$this->tableName} u
                    LEFT JOIN roles r ON r.role_id = u.role_id
                    WHERE u.user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['user_id' => $userId])) {
                return null;
            }
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get user by ID: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    public function getByEmail(string $email): ?array
    {
        try {
            $sql = "SELECT u.user_id, u.role_id, r.role_name, u.username, u.email, u.profile_image, u.created_at, u.updated_at
                    FROM {$this->tableName} u
                    LEFT JOIN roles r ON r.role_id = u.role_id
                    WHERE u.email = :email";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['email' => $email])) {
                return null;
            }
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get user by email: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    public function getByUsername(string $username): ?array
    {
        try {
            $sql = "SELECT u.user_id, u.role_id, r.role_name, u.username, u.email, u.profile_image, u.created_at, u.updated_at
                    FROM {$this->tableName} u
                    LEFT JOIN roles r ON r.role_id = u.role_id
                    WHERE u.username = :username";
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['username' => $username])) {
                return null;
            }
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get user by username: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Create a new user
     * @param array{role_id:int,username:string,email:string,password:string,profile_image?:string} $data
     * @return int|false Inserted user_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            // Validate required fields
            $required = ['role_id', 'username', 'email', 'password'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $this->lastError = "Missing required field: {$field}";
                    return false;
                }
            }

            // Enforce uniqueness of username and email
            if ($this->getByEmail($data['email']) || $this->getByUsername($data['username'])) {
                $this->lastError = 'User already exists with this email or username';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (role_id, username, email, password_hash, profile_image)
                    VALUES (:role_id, :username, :email, :password_hash, :profile_image)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'role_id'       => (int) $data['role_id'],
                'username'      => $data['username'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'profile_image' => $data['profile_image'] ?? null,
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create user: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Update user fields (excluding password)
     */
    public function update(int $userId, array $data): bool
    {
        try {
            if (!$this->getById($userId)) {
                $this->lastError = 'User not found';
                return false;
            }

            $allowedFields = ['role_id', 'username', 'email', 'profile_image'];
            $sets = [];
            $params = ['user_id' => $userId];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    $sets[] = "$key = :$key";
                    $params[$key] = $key === 'role_id' ? (int) $value : $value;
                }
            }

            if (empty($sets)) {
                $this->lastError = 'No valid fields provided for update.';
                return false;
            }

            $sql = 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $sets) . ' WHERE user_id = :user_id';
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt, $params);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update user: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    public function delete(int $userId): bool
    {
        try {
            if (!$this->getById($userId)) {
                $this->lastError = 'User not found';
                return false;
            }
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE user_id = :user_id");
            return $this->executeQuery($stmt, ['user_id' => $userId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to delete user: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Authenticate by username or email and password
     */
    public function login(string $usernameOrEmail, string $password): ?array
    {
        try {
            if ($usernameOrEmail === '' || $password === '') {
                $this->lastError = 'Username/email and password are required';
                return null;
            }

            $stmt = $this->db->prepare("SELECT u.*, r.role_name FROM {$this->tableName} u LEFT JOIN roles r ON r.role_id = u.role_id WHERE u.username = :ue OR u.email = :ue LIMIT 1");
            if (!$this->executeQuery($stmt, ['ue' => $usernameOrEmail])) {
                $this->lastError = 'Database error during login';
                return null;
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $this->lastError = 'User not found';
                return null;
            }

            if (!password_verify($password, (string) $user['password_hash'])) {
                $this->lastError = 'Invalid password';
                return null;
            }

            if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                $this->updatePasswordHash((int) $user['user_id'], $password);
            }

            unset($user['password_hash']);
            return $user;
        } catch (PDOException $e) {
            $this->lastError = 'Login failed: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    private function updatePasswordHash(int $userId, string $password): bool
    {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE {$this->tableName} SET password_hash = :hash WHERE user_id = :user_id");
            return $this->executeQuery($stmt, ['hash' => $newHash, 'user_id' => $userId]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update password hash: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    public function updatePasswordWithConfirmation(int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            if (strlen($newPassword) < 8) {
                $this->lastError = 'Password must be at least 8 characters';
                return false;
            }

            $stmt = $this->db->prepare("SELECT password_hash FROM {$this->tableName} WHERE user_id = :user_id");
            if (!$this->executeQuery($stmt, ['user_id' => $userId])) {
                return false;
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->lastError = 'User not found';
                return false;
            }

            if (!password_verify($currentPassword, (string) $row['password_hash'])) {
                $this->lastError = 'Current password is incorrect';
                return false;
            }

            $stmt = $this->db->prepare("UPDATE {$this->tableName} SET password_hash = :hash WHERE user_id = :user_id");
            return $this->executeQuery($stmt, [
                'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'user_id' => $userId,
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update password: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            if (strlen($newPassword) < 8) {
                $this->lastError = 'Password must be at least 8 characters';
                return false;
            }
            $stmt = $this->db->prepare("UPDATE {$this->tableName} SET password_hash = :hash WHERE user_id = :user_id");
            return $this->executeQuery($stmt, [
                'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'user_id' => $userId,
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to update password: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Password Reset (OTP) flows using password_resets table
     */
    public function setOtpCode(string $email, string $otpCode, string $expiresAt): bool
    {
        try {
            $user = $this->getByEmail($email);
            if (!$user) {
                $this->lastError = 'User not found with this email';
                return false;
            }

            $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, otp, expires_at, used) VALUES (:user_id, :otp, :expires_at, 0)');
            return $this->executeQuery($stmt, [
                'user_id' => (int) $user['user_id'],
                'otp' => $otpCode,
                'expires_at' => $expiresAt,
            ]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to set OTP code: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    public function findByOtpCode(string $otp): ?array
    {
        try {
            $sql = 'SELECT u.user_id, u.role_id, r.role_name, u.username, u.email, u.profile_image
                    FROM password_resets pr
                    INNER JOIN users u ON u.user_id = pr.user_id
                    LEFT JOIN roles r ON r.role_id = u.role_id
                    WHERE pr.otp = :otp AND pr.expires_at > NOW() AND pr.used = 0
                    ORDER BY pr.reset_id DESC LIMIT 1';
            $stmt = $this->db->prepare($sql);
            if (!$this->executeQuery($stmt, ['otp' => $otp])) {
                return null;
            }
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get user by OTP: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Consume an OTP (mark as used) for a user.
     */
    public function markOtpAsUsed(int $userId, string $otp): bool
    {
        try {
            $stmt = $this->db->prepare('UPDATE password_resets SET used = 1 WHERE user_id = :user_id AND otp = :otp AND used = 0');
            return $this->executeQuery($stmt, ['user_id' => $userId, 'otp' => $otp]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to mark OTP as used: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Generate a 6-digit numeric OTP
     */
    public function generateOtp(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}


