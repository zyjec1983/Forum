<?php
/** Admin panel header: navbar + aside. Requires $activeNav and $pageTitle. */
$__active = $activeNav ?? '';
$__role   = current_user()['role'] ?? '';
$__isAdminRole = $__role === 'admin';
?>
<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top shadow-sm">
    <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold small" href="<?= e(base_url('admin')) ?>">⚙ <?= e(APP_NAME) ?></a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <span class="badge bg-light text-dark small"><?= $__isAdminRole ? 'Administrator' : 'Teacher' ?></span>
            <a href="<?= e(base_url('forum')) ?>" class="btn btn-sm btn-outline-light">View forum</a>
            <a href="<?= e(base_url('/')) ?>" class="btn btn-sm btn-outline-light">Go to home</a>
            <a href="<?= e(base_url('auth/logout')) ?>" class="btn btn-sm btn-light">Sign out</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-3 py-3">
    <div class="row g-3">
        <aside class="col-12 col-lg-3 col-xxl-2">
            <div class="card border-0 shadow-sm admin-aside">
                <div class="card-body p-2">
                    <div class="d-flex flex-column flex-lg-column gap-1">
                        <a href="<?= e(base_url('admin')) ?>" class="admin-link <?= $__active === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                        <a href="<?= e(base_url('admin/forum')) ?>" class="admin-link <?= $__active === 'forum' ? 'active' : '' ?>"><i class="bi bi-chat-square-text me-2"></i>Forum Management</a>
                        <a href="<?= e(base_url('admin/salones')) ?>" class="admin-link <?= $__active === 'salones' ? 'active' : '' ?>"><i class="bi bi-mortarboard me-2"></i>Classrooms</a>
                        <a href="<?= e(base_url('admin/students')) ?>" class="admin-link <?= $__active === 'students' ? 'active' : '' ?>"><i class="bi bi-people me-2"></i>Students</a>
                        <a href="<?= e(base_url('admin/responses')) ?>" class="admin-link <?= $__active === 'responses' ? 'active' : '' ?>"><i class="bi bi-chat-left-dots me-2"></i>Forum Responses</a>
                        <a href="<?= e(base_url('admin/logs')) ?>" class="admin-link <?= $__active === 'logs' ? 'active' : '' ?>"><i class="bi bi-shield-lock me-2"></i>Security / Audit</a>
                        <?php if ($__isAdminRole): ?>
                            <a href="<?= e(base_url('admin/teachers')) ?>" class="admin-link <?= $__active === 'teachers' ? 'active' : '' ?>"><i class="bi bi-person-workspace me-2"></i>Teachers</a>
                        <?php endif; ?>
                        <a href="<?= e(base_url('admin/settings')) ?>" class="admin-link <?= $__active === 'settings' ? 'active' : '' ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
                    </div>
                </div>
            </div>
        </aside>
        <main class="col-12 col-lg-9 col-xxl-10">
            <?php include APP_PATH . '/Views/shared/_flash.php'; ?>