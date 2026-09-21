<?php $activeNav = 'dashboard'; ?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Dashboard</h1>
        <p class="text-muted small mb-0">Academic forum summary.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= (int) $stats['students'] ?></div>
                <div class="text-muted small">Students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= (int) $stats['responses'] ?></div>
                <div class="text-muted small">Participations</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= (int) $stats['conclusions'] ?></div>
                <div class="text-muted small">Conclusions</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= (int) $stats['attempts'] ?></div>
                <div class="text-muted small">Security events (today: <?= (int) $stats['today'] ?>)</div>
            </div>
        </div>
    </div>
</div>

<?php if ($forum): ?>
    <?php $__st = time_status($forum); ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold small">Currently active forum</div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h6 class="mb-1"><?= e($forum['title']) ?></h6>
                    <div class="small text-muted mb-2"><?= e($forum['subject']) ?></div>
                    <span class="badge <?= $__st === 'open' ? 'text-bg-success' : ($__st === 'expired' ? 'text-bg-danger' : 'text-bg-warning') ?>">
                        <?= $__st === 'open' ? 'Open' : ($__st === 'expired' ? 'Expired' : 'Not started') ?>
                    </span>
                </div>
                <div class="small">
                    <div>Opens: <strong><?= e(pretty_datetime($forum['open_at'])) ?></strong></div>
                    <div>Closes: <strong><?= e(pretty_datetime($forum['close_at'])) ?></strong></div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <a href="<?= e(base_url('admin/responses')) ?>" class="btn btn-sm btn-primary">View responses</a>
                <a href="<?= e(base_url('admin/logs')) ?>" class="btn btn-sm btn-outline-danger">View security events</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning shadow-sm small mb-0">
        There is no active forum. <a href="<?= e(base_url('admin/forum')) ?>">Create and activate a forum</a> to start participation.
    </div>
<?php endif; ?>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>