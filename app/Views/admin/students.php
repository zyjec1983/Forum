<?php $activeNav = 'students'; ?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Students</h1>
        <p class="text-muted small mb-0">Register students or manage their lock status from here.</p>
    </div>
    <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="collapse" data-bs-target="#addStudent">+ Register student</button>
</div>

<div class="collapse <?= isset($filters['show_form']) ? 'show' : '' ?>" id="addStudent">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="post" action="<?= e(base_url('admin/students/save')) ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="email@ecomundo.edu.ec">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="first_name" class="form-control form-control-sm" required placeholder="First name">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="last_name" class="form-control form-control-sm" required placeholder="First surname">
                    </div>
                    <div class="col-md-2">
                        <select name="salon_id" class="form-select form-select-sm" required>
                            <option value="">Classroom</option>
                            <?php foreach ($salones as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary w-100 fw-bold">Save</button>
                    </div>
                </div>
                <div class="form-text mt-1">An automatic initial password will be generated and shown after saving.</div>
            </form>
        </div>
    </div>
</div>

<form method="get" action="<?= e(base_url('admin/students')) ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by name, surname or email…" value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-8 col-md-4">
                <select name="salon_id" class="form-select form-select-sm">
                    <option value="">All classrooms</option>
                    <?php foreach ($salones as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (isset($filters['salon_id']) && (int) $filters['salon_id'] === (int) $s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2">
                <button class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold small">Registered students (<?= count($students) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Classroom</th>
                    <th>Failed attempts</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $st): ?>
                    <tr>
                        <td>
                            <span class="avatar avatar-xs avatar-slate me-2"><?= e(initials($st['first_name'] . ' ' . $st['last_name'])) ?></span>
                            <span class="fw-semibold"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></span>
                        </td>
                        <td><?= e($st['email']) ?></td>
                        <td><?= e($st['salon_name'] ?? '—') ?></td>
                        <td><?= (int) $st['failed_attempts'] ?>/<?= MAX_LOGIN_ATTEMPTS ?></td>
                        <td>
                            <?php if ((int) $st['locked']): ?>
                                <span class="badge text-bg-danger">Locked</span>
                            <?php else: ?>
                                <span class="badge text-bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" action="<?= e(base_url('admin/students/toggle')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $st['id'] ?>">
                                <button class="btn btn-sm <?= (int) $st['locked'] ? 'btn-outline-success' : 'btn-outline-danger' ?>">
                                    <?= (int) $st['locked'] ? 'Unlock' : 'Lock' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$students): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No students matched the filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>