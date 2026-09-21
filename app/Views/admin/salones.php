<?php $activeNav = 'salones';
$__isAdminRole = is_admin_user();
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Classrooms</h1>
        <p class="text-muted small mb-0"><?= $__isAdminRole ? 'Classrooms (9th "A", 9th "B", …) are filled from here and appear in both student registration and the forum header.' : 'Create the classrooms you supervise; students register selecting your classrooms.' ?></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold small">Add classroom</div>
            <div class="card-body">
                <form method="post" action="<?= e(base_url('admin/salones/save')) ?>">
                    <?= csrf_field() ?>
                    <label class="form-label small fw-semibold">Classroom name</label>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" name="name" class="form-control" required maxlength="80" placeholder='e.g. 9th "A"'>
                        <button class="btn btn-primary">Save</button>
                    </div>
                    <?php if ($__isAdminRole && $teachers): ?>
                        <label class="form-label small fw-semibold">Owner teacher</label>
                        <select name="teacher_id" class="form-select form-select-sm mb-2">
                            <option value="">(Unassigned)</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= e($t['first_name'] . ' ' . $t['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold small">Classroom list</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <?php if ($__isAdminRole): ?><th>Owner</th><?php endif; ?>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salones as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= e($s['name']) ?></td>
                                <?php if ($__isAdminRole): ?>
                                    <td class="text-muted">
                                        <?= $s['teacher_id'] ? e(trim($s['first_name'] . ' ' . ($s['last_name'] ?? ''))) : '<span class="badge text-bg-secondary">Unassigned</span>' ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-muted"><?= e(pretty_datetime($s['created_at'])) ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= e(base_url('admin/salones/delete')) ?>" class="d-inline"
                                          onsubmit="return confirm('Delete classroom <?= e(addslashes($s['name'])) ?>? Its students will no longer have a classroom.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="salon_id" value="<?= (int) $s['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$salones): ?>
                            <tr><td colspan="<?= $__isAdminRole ? 5 : 4 ?>" class="text-center text-muted py-4">No classrooms registered yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>