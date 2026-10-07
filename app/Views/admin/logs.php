<?php $activeNav = 'logs';
$__isAdminRole = is_admin_user();
$__canDeleteLogs = $__isAdminRole || (current_user()['role'] ?? '') === 'teacher';
$__canClearLogs = $__isAdminRole || (current_user()['role'] ?? '') === 'teacher';
$__clearLabel = $__isAdminRole ? 'Clear activities' : 'Clear my logs';
$__clearConfirm = $__isAdminRole
    ? 'Delete ALL the security activities? This action cannot be undone.'
    : 'Delete ALL the security activities of your account and your students? This action cannot be undone.';
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Security / Audit Log</h1>
        <p class="text-muted small mb-0"><?= $__isAdminRole ? 'See here <strong>who</strong> tried to copy, cut, paste, select, screenshot, open the console or breach the forum rules, with date/time and IP.' : 'Audit of your own account and your students.' ?></p>
    </div>
    <?php if ($__canClearLogs): ?>
        <form method="post" action="<?= e(base_url('admin/logs/clear')) ?>"
              onsubmit="return confirm('<?= e($__clearConfirm) ?>');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i><?= e($__clearLabel) ?></button>
        </form>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(base_url('admin/logs')) ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by user, email, detail or IP…" value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <select name="salon" class="form-select form-select-sm" title="Filter by course">
                    <option value="">All courses</option>
                    <?php foreach ($salons as $__salon): ?>
                        <option value="<?= (int) $__salon['id'] ?>" <?= ($salonFilter ?? 0) == (int) $__salon['id'] ? 'selected' : '' ?>>
                            <?= e($__salon['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="event" class="form-select form-select-sm">
                    <option value="">All events</option>
                    <?php foreach (['attempt_copy', 'attempt_cut', 'attempt_paste', 'attempt_select', 'attempt_contextmenu', 'attempt_printscreen', 'attempt_devtools', 'attempt_drag', 'attempt_window_switch', 'hack_duplicate_teacher', 'hack_duplicate_conclusion', 'hack_invalid_parent', 'hack_role', 'time_block', 'time_not_started', 'login_failed', 'login_locked', 'login', 'register', 'recover', 'logout', 'student_created', 'forum_created', 'forum_edited', 'forum_reopened', 'forum_deleted', 'salon_created', 'salon_deleted', 'student_locked', 'student_unlocked', 'student_deleted', 'teacher_deleted', 'response_deleted', 'log_deleted', 'logs_cleared', 'settings_updated'] as $__ev): ?>
                        <option value="<?= e($__ev) ?>" <?= ($filters['event'] ?? '') === $__ev ? 'selected' : '' ?>><?= e($eventLabel($__ev)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<!-- Print (A4 PDF): choose course, forum topic and WHAT to print -->
<form method="post" action="<?= e(base_url('admin/logs/export')) ?>" class="card border-0 shadow-sm mb-3">
    <?= csrf_field() ?>
    <div class="card-header bg-white fw-bold small"><i class="bi bi-printer me-1"></i>Print PDF (A4): copy-intent attempts</div>
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="small fw-semibold text-secondary mb-1 d-block">Course (header + filter)</label>
                <select name="salon" class="form-select form-select-sm">
                    <option value="">All courses</option>
                    <?php foreach ($salons as $__salon): ?>
                        <option value="<?= (int) $__salon['id'] ?>" <?= ($salonFilter ?? 0) == (int) $__salon['id'] ? 'selected' : '' ?>>
                            <?= e($__salon['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="small fw-semibold text-secondary mb-1 d-block">Forum topic (header)</label>
                <select name="forum" class="form-select form-select-sm">
                    <?php foreach ($forums as $__f): $__isActive = $activeForum && (int) $__f['id'] === (int) $activeForum['id']; ?>
                        <option value="<?= (int) $__f['id'] ?>" <?= $__isActive ? 'selected' : '' ?>>
                            <?= e($__f['title']) ?><?= $__isActive ? ' (active)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (!$forums): ?>
                        <option value="0">No forum</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="include_zeros" id="inc_zeros" value="1">
                    <label class="form-check-label small" for="inc_zeros">Include students with <strong>0</strong> attempts (full course roster)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="include_detail" id="inc_detail" value="1" checked>
                    <label class="form-check-label small" for="inc_detail">Include the list of every single attempt (date/time, student, IP)</label>
                </div>
            </div>
        </div>

        <div class="small fw-semibold text-secondary mt-2 mb-1">What to print (counted actions):</div>
        <div class="row g-1">
            <?php foreach (SecurityLog::ATTEMPT_EVENTS as $__ev): ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="events[]" value="<?= e($__ev) ?>" id="ev<?= e($__ev) ?>" checked>
                        <label class="form-check-label small" for="ev<?= e($__ev) ?>"><?= e($eventLabel($__ev)) ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-2">
            <button class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Export PDF (A4)</button>
            <span class="small text-muted ms-1">Header: course, professor and forum topic. Contents: summary by event + count per student.</span>
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
                    <?php if ($__canDeleteLogs): ?><th class="text-end">Action</th><?php endif; ?>
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
                        <?php if ($__canDeleteLogs): ?>
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
                    <tr><td colspan="<?= $__canDeleteLogs ? 6 : 5 ?>" class="text-center text-muted py-4">No security events registered.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>