<?php
/**
 * Static MySQL connection class using mysqli (prepared statements).
 */
class Database
{
    /** @var mysqli|null */
    private static $conn = null;

    private static function conn(): mysqli
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            self::$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            self::$conn->set_charset('utf8mb4');
        }
        return self::$conn;
    }

    private static function bindParams(mysqli_stmt $stmt, array $params): void
    {
        if (!$params) {
            return;
        }
        $types  = '';
        $values = [];
        foreach ($params as $p) {
            if (is_int($p)) {
                $types .= 'i';
            } elseif (is_double($p)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $p;
        }
        $stmt->bind_param($types, ...$values);
    }

    /** Runs a prepared query and returns the mysqli_result (null on INSERT/UPDATE/DELETE). */
    public static function query(string $sql, array $params = []): ?mysqli_result
    {
        $stmt = self::conn()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->get_result();
    }

    /** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::conn()->prepare($sql);
        self::bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public static function insertId(): int
    {
        return self::conn()->insert_id;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $result = self::query($sql, $params);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params);
        return $result ? ($result->fetch_assoc() ?: null) : null;
    }

    public static function count(string $sql, array $params = []): int
    {
        $row = self::fetchOne($sql, $params);
        $row = $row ?: [];
        return (int) reset($row);
    }

    public static function serverTime(): int
    {
        return (int) self::conn()->query("SELECT UNIX_TIMESTAMP() AS t")->fetch_assoc()['t'];
    }
}