<?php $activeNav = 'guests';
$__isAdminRole = is_admin_user();
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Guest Accounts</h1>
        <p class="text-muted small mb-0">Create read-only accounts so parents or auditors can view the activity of a classroom's forums.</p>
    </div>
    <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="collapse" data-bs-target="#addGuest">+ Create guest account</button>
</div>

<div class="collapse <?= isset($filters['show_form']) ? 'show' : '' ?>" id="addGuest">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="post" action="<?= e(base_url('admin/guests/save')) ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="Guest email (fictitious is fine)">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="first_name" class="form-control form-control-sm" required placeholder="Name (e.g. Parent of Ana)">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Surname (optional)">
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password" class="form-control form-control-sm" required placeholder="Password (min 6 chars)">
                    </div>
                    <div class="col-md-2">
                        <select name="salon_id" class="form-select form-select-sm" required>
                            <option value="">Classroom</option>
                            <?php foreach ($salones as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-primary w-100 fw-bold">Save</button>
                    </div>
                </div>
                <div class="form-text mt-1">The guest only <strong>views</strong> the forums of that classroom in read-only mode (no participation, no admin panel). Share the email and password with the parent or auditor.</div>
            </form>
        </div>
    </div>
</div>

<form method="get" action="<?= e(base_url('admin/guests')) ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by name or email…" value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-4 col-md-2">
                <button class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold small">Guest accounts (<?= count($guests) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Classroom</th>
                    <th>Failed attempts</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guests as $g): ?>
                    <tr>
                        <td>
                            <span class="avatar avatar-xs avatar-slate me-2"><?= e(initials($g['first_name'] . ' ' . $g['last_name'])) ?></span>
                            <span class="fw-semibold"><?= e($g['first_name'] . ' ' . $g['last_name']) ?></span>
                        </td>
                        <td><?= e($g['email']) ?></td>
                        <td><?= e($g['salon_name'] ?? '—') ?></td>
                        <td><?= (int) $g['failed_attempts'] ?>/<?= MAX_LOGIN_ATTEMPTS ?></td>
                        <td>
                            <?php if ((int) $g['locked']): ?>
                                <span class="badge text-bg-danger">Locked</span>
                            <?php else: ?>
                                <span class="badge text-bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <form method="post" action="<?= e(base_url('admin/guests/toggle')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $g['id'] ?>">
                                <button class="btn btn-sm <?= (int) $g['locked'] ? 'btn-outline-success' : 'btn-outline-danger' ?>">
                                    <?= (int) $g['locked'] ? 'Unlock' : 'Lock' ?>
                                </button>
                            </form>
                            <form method="post" action="<?= e(base_url('admin/guests/delete')) ?>" class="d-inline"
                                  onsubmit="return confirm('Delete the guest account <?= e(addslashes($g['email'])) ?>? The parent or auditor will lose access. This action cannot be undone.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $g['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$guests): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No guest accounts found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>