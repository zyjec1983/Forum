<?php $activeNav = 'settings'; ?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Configuration</h1>
        <p class="text-muted small mb-0">Accepted email domains for student and teacher registration.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= e(base_url('admin/settings/save')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-check form-switch fs-5 mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="allow_any_domain"
                               name="allow_any_domain" value="1" <?= $anyDomain ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="allow_any_domain">
                            Accept any email domain
                        </label>
                        <div class="form-text">If activated, any domain (gmail.com, outlook.com, ...) is accepted at registration. Recommended to keep it off.</div>
                    </div>

                    <label class="form-label fw-semibold small" for="accepted_domains">
                        Accepted domains <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control font-monospace" id="accepted_domains" name="accepted_domains"
                              rows="5" required><?= e(implode("\n", $domains)) ?></textarea>
                    <div class="form-text">One domain per line, without the @ (e.g. <code>ecomundo.edu.ec</code>).</div>

                    <button type="submit" class="btn btn-primary fw-semibold mt-4">Save configuration</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm text-white bg-dark">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold text-uppercase">Current status</h2>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">
                        <span class="badge <?= $anyDomain ? 'text-bg-warning' : 'text-bg-success' ?> me-2"><?= $anyDomain ? 'Any domain' : 'Only accepted domains' ?></span>
                        <?= $anyDomain ? 'Any email domain will be able to create accounts.' : 'Only the configured domains can register.' ?>
                    </li>
                    <li class="mb-2">
                        <strong>Domains:</strong>
                        <?php if ($domains): ?>
                            <span class="text-info"><?= e(implode(', ', $domains)) ?></span>
                        <?php else: ?>
                            <span class="text-danger">none</span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <i class="bi bi-shield-check me-1"></i> The administrator account
                        (<strong><?= e(current_user()['email']) ?></strong>) always keeps full access regardless of these settings.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>