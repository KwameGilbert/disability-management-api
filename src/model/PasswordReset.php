<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * PasswordReset Model
 *
 * Handles database operations for the password_resets table.
 * Schema: password_resets(reset_id, user_id, otp, expires_at, used)
 */
class PasswordReset
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'password_resets';

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
     * Create a new password reset record
     * 
     * @param array{user_id:int, otp:string, expires_at:string} $data
     * @return int|false Inserted reset_id or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            if (!isset($data['user_id'], $data['otp'], $data['expires_at'])) {
                $this->lastError = 'Missing required fields: user_id, otp, or expires_at';
                return false;
            }

            $sql = "INSERT INTO {$this->tableName} (user_id, otp, expires_at, used) 
                    VALUES (:user_id, :otp, :expires_at, :used)";
            $stmt = $this->db->prepare($sql);

            $params = [
                'user_id' => $data['user_id'],
                'otp' => $data['otp'],
                'expires_at' => $data['expires_at'],
                'used' => $data['used'] ?? false
            ];

            if (!$this->executeQuery($stmt, $params)) {
                return false;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = 'Failed to create password reset record: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Get valid (not expired, not used) OTP for a user
     */
    public function getValidOTPForUser(int $userId): ?array
    {
        try {
            $sql = "SELECT reset_id, user_id, otp, expires_at, used 
                    FROM {$this->tableName} 
                    WHERE user_id = :user_id 
                      AND used = 0 
                      AND expires_at > NOW() 
                    ORDER BY expires_at DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, ['user_id' => $userId])) {
                return null;
            }

            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            return $reset ?: null;
        } catch (PDOException $e) {
            $this->lastError = 'Failed to get valid OTP: ' . $e->getMessage();
            error_log($this->lastError);
            return null;
        }
    }

    /**
     * Verify OTP by value and mark as used if valid
     */
    public function verifyOTP(string $otp): bool
    {
        try {
            $sql = "SELECT reset_id FROM {$this->tableName} 
                    WHERE otp = :otp 
                      AND used = 0 
                      AND expires_at > NOW()";
            $stmt = $this->db->prepare($sql);

            if (!$this->executeQuery($stmt, ['otp' => $otp])) {
                return false;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                $this->lastError = 'Invalid or expired OTP';
                return false;
            }

            // Mark OTP as used
            $updateSql = "UPDATE {$this->tableName} SET used = 1 WHERE reset_id = :reset_id";
            $updateStmt = $this->db->prepare($updateSql);

            return $this->executeQuery($updateStmt, ['reset_id' => $result['reset_id']]);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to verify OTP: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Delete expired password reset records
     */
    public function cleanupExpiredResets(): bool
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE expires_at < NOW() OR used = 1";
            $stmt = $this->db->prepare($sql);
            return $this->executeQuery($stmt);
        } catch (PDOException $e) {
            $this->lastError = 'Failed to clean up expired resets: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
}
