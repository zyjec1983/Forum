<?php
/**
 * Classrooms model (9th "A", 9th "B", ...). Each classroom is owned by a
 * teacher (teacher_id); the administrator can see and manage all of them.
 */
class Salon
{
    /** All classrooms. When $teacherId is set, only that teacher's classrooms. */
    public static function all(?int $teacherId = null): array
    {
        $sql = "SELECT s.*, u.first_name, u.last_name AS teacher_name
                FROM salones s
                LEFT JOIN users u ON u.id = s.teacher_id";
        $params = [];
        if ($teacherId !== null) {
            $sql      .= " WHERE s.teacher_id = ?";
            $params[] = $teacherId;
        }
        $sql .= " ORDER BY s.name ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM salones WHERE id = ? LIMIT 1", [$id]);
    }

    /** Whether the classroom belongs to the given teacher. */
    public static function ownedBy(int $salonId, int $teacherId): bool
    {
        return (bool) Database::fetchOne(
            "SELECT 1 FROM salones WHERE id = ? AND teacher_id = ? LIMIT 1",
            [$salonId, $teacherId]
        );
    }

    public static function create(string $name, ?int $teacherId = null): int
    {
        Database::execute(
            "INSERT INTO salones (name, teacher_id) VALUES (?, ?)",
            [trim($name), $teacherId]
        );
        return Database::insertId();
    }

    public static function delete(int $id): void
    {
        // Students get salon_id NULL (FK SET NULL)
        Database::execute("DELETE FROM salones WHERE id = ?", [$id]);
    }

    public static function count(?int $teacherId = null): int
    {
        if ($teacherId === null) {
            return Database::count("SELECT COUNT(*) AS c FROM salones");
        }
        return Database::count("SELECT COUNT(*) AS c FROM salones WHERE teacher_id = ?", [$teacherId]);
    }
}