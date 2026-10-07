<?php
/**
 * Security audit model.
 * Logs blocks of copy/cut/paste/select/screenshot attempts, hacking attempts
 * and relevant events (user, event, date/time and IP).
 */
class SecurityLog
{
    /** Events that represent an attempt to take content out of the forum. */
    public const ATTEMPT_EVENTS = [
        'attempt_copy',
        'attempt_cut',
        'attempt_paste',
        'attempt_select',
        'attempt_contextmenu',
        'attempt_printscreen',
        'attempt_devtools',
        'attempt_drag',
        'attempt_window_switch',
    ];

    /** Short (<= 8 chars) labels used as columns in the printed report. */
    public const ATTEMPT_SHORT = [
        'attempt_copy'          => 'Copia',
        'attempt_cut'           => 'Cortar',
        'attempt_paste'         => 'Pegar',
        'attempt_select'        => 'Select',
        'attempt_contextmenu'   => 'Menu',
        'attempt_printscreen'   => 'Captura',
        'attempt_devtools'      => 'Consola',
        'attempt_drag'          => 'Arrastre',
        'attempt_window_switch' => 'Ventana',
    ];

    public static function record(?int $userId, string $event, string $detail, ?string $ip = null, ?string $ua = null): void
    {
        Database::execute(
            "INSERT INTO security_logs (user_id, event, detail, ip, user_agent)
             VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                substr($event, 0, 60),
                mb_substr($detail, 0, 255),
                $ip ?: client_ip(),
                $ua ?: client_user_agent(),
            ]
        );
    }

    public static function all(array $filters = []): array
    {
        $sql    = "SELECT l.*, u.first_name, u.last_name, u.email
                   FROM security_logs l
                   LEFT JOIN users u ON u.id = l.user_id
                   WHERE 1=1";
        $params = [];

        if (!empty($filters['event'])) {
            $sql      .= " AND l.event = ?";
            $params[] = $filters['event'];
        }
        if (!empty($filters['salon_id'])) {
            $sql      .= " AND u.salon_id = ?";
            $params[] = (int) $filters['salon_id'];
        }
        if (!empty($filters['user_ids'])) {
            $ids = array_values(array_unique(array_map('intval', (array) $filters['user_ids'])));
            $ids = array_filter($ids, function ($id) { return $id > 0; });
            if ($ids) {
                $sql .= " AND l.user_id IN (" . implode(',', $ids) . ")";
            }
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR l.detail LIKE ? OR l.ip LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            for ($i = 0; $i < 5; $i++) {
                $params[] = $like;
            }
        }
        $sql .= " ORDER BY l.created_at DESC LIMIT 1000";
        return Database::fetchAll($sql, $params);
    }

    // ------------------------------------------------------------------
    // COPY-INTENT COUNTERS (students only) used by the printed report
    // ------------------------------------------------------------------

    /**
     * WHERE/params for copy-intent attempts of STUDENTS:
     * selected events, optional classroom, optional scope (user ids).
     */
    private static function attemptWhere(array $filters): array
    {
        $events = array_values(array_intersect(
            (array) ($filters['events'] ?? []),
            self::ATTEMPT_EVENTS
        ));
        if (!$events) {
            $events = self::ATTEMPT_EVENTS;
        }

        $sql = " FROM security_logs l
                 JOIN users u ON u.id = l.user_id
                 WHERE u.role = 'student'
                   AND l.event IN (" . implode(',', array_fill(0, count($events), '?')) . ")";
        $params = $events;

        if (!empty($filters['salon_id'])) {
            $sql      .= " AND u.salon_id = ?";
            $params[] = (int) $filters['salon_id'];
        }
        if (array_key_exists('user_ids', $filters) && $filters['user_ids'] !== null) {
            $ids = array_values(array_unique(array_map('intval', (array) $filters['user_ids'])));
            $ids = array_filter($ids, function ($id) { return $id > 0; });
            if ($ids) {
                $sql .= " AND l.user_id IN (" . implode(',', $ids) . ")";
            } else {
                $sql .= " AND 1 = 0";
            }
        }
        return [$sql, $params];
    }

    /** Attempts grouped by student and event (rows of the printed matrix). */
    public static function attemptsByStudent(array $filters = []): array
    {
        [$where, $params] = self::attemptWhere($filters);
        return Database::fetchAll(
            "SELECT l.event, l.user_id, u.first_name, u.last_name, COUNT(*) AS total
             {$where}
             GROUP BY l.event, l.user_id, u.first_name, u.last_name, u.email
             ORDER BY u.last_name ASC, u.first_name ASC",
            $params
        );
    }

    /** Totals per event: total attempts and how many students attempted it. */
    public static function attemptsByEvent(array $filters = []): array
    {
        [$where, $params] = self::attemptWhere($filters);
        return Database::fetchAll(
            "SELECT l.event, COUNT(*) AS total, COUNT(DISTINCT l.user_id) AS students
             {$where}
             GROUP BY l.event",
            $params
        );
    }

    /** Every single attempt (date/time, student, event, IP) newest first. */
    public static function attemptsDetail(array $filters = [], int $limit = 1500): array
    {
        [$where, $params] = self::attemptWhere($filters);
        $limit = max(1, min(3000, $limit));
        return Database::fetchAll(
            "SELECT l.created_at, l.event, l.ip, u.first_name, u.last_name
             {$where}
             ORDER BY l.created_at DESC
             LIMIT {$limit}",
            $params
        );
    }

    public static function count(?array $userIds = null): int
    {
        if ($userIds === null) {
            return Database::count("SELECT COUNT(*) AS c FROM security_logs");
        }
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        $ids = array_filter($ids, function ($id) { return $id > 0; });
        if (!$ids) {
            return 0;
        }
        return Database::count("SELECT COUNT(*) AS c FROM security_logs WHERE user_id IN (" . implode(',', $ids) . ")");
    }

    public static function countToday(?array $userIds = null): int
    {
        if ($userIds === null) {
            return Database::count("SELECT COUNT(*) AS c FROM security_logs WHERE DATE(created_at) = CURDATE()");
        }
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        $ids = array_filter($ids, function ($id) { return $id > 0; });
        if (!$ids) {
            return 0;
        }
        return Database::count(
            "SELECT COUNT(*) AS c FROM security_logs WHERE DATE(created_at) = CURDATE() AND user_id IN (" . implode(',', $ids) . ")"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM security_logs WHERE id = ? LIMIT 1", [$id]);
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM security_logs WHERE id = ?", [$id]);
    }

    public static function clear(): void
    {
        Database::execute("DELETE FROM security_logs");
    }

    /** Deletes all log entries belonging to the given user ids (teacher scope). */
    public static function clearFor(array $userIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        $ids = array_filter($ids, function ($id) { return $id > 0; });
        if (!$ids) {
            return;
        }
        Database::execute(
            "DELETE FROM security_logs WHERE user_id IN (" . implode(',', $ids) . ")"
        );
    }

    /** Human readable label for each event shown in the admin panel. */
    public static function eventLabel(string $event): string
    {
        $labels = [
            'attempt_copy'           => 'Attempt to Copy',
            'attempt_cut'            => 'Attempt to Cut',
            'attempt_paste'          => 'Attempt to Paste',
            'attempt_select'         => 'Attempt to select text',
            'attempt_contextmenu'    => 'Blocked context menu',
            'attempt_printscreen'    => 'Attempt to take a screenshot',
            'attempt_devtools'       => 'Attempt to open devtools',
            'attempt_drag'           => 'Attempt to drag text',
            'attempt_window_switch'  => 'Window/tab switch (session closed)',
            'hack_duplicate_teacher' => 'Hack: 2nd teacher response',
            'hack_duplicate_conclusion' => 'Hack: 2nd conclusion',
            'hack_invalid_parent'    => 'Hack: reply to an invalid target',
            'hack_role'              => 'Hack: unauthorized role',
            'time_block'             => 'Blocked by the time window',
            'time_not_started'       => 'Blocked: attempt before opening',
            'login_failed'           => 'Failed sign-in',
            'login_locked'           => 'Account blocked / locked by attempts',
            'register'               => 'Account registration',
            'recover'                => 'Password recovery',
            'login'                  => 'Sign-in',
            'logout'                 => 'Sign out',
            'student_created'        => 'Student created by administrator',
            'forum_created'          => 'Forum created',
            'forum_edited'           => 'Forum edited',
            'forum_reopened'         => 'Forum reopened',
            'forum_deleted'          => 'Forum deleted',
            'salon_created'          => 'Classroom created',
            'salon_deleted'          => 'Classroom deleted',
            'student_locked'         => 'Student blocked',
            'student_unlocked'       => 'Student unblocked',
            'student_deleted'        => 'Student deleted',
            'teacher_deleted'        => 'Teacher deleted',
            'guest_created'          => 'Guest account created',
            'guest_deleted'          => 'Guest account deleted',
            'guest_locked'           => 'Guest account blocked',
            'guest_unlocked'         => 'Guest account unblocked',
            'response_deleted'       => 'Response deleted',
            'log_deleted'            => 'Log entry deleted',
            'logs_cleared'           => 'Security log cleared',
            'settings_updated'       => 'Registration settings updated',
        ];
        return $labels[$event] ?? $event;
    }
}