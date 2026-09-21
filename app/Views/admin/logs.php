<?php $activeNav = 'logs';
$__isAdminRole = is_admin_user();
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Security / Audit Log</h1>
        <p class="text-muted small mb-0"><?= $__isAdminRole ? 'See here <strong>who</strong> tried to copy, cut, paste, select, screenshot, open the console or breach the forum rules, with date/time and IP.' : 'Audit of your own account and your students.' ?></p>
    </div>
    <?php if ($__isAdminRole): ?>
        <form method="post" action="<?= e(base_url('admin/logs/clear')) ?>"
              onsubmit="return confirm('Delete ALL the security log entries? This action cannot be undone.');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Clear log</button>
        </form>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(base_url('admin/logs')) ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by user, email, detail or IP…" value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-4">
                <select name="event" class="form-select form-select-sm">
                    <option value="">All events</option>
                    <?php foreach (['attempt_copy', 'attempt_cut', 'attempt_paste', 'attempt_select', 'attempt_contextmenu', 'attempt_printscreen', 'attempt_devtools', 'attempt_drag', 'hack_duplicate_teacher', 'hack_duplicate_conclusion', 'hack_invalid_parent', 'hack_role', 'time_block', 'time_not_started', 'login_failed', 'login_locked', 'login', 'register', 'recover', 'logout', 'student_created', 'forum_created', 'forum_edited', 'forum_reopened', 'forum_deleted', 'salon_created', 'salon_deleted', 'student_locked', 'student_unlocked', 'student_deleted', 'teacher_deleted', 'response_deleted', 'log_deleted', 'logs_cleared', 'settings_updated'] as $__ev): ?>
                        <option value="<?= e($__ev) ?>" <?= ($filters['event'] ?? '') === $__ev ? 'selected' : '' ?>><?= e($eventLabel($__ev)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <button class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold small">Registered events (<?= count($logs) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date / Time</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Detail</th>
                    <th>IP</th>
                    <?php if ($__isAdminRole): ?><th class="text-end">Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                    <?php
                    $__ev = $l['event'];
                    $__danger = (strpos($__ev, 'hack_') === 0) || in_array($__ev, ['login_locked', 'time_block'], true);
                    ?>
                    <tr>
                        <td class="text-nowrap text-muted"><?= e(pretty_datetime($l['created_at'])) ?></td>
                        <td>
                            <?php if ($l['user_id']): ?>
                                <span class="fw-semibold"><?= e(($l['first_name'] ?? '') . ' ' . ($l['last_name'] ?? '')) ?></span>
                                <div class="text-muted"><?= e($l['email'] ?? '—') ?></div>
                            <?php else: ?>
                                <span class="text-muted">— (no session)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $__danger ? 'text-bg-danger' : 'text-bg-secondary' ?>"><?= e($eventLabel($__ev)) ?></span>
                        </td>
                        <td class="text-secondary" style="max-width: 380px;"><?= e($l['detail'] ?? '—') ?></td>
                        <td class="text-nowrap"><code><?= e($l['ip']) ?></code></td>
                        <?php if ($__isAdminRole): ?>
                            <td class="text-end">
                                <form method="post" action="<?= e(base_url('admin/logs/delete')) ?>" class="d-inline"
                                      onsubmit="return confirm('Delete this log entry (ID <?= (int) $l['id'] ?>)?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="log_id" value="<?= (int) $l['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                    <tr><td colspan="<?= $__isAdminRole ? 6 : 5 ?>" class="text-center text-muted py-4">No security events registered.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>