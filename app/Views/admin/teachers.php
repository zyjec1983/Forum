<?php $activeNav = 'teachers'; ?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Teachers</h1>
        <p class="text-muted small mb-0">Accounts that manage their own classrooms and forums.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Teacher</th>
                    <th>Email</th>
                    <th class="text-center">Classrooms</th>
                    <th class="text-center">Forums</th>
                    <th>Registered</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$teachers): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No teachers have been registered yet.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($teachers as $t): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar <?= e(avatar_color((string) $t['id'])) ?>">
                                    <?= e(initials($t['first_name'] . ' ' . $t['last_name'])) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= e($t['first_name'] . ' ' . $t['last_name']) ?></div>
                                    <div class="small text-muted"><?= $t['locked'] ? '<span class="badge text-bg-danger">Blocked</span>' : '<span class="badge text-bg-success">Active</span>' ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted"><?= e($t['email']) ?></td>
                        <td class="text-center"><?= (int) $t['students_count'] ?></td>
                        <td class="text-center"><?= (int) $t['forums_count'] ?></td>
                        <td class="text-muted small"><?= e(pretty_date((string) $t['created_at'])) ?></td>
                        <td class="text-end pe-3">
                            <form method="post" action="<?= e(base_url('admin/teachers/delete')) ?>" class="d-inline"
                                  onsubmit="return confirm('Delete the teacher <strong><?= e(addslashes($t['first_name'] . ' ' . $t['last_name'])) ?></strong>? Their classrooms and forums will also be deleted. This action cannot be undone. Continue?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $t['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>