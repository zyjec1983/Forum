<?php
/**
 * Users model (students and administrators).
 */
class User
{
    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [mb_strtolower(trim($email))]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT u.*, s.name AS salon_name
             FROM users u
             LEFT JOIN salones s ON s.id = u.salon_id
             WHERE u.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function create(array $d): int
    {
        Database::execute(
            "INSERT INTO users (email, first_name, last_name, salon_id, password, role)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                mb_strtolower(trim($d['email'])),
                trim($d['first_name']),
                trim($d['last_name']),
                $d['salon_id'] === null || $d['salon_id'] === '' ? null : (int) $d['salon_id'],
                $d['password'],
                $d['role'] ?? 'student',
            ]
        );
        return Database::insertId();
    }

    public static function all(array $filters = []): array
    {
        $sql    = "SELECT u.*, s.name AS salon_name
                   FROM users u
                   LEFT JOIN salones s ON s.id = u.salon_id
                   WHERE 1=1";
        $params = [];

        if (!empty($filters['salon_id'])) {
            $sql      .= " AND u.salon_id = ?";
            $params[] = (int) $filters['salon_id'];
        }
        if (!empty($filters['search'])) {
            $sql      .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $like     = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['role'])) {
            $sql      .= " AND u.role = ?";
            $params[] = $filters['role'];
        }
        if (!empty($filters['teacher_id'])) {
            $sql .= " AND u.salon_id IN (SELECT id FROM salones WHERE teacher_id = ?)";
            $params[] = (int) $filters['teacher_id'];
        }

        $sql .= " ORDER BY u.first_name ASC, u.last_name ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function studentsCount(): int
    {
        return Database::count("SELECT COUNT(*) AS c FROM users WHERE role = 'student'");
    }

    /** Returns the updated failed-attempts count and locks the account at the maximum. */
    public static function registerFailedAttempt(int $id): int
    {
        Database::execute("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?", [$id]);
        $attempts = (int) (self::findById($id)['failed_attempts'] ?? 0);
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            Database::execute("UPDATE users SET locked = 1 WHERE id = ?", [$id]);
        }
        return $attempts;
    }

    public static function resetFailedAttempts(int $id): void
    {
        Database::execute("UPDATE users SET failed_attempts = 0, locked = 0 WHERE id = ?", [$id]);
    }

    public static function isLocked(int $id): bool
    {
        return (bool) (self::findById($id)['locked'] ?? false);
    }

    public static function setPassword(int $id, string $hash): void
    {
        Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $id]);
    }

    public static function toggleLock(int $id): int
    {
        Database::execute("UPDATE users SET locked = IF(locked = 1, 0, 1) WHERE id = ?", [$id]);
        return (int) (self::findById($id)['locked'] ?? 0);
    }

    /**
     * Deletes a user cleaning up their dependencies:
     *  - a teacher's forums are deleted first (cascades responses + classroom
     *    assignments); the teacher's classrooms are removed by cascade.
     *  - the user's responses are removed by cascade and security logs keep
     *    their rows with a NULL user.
     */
    public static function deleteUser(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $user = self::findById($id);
        if (!$user) {
            return;
        }
        if ($user['role'] === 'teacher') {
            Database::execute("DELETE FROM forums WHERE created_by = ?", [$id]);
        }
        Database::execute("DELETE FROM users WHERE id = ?", [$id]);
    }
}