<?php
/**
 * Forums model. Each forum has a question, a subject and a time window (open_at / close_at).
 */
class Forum
{
    public static function active(): ?array
    {
        return Database::fetchOne(
            "SELECT f.*, u.first_name, u.last_name, u.email AS teacher_email
             FROM forums f
             LEFT JOIN users u ON u.id = f.created_by
             WHERE f.is_active = 1
             ORDER BY f.id DESC LIMIT 1"
        );
    }

    /** All forums. When $teacherId is set, only the forums created by that teacher. */
    public static function all(?int $teacherId = null): array
    {
        $sql = "SELECT f.*, u.first_name, u.last_name,
                    (SELECT COUNT(*) FROM responses r WHERE r.forum_id = f.id) AS total_responses
             FROM forums f
             LEFT JOIN users u ON u.id = f.created_by";
        $params = [];
        if ($teacherId !== null) {
            $sql      .= " WHERE f.created_by = ?";
            $params[] = $teacherId;
        }
        $sql .= " ORDER BY f.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $d): int
    {
        Database::execute(
            "INSERT INTO forums (title, subject, question, open_at, close_at, created_by, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)",
            [
                trim($d['title']),
                trim($d['subject']),
                trim($d['question']),
                $d['open_at'],
                $d['close_at'],
                (int) $d['created_by'],
            ]
        );
        $id = Database::insertId();
        self::activate($id);
        return $id;
    }

    public static function activate(int $id): void
    {
        Database::execute("UPDATE forums SET is_active = 0");
        Database::execute("UPDATE forums SET is_active = 1 WHERE id = ?", [$id]);
    }

    public static function exists(int $id): bool
    {
        return (bool) Database::fetchOne("SELECT id FROM forums WHERE id = ? LIMIT 1", [$id]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM forums WHERE id = ? LIMIT 1", [$id]);
    }

    /** Whether the forum was created by the given teacher/admin. */
    public static function ownedBy(int $forumId, int $userId): bool
    {
        return (bool) Database::fetchOne(
            "SELECT 1 FROM forums WHERE id = ? AND created_by = ? LIMIT 1",
            [$forumId, $userId]
        );
    }

    /** Deletes a forum (responses and classroom assignments cascade). */
    public static function deleteForum(int $forumId): void
    {
        Database::execute("DELETE FROM forums WHERE id = ?", [$forumId]);
    }

    public static function updateWindow(int $id, string $openAt, string $closeAt): void
    {
        Database::execute("UPDATE forums SET open_at = ?, close_at = ? WHERE id = ?", [$openAt, $closeAt, $id]);
    }

    public static function update(int $id, array $d): void
    {
        Database::execute(
            "UPDATE forums SET title = ?, subject = ?, question = ?, open_at = ?, close_at = ? WHERE id = ?",
            [
                trim($d['title']),
                trim($d['subject']),
                trim($d['question']),
                $d['open_at'],
                $d['close_at'],
                $id,
            ]
        );
    }

    // ------------------------------------------------------------------
    // Classroom assignment (forum <-> classrooms)
    // ------------------------------------------------------------------

    /** Returns the ids of the classrooms the forum is assigned to. */
    public static function salonsOf(int $forumId): array
    {
        $rows = Database::fetchAll("SELECT salon_id FROM forum_salones WHERE forum_id = ?", [$forumId]);
        $ids  = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['salon_id'];
        }
        return $ids;
    }

    /** Replaces the classroom assignment of a forum. */
    public static function setSalons(int $forumId, array $salonIds): void
    {
        Database::execute("DELETE FROM forum_salones WHERE forum_id = ?", [$forumId]);
        foreach (array_values(array_unique($salonIds)) as $salonId) {
            Database::execute(
                "INSERT INTO forum_salones (forum_id, salon_id) VALUES (?, ?)",
                [$forumId, (int) $salonId]
            );
        }
    }

    /** Whether a forum is assigned to the given classroom. */
    public static function isAssignedTo(int $forumId, int $salonId): bool
    {
        if ($salonId <= 0) {
            return false;
        }
        return (bool) Database::fetchOne(
            "SELECT 1 FROM forum_salones WHERE forum_id = ? AND salon_id = ? LIMIT 1",
            [$forumId, $salonId]
        );
    }

    /** All forums assigned to a classroom (for the student's sidebar). */
    public static function forSalon(int $salonId): array
    {
        if ($salonId <= 0) {
            return [];
        }
        return Database::fetchAll(
            "SELECT f.*, u.first_name, u.last_name,
                    (SELECT COUNT(*) FROM responses r WHERE r.forum_id = f.id) AS total_responses
             FROM forums f
             JOIN forum_salones fs ON fs.forum_id = f.id
             LEFT JOIN users u ON u.id = f.created_by
             WHERE fs.salon_id = ?
             ORDER BY f.created_at DESC",
            [$salonId]
        );
    }
}