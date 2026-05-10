<?php

/**
 * FILE: User.php
 * PURPOSE: Authenticates credentials, registers accounts, and exposes tiny privacy helpers consumed by recommendation Strategies.
 * USED BY: `AuthController`, cart/order services, `RecommendationEngine` consent checks before enabling purchase-history rails.
 * DESIGN PATTERN: None — passive data access via PDO prepared statements only.
 */

// Maps IBRCN users table rows into PHP arrays for session hydration and GDPR-oriented recommendation opt-in probing (model layer).
class User
{
    // Cached flag describing whether optional personalization column exists without hammering information_schema each request.
    private static ?bool $shareReadingHistoryColumnExists = null;

    private PDO $db;

    /**
     * Retains PDO wired through constructors originating from `DB::getInstance()` singleton semantics per HTTP request.
     *
     * @param PDO $databaseConnection Shared PDO handle injected by controllers/services constructing this model.
     * @return void
     */
    public function __construct(PDO $databaseConnection)
    {
        $this->db = $databaseConnection;
    }

    /**
     * Retrieves a user row by unique username for interactive login password verification flows.
     *
     * @param string $username Case-sensitive username substring supplied via login forms (trimmed upstream).
     * @return array<string, mixed>|null Associative row including password hash when found; otherwise null.
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT user_id, username, email, password_hash, role FROM users WHERE username = :username LIMIT 1"
        );
        $stmt->execute(array('username' => $username));
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Loads account identity columns keyed by surrogate primary key used throughout session and middleware guards.
     *
     * @param int $userId Numeric `users.user_id` referenced by foreign keys across clubs/carts/orders modules.
     * @return array<string, mixed>|null Row snapshot without password when absent (caller decides sensitivity).
     */
    public function findById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT user_id, username, email, password_hash, role FROM users WHERE user_id = :user_id LIMIT 1"
        );
        $stmt->execute(array('user_id' => $userId));
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Inserts a freshly hashed credential bundle enforcing uniqueness constraints enforced by database indexes/triggers.
     *
     * @param string $username Unique handle collected during signup wizard validation layers.
     * @param string $email    Unique electronic mailbox string normalized lightly before persistence attempt.
     * @param string $password Plaintext password hashed via bcrypt before insertion for secure storage semantics.
     * @param string $role     Role discriminator aligning with middleware expectations (`Reader`, `Owner`, `Admin`).
     * @return bool|int New `user_id` on success or boolean false when duplicate conflicts/other PDO failures occur.
     */
    public function registerUser(string $username, string $email, string $password, string $role): bool|int
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password, :role)"
            );
            $stmt->execute(array(
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
            ));
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Determines whether optional column `users.share_reading_history` exists and contains an affirmative personalization flag.
     *
     * @param int $readerUserId Authenticated reader identifier evaluated before Strategy swaps to purchase-history algorithms.
     * @return bool True only when schema supports column AND stored flag resolves truthy; otherwise false preserving legacy homepage parity.
     */
    public function readerAllowsPersonalizedRecommendationsFromHistory(int $readerUserId): bool
    {
        if ($readerUserId <= 0) {
            return false;
        }

        if (!self::usersTableHasShareReadingHistoryColumn($this->db)) {
            return false;
        }

        $statement = $this->db->prepare(
            'SELECT share_reading_history FROM users WHERE user_id = :user_id LIMIT 1'
        );
        $statement->execute(array('user_id' => $readerUserId));
        $rawFlag = $statement->fetchColumn();
        if ($rawFlag === false) {
            return false;
        }

        return (int) $rawFlag === 1 || $rawFlag === true || $rawFlag === '1';
    }

    /**
     * Memoizes schema capability probing so SHOW COLUMNS executes at most once per PHP worker/request lifecycle.
     *
     * @param PDO $databaseConnection Active PDO used for lightweight metadata introspection against live schema.
     * @return bool True when optional personalization column exists enabling GDPR-aware branching logic elsewhere.
     */
    private static function usersTableHasShareReadingHistoryColumn(PDO $databaseConnection): bool
    {
        if (self::$shareReadingHistoryColumnExists !== null) {
            return self::$shareReadingHistoryColumnExists;
        }

        try {
            $result = $databaseConnection->query("SHOW COLUMNS FROM users LIKE 'share_reading_history'");
            self::$shareReadingHistoryColumnExists = $result !== false && (bool) $result->fetch(PDO::FETCH_NUM);
        } catch (Throwable $exception) {
            self::$shareReadingHistoryColumnExists = false;
        }

        return self::$shareReadingHistoryColumnExists;
    }
}
