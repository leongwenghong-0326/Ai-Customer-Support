<?php
/**
 * NovaServe AI - Database Connection & Query Helper
 * High-performance PDO abstraction with auto-port detection
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {
        // Check for custom installer config file if present
        $customConfig = __DIR__ . '/db_custom.php';
        $host = DB_HOST;
        $port = DB_PORT;
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;

        if (file_exists($customConfig)) {
            $custom = require $customConfig;
            $host = $custom['host'] ?? $host;
            $port = $custom['port'] ?? $port;
            $dbname = $custom['dbname'] ?? $dbname;
            $user = $custom['user'] ?? $user;
            $pass = $custom['pass'] ?? $pass;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        // Attempt primary connection
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Auto port fallback attempt (e.g. switch 3307 <-> 3306)
            $altPort = ($port == 3307) ? 3306 : 3307;
            try {
                $altDsn = "mysql:host={$host};port={$altPort};dbname={$dbname};charset=utf8mb4";
                $this->pdo = new PDO($altDsn, $user, $pass, $options);
            } catch (PDOException $e2) {
                error_log("Database connection failure: " . $e->getMessage() . " | " . $e2->getMessage());
                if (defined('IS_INSTALLER') && IS_INSTALLER) {
                    throw $e;
                }
                // If database does not exist or connection fails, show graceful page
                die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #fee2e2;background:#fef2f2;border-radius:12px;color:#991b1b;'>
                    <h2>Database Connection Notice</h2>
                    <p>Unable to connect to NovaServe database [<strong>{$dbname}</strong>]. If this is your first time setting up, please run the installer:</p>
                    <p><a href='" . BASE_URL . "install.php' style='display:inline-block;padding:10px 18px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>Launch NovaServe Installer</a></p>
                </div>");
            }
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    // Helper: Execute prepared statement with parameters
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Helper: Fetch single record
    public function fetchOne(string $sql, array $params = []): ?array {
        $res = $this->query($sql, $params)->fetch();
        return $res ? $res : null;
    }

    // Helper: Fetch all records
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    // Helper: Insert record and return last insert ID
    public function insert(string $table, array $data): int {
        $keys = array_keys($data);
        $fields = implode('`, `', $keys);
        $placeholders = ':' . implode(', :', $keys);
        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return (int)$this->pdo->lastInsertId();
    }

    // Helper: Update record
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = [];
        $params = [];
        foreach ($data as $key => $val) {
            $set[] = "`{$key}` = :set_{$key}";
            $params["set_{$key}"] = $val;
        }
        $setString = implode(', ', $set);
        $sql = "UPDATE `{$table}` SET {$setString} WHERE {$where}";
        $stmt = $this->query($sql, array_merge($params, $whereParams));
        return $stmt->rowCount();
    }
}
