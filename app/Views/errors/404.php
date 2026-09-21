<?php $pageTitle = $pageTitle ?? 'Page not found'; ?>
<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<div class="container py-5 text-center">
    <div class="fs-1 mb-2">🔍</div>
    <h1 class="h4 fw-bold">Page not found (404)</h1>
    <p class="text-muted small mb-4">The requested route does not exist in the forum.</p>
    <a href="<?= e(is_logged() ? base_url('forum') : base_url('')) ?>" class="btn btn-primary btn-sm">Go back</a>
</div>

<?php include APP_PATH . '/Views/shared/_foot.php'; ?>