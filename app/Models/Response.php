<?php
/**
 * Participations model:
 *  - teacher   => direct response to the teacher (NO REPEATS)
 *  - partner   => reply to a classmate (WhatsApp style / professional forum)
 *  - conclusion=> final conclusion (ONE per student)
 */
class Response
{
    public static function teacherResponses(int $forumId): array
    {
        $sql = "SELECT r.*, u.first_name, u.last_name, s.name AS salon_name
                FROM responses r
                JOIN users u ON u.id = r.user_id
                LEFT JOIN salones s ON s.id = u.salon_id
                WHERE r.forum_id = ? AND r.type = 'teacher'
                ORDER BY r.created_at DESC";
        return Database::fetchAll($sql, [$forumId]);
    }

    public static function partnerReplies(int $responseId): array
    {
        $sql = "SELECT r.*, u.first_name, u.last_name,
                       pu.first_name AS parent_fn, pu.last_name AS parent_ln
                FROM responses r
                JOIN users u ON u.id = r.user_id
                LEFT JOIN responses pr ON pr.id = r.parent_id
                LEFT JOIN users pu ON pu.id = pr.user_id
                WHERE r.parent_id = ? AND r.type = 'partner'
                ORDER BY r.created_at ASC";
        return Database::fetchAll($sql, [$responseId]);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT r.*, u.first_name, u.last_name
             FROM responses r JOIN users u ON u.id = r.user_id
             WHERE r.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function create(int $forumId, int $userId, string $type, string $content, ?int $parentId = null): int
    {
        Database::execute(
            "INSERT INTO responses (forum_id, user_id, parent_id, type, content) VALUES (?, ?, ?, ?, ?)",
            [$forumId, $userId, $parentId, $type, trim($content)]
        );
        return Database::insertId();
    }

    public static function hasTeacherResponse(int $forumId, int $userId): bool
    {
        return (bool) Database::fetchOne(
            "SELECT id FROM responses WHERE forum_id = ? AND user_id = ? AND type = 'teacher' LIMIT 1",
            [$forumId, $userId]
        );
    }

    public static function hasConclusion(int $forumId, int $userId): bool
    {
        return (bool) Database::fetchOne(
            "SELECT id FROM responses WHERE forum_id = ? AND user_id = ? AND type = 'conclusion' LIMIT 1",
            [$forumId, $userId]
        );
    }

    public static function counts(int $forumId): array
    {
        $row = Database::fetchOne(
            "SELECT
                SUM(type='teacher')    AS teacher,
                SUM(type='partner')    AS partner,
                SUM(type='conclusion') AS conclusion
             FROM responses WHERE forum_id = ?",
            [$forumId]
        ) ?: ['teacher' => 0, 'partner' => 0, 'conclusion' => 0];
        return array_map('intval', $row);
    }

    /** Listing for the admin/teacher panel. */
    public static function allFiltered(array $filters = []): array
    {
        $sql    = "SELECT r.*, u.first_name, u.last_name, u.email, s.name AS salon_name,
                          pu.first_name AS parent_fn, pu.last_name AS parent_ln, f.title AS forum_title
                   FROM responses r
                   JOIN users u ON u.id = r.user_id
                   LEFT JOIN salones s ON s.id = u.salon_id
                   LEFT JOIN responses pr ON pr.id = r.parent_id
                   LEFT JOIN users pu ON pu.id = pr.user_id
                   LEFT JOIN forums f ON f.id = r.forum_id
                   WHERE 1=1";
        $params = [];
        if (!empty($filters['type'])) {
            $sql      .= " AND r.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['forum_id'])) {
            $sql      .= " AND r.forum_id = ?";
            $params[] = (int) $filters['forum_id'];
        }
        if (!empty($filters['teacher_id'])) {
            $sql .= " AND r.forum_id IN (SELECT id FROM forums WHERE created_by = ?)";
            $params[] = (int) $filters['teacher_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR r.content LIKE ? OR u.email LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            foreach ([1, 2, 3, 4] as $_k) {
                $params[] = $like;
            }
        }
        $sql .= " ORDER BY r.created_at DESC LIMIT 1000";
        return Database::fetchAll($sql, $params);
    }

    public static function total(?int $teacherId = null): int
    {
        if ($teacherId === null) {
            return Database::count("SELECT COUNT(*) AS c FROM responses");
        }
        return Database::count(
            "SELECT COUNT(*) AS c FROM responses r JOIN forums f ON f.id = r.forum_id
             WHERE f.created_by = ?",
            [$teacherId]
        );
    }

    /** Deletes a response (its replies cascade through the parent FK). */
    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM responses WHERE id = ?", [$id]);
    }

    public static function partnerJoinedActions(int $forumId): array
    {
        return Database::fetchAll(
            "SELECT r.user_id, u.first_name, u.last_name, u.email, s.name AS salon_name,
                    SUM(r.type='teacher')    AS teacher_responses,
                    SUM(r.type='partner')    AS partner_replies,
                    SUM(r.type='conclusion') AS conclusions
             FROM responses r
             JOIN users u ON u.id = r.user_id
             LEFT JOIN salones s ON s.id = u.salon_id
             WHERE r.forum_id = ?
             GROUP BY r.user_id, u.first_name, u.last_name, u.email, s.name
             ORDER BY u.first_name, u.last_name",
            [$forumId]
        );
    }
}