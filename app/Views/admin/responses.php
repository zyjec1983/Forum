<?php $activeNav = 'responses';
$__isAdminRole = is_admin_user();
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Forum Responses</h1>
        <p class="text-muted small mb-0"><?= $__isAdminRole ? 'Student participation in the active forum and detailed view of all responses.' : 'Participation of your students and detailed view of all responses in your forums.' ?></p>
    </div>
</div>

<?php if ($activeForum): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold small">Participation summary · <?= e($activeForum['title']) ?></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Classroom</th>
                        <th>Teacher response</th>
                        <th>Partner replies</th>
                        <th>Conclusion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                        <tr>
                            <td>
                                <span class="avatar avatar-xs avatar-slate me-2"><?= e(initials($row['first_name'] . ' ' . $row['last_name'])) ?></span>
                                <?= e($row['first_name'] . ' ' . $row['last_name']) ?>
                                <div class="text-muted"><?= e($row['email']) ?></div>
                            </td>
                            <td><?= e($row['salon_name'] ?? '—') ?></td>
                            <td><?= (int) $row['teacher_responses'] ?></td>
                            <td><?= (int) $row['partner_replies'] ?></td>
                            <td>
                                <?php if ((int) $row['conclusions']): ?>
                                    <span class="badge text-bg-success">Submitted</span>
                                <?php else: ?>
                                    <span class="badge text-bg-light text-secondary border">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$summary): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">There are no participations in the active forum yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<form method="get" action="<?= e(base_url('admin/responses')) ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Search student, content or email…" value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-4">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <option value="teacher" <?= ($filters['type'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher response</option>
                    <option value="partner" <?= ($filters['type'] ?? '') === 'partner' ? 'selected' : '' ?>>Partner reply</option>
                    <option value="conclusion" <?= ($filters['type'] ?? '') === 'conclusion' ? 'selected' : '' ?>>Conclusion</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <button class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold small">Response detail (<?= count($responses) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date / Time</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Content</th>
                    <?php if ($__isAdminRole): ?><th class="text-end">Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responses as $r): ?>
                    <?php
                    $__label = ['teacher' => 'Teacher', 'partner' => 'Reply', 'conclusion' => 'Conclusion'][$r['type']] ?? $r['type'];
                    $__badge = ['teacher' => 'info', 'partner' => 'secondary', 'conclusion' => 'warning'][$r['type']] ?? 'light';
                    $__target = $r['type'] === 'partner' ? (' → ' . e(trim($r['parent_fn'] . ' ' . $r['parent_ln']))) : '';
                    ?>
                    <tr>
                        <td class="text-nowrap text-muted"><?= e(pretty_datetime($r['created_at'])) ?></td>
                        <td>
                            <span class="fw-semibold"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span>
                            <div class="text-muted"><?= e($r['email']) ?> <?= !empty($r['salon_name']) ? '· ' . e($r['salon_name']) : '' ?></div>
                        </td>
                        <td><span class="badge text-bg-<?= $__badge ?>"><?= $__label ?></span><?= $__target ?></td>
                        <td class="text-secondary" style="max-width:420px;">
                            <?= e(mb_strimwidth($r['content'], 0, 160, '…')) ?>
                            <div class="text-muted mt-1"><?= e($r['forum_title'] ?? '') ?></div>
                        </td>
                        <?php if ($__isAdminRole): ?>
                            <td class="text-end text-nowrap">
                                <form method="post" action="<?= e(base_url('admin/responses/delete')) ?>" class="d-inline"
                                      onsubmit="return confirm('Delete this response (ID <?= (int) $r['id'] ?>)? Its replies will also be removed.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="response_id" value="<?= (int) $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$responses): ?>
                    <tr><td colspan="<?= $__isAdminRole ? 5 : 4 ?>" class="text-center text-muted py-4">No responses matched the filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>