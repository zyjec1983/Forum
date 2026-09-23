<?php $activeNav = 'responses';
$__isAdminRole = is_admin_user();
$__typeLabel = ['teacher' => 'Teacher response', 'partner' => 'Reply', 'conclusion' => 'Conclusion'];
$__typeBadge = ['teacher' => 'info', 'partner' => 'secondary', 'conclusion' => 'warning'];
$__studentsCount = count($grouped);
$__responsesCount = 0;
foreach ($grouped as $__g) { $__responsesCount += count($__g['responses']); }
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Forum Responses</h1>
        <p class="text-muted small mb-0"><?= $__isAdminRole ? 'Student participation grouped by student, alphabetical, with details of every response.' : 'Participation of your students grouped alphabetically, with details of every response in your forums.' ?></p>
    </div>
    <form method="post" action="<?= e(base_url('admin/responses/export')) ?>" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($filters['type'] ?? '') ?>">
        <input type="hidden" name="search" value="<?= e($filters['search'] ?? '') ?>">
        <button class="btn btn-sm btn-success"><i class="bi bi-file-earmark-pdf me-1"></i>Export all to PDF</button>
    </form>
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
    <div class="card-header bg-white fw-bold small">Participation detail (<?= $__studentsCount ?> student<?= $__studentsCount === 1 ? '' : 's' ?> · <?= $__responsesCount ?> response<?= $__responsesCount === 1 ? '' : 's' ?>)</div>
    <div class="card-body p-0">
        <?php foreach ($grouped as $__g): $__user = $__g['user']; ?>
            <div class="border-bottom p-3">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="avatar avatar-sm avatar-slate"><?= e(initials($__user['first_name'] . ' ' . $__user['last_name'])) ?></span>
                    <div>
                        <span class="fw-semibold"><?= e($__user['last_name'] . ', ' . $__user['first_name']) ?></span>
                        <div class="text-muted small">
                            <?= e($__user['email']) ?>
                            <?php if (!empty($__user['salon_name'])): ?> · <?= e($__user['salon_name']) ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="badge text-bg-light text-secondary border ms-auto"><?= count($__g['responses']) ?> response<?= count($__g['responses']) === 1 ? '' : 's' ?></span>
                </div>
                <div class="list-group list-group-flush ms-4">
                    <?php foreach ($__g['responses'] as $__r):
                        $__label = $__typeLabel[$__r['type']] ?? $__r['type'];
                        $__badge = $__typeBadge[$__r['type']] ?? 'light';
                        $__target = $__r['type'] === 'partner' && trim($__r['parent_fn'] . ' ' . $__r['parent_ln']) !== ''
                            ? ' → ' . e(trim($__r['parent_fn'] . ' ' . $__r['parent_ln'])) : '';
                    ?>
                        <div class="list-group-item ps-0 pe-0 border-0 border-bottom d-flex gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="text-muted text-nowrap"><?= e(pretty_datetime($__r['created_at'])) ?></span>
                                    <span class="badge text-bg-<?= $__badge ?>"><?= e($__label) ?></span><?= $__target ?>
                                </div>
                                <div class="text-secondary small mt-1"><?= e($__r['content']) ?></div>
                                <?php if (!empty($__r['forum_title'])): ?>
                                    <div class="text-muted small"><?= e($__r['forum_title']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($__isAdminRole): ?>
                                <div class="text-end text-nowrap">
                                    <form method="post" action="<?= e(base_url('admin/responses/delete')) ?>" class="d-inline"
                                          onsubmit="return confirm('Delete this response (ID <?= (int) $__r['id'] ?>)? Its replies will also be removed.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="response_id" value="<?= (int) $__r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$__g['responses']): ?>
                        <div class="text-muted small ms-4">No responses.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$grouped): ?>
            <div class="text-center text-muted py-4 mb-0">No responses matched the filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>