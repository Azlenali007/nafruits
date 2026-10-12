<?php
/**
 * NA FRESH – Fruits & Coconuts
 * MySQL Database Connection Layer using PDO
 */

declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionFailed = false;
    private static ?string $lastError = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Get or initialize the PDO instance
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (self::$connectionFailed) {
            return null;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$instance;
        } catch (PDOException $e) {
            self::$connectionFailed = true;
            self::$lastError = $e->getMessage();
            error_log('NA Fresh Database Connection Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper to run prepared queries with parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $db = self::getConnection();
        if (!$db) {
            throw new RuntimeException('Database connection is not available: ' . (self::$lastError ?? 'Unknown error'));
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single scalar value
     */
    public static function fetchValue(string $sql, array $params = []): mixed {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute and return last insert ID
     */
    public static function insert(string $sql, array $params = []): string {
        $db = self::getConnection();
        if (!$db) {
            throw new RuntimeException('Database connection is not available');
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $db->lastInsertId();
    }

    /**
     * Execute UPDATE / DELETE and return affected row count
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Check if database connection is alive
     */
    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    /**
     * Get last connection error message
     */
    public static function getLastError(): ?string {
        return self::$lastError;
    }
}

/**
 * Shorthand helper for database access
 */
function getDb(): ?PDO {
    return Database::getConnection();
}
