<?php
$__isAdmin = ($user['role'] ?? '') === 'admin';
$__hasAssigned = !empty($assigned);
?>
<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<nav class="navbar navbar-expand navbar-dark app-navbar sticky-top shadow-sm">
    <div class="container-lg">
        <a class="navbar-brand fw-bold small" href="<?= e(base_url('/')) ?>"><?= e(APP_NAME) ?></a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if ($__isAdmin): ?>
                <a href="<?= e(base_url('admin')) ?>" class="btn btn-sm btn-outline-light">Admin Panel</a>
            <?php endif; ?>
            <a href="<?= e(base_url('auth/logout')) ?>" class="btn btn-sm btn-light">Sign out</a>
        </div>
    </div>
</nav>

<div class="container-lg py-4">
    <div class="row g-4">
        <div class="col-12 col-lg-3">
            <?= View::renderPartial('forum/_aside', [
                'assigned'       => $assigned ?? [],
                'activeForumId'  => $activeForumId ?? 0,
                'currentForumId' => $currentForumId ?? 0,
            ]) ?>
        </div>

        <div class="col-12 col-lg-9">
            <?php include APP_PATH . '/Views/shared/_flash.php'; ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="fs-1 mb-3"><i class="bi bi-calendar2-x"></i></div>
                    <h1 class="h4 fw-bold mb-2">No active forum for you right now</h1>
                    <?php if ($__isAdmin): ?>
                        <p class="text-secondary small mb-4">Create a forum, assign it to one or more classrooms and open its participation window.</p>
                        <a href="<?= e(base_url('admin/forum')) ?>" class="btn btn-primary btn-sm fw-bold">Create / activate forum</a>
                    <?php elseif ($__hasAssigned): ?>
                        <p class="text-secondary small mb-4">
                            The forums of your classroom are not configured as active yet. Use the <strong>My forums</strong>
                            list to view their schedules. Come back later or ask your teacher.
                        </p>
                    <?php else: ?>
                        <p class="text-secondary small mb-4">
                            No forum has been assigned to your classroom yet. Come back later or ask your teacher.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$scripts = ['security'];
include APP_PATH . '/Views/shared/_foot.php';
?>