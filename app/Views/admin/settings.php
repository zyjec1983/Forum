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

<?php if (is_admin_user()): ?>
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Login appearance</h2>
                        <p class="text-muted small mb-0">Change the login wallpaper and the tab icon (favicon).</p>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <?php foreach ([
                        ['target' => 'wallpaper', 'key' => 'login_wallpaper', 'title' => 'Login wallpaper', 'help' => 'Shown as the background of the sign-in page.', 'current' => $wallpaper],
                        ['target' => 'favicon', 'key' => 'favicon', 'title' => 'Tab icon (favicon)', 'help' => 'The small icon that appears on the browser tab.', 'current' => $favicon],
                    ] as $panel): ?>
                    <?php $active = false; foreach ($images as $__img) { if ($__img['name'] === $panel['current']) { $active = $__img; } } ?>
                    <div class="col-12 col-xl-6 mb-3">
                        <div class="h-100 border rounded-3 p-3 bg-white">
                            <h3 class="h6 fw-bold mb-1"><?= e($panel['title']) ?></h3>
                            <p class="text-muted small mb-3"><?= e($panel['help']) ?></p>

                            <div class="mb-3">
                                <?php if ($active): ?>
                                    <?php if ($panel['target'] === 'wallpaper'): ?>
                                        <div class="rounded border" style="height:110px;background:#0f172a url('<?= e($active['url']) ?>') center/cover no-repeat;"></div>
                                    <?php else: ?>
                                        <img src="<?= e($active['url']) ?>" alt="favicon" class="img-thumbnail" style="width:64px;height:64px;object-fit:contain;">
                                    <?php endif; ?>
                                    <div class="form-text">Current: <code><?= e($active['name']) ?></code> (<?= e($active['size']) ?>)</div>
                                <?php else: ?>
                                    <div class="text-muted small">Default appearance (no custom image).</div>
                                <?php endif; ?>
                            </div>

                            <form method="post" action="<?= e(base_url('admin/settings/upload-image')) ?>"
                                  enctype="multipart/form-data" class="mb-3">
                                <?= csrf_field() ?>
                                <input type="hidden" name="target" value="<?= e($panel['target']) ?>">
                                <div class="d-flex gap-2 align-items-start flex-wrap">
                                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,image/x-icon"
                                           class="form-control form-control-sm" style="max-width:260px" required>
                                    <button type="submit" class="btn btn-sm btn-dark">Upload from device</button>
                                </div>
                                <div class="form-text mt-1">Max 4 MB. JPG, PNG, WEBP, GIF, SVG.</div>
                            </form>

                            <form method="post" action="<?= e(base_url('admin/settings/pick-image')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="target" value="<?= e($panel['target']) ?>">
                                <label class="form-label small fw-semibold mb-1">Or choose from the images folder (/public/img)</label>
                                <?php if ($images): ?>
                                    <div class="d-flex flex-wrap gap-2 mb-2" style="max-height:150px;overflow:auto;">
                                        <?php foreach ($images as $__img): ?>
                                            <label class="text-center p-1 rounded border <?= $__img['name'] === $panel['current'] ? 'border-primary bg-primary bg-opacity-10' : '' ?>"
                                                   style="width:78px;cursor:pointer;" title="<?= e($__img['name']) ?>">
                                                <input type="radio" name="image" value="<?= e($__img['name']) ?>"
                                                       class="form-check-input d-block mx-auto mb-1"
                                                       <?= $__img['name'] === $panel['current'] ? 'checked' : '' ?>>
                                                <?php if ($panel['target'] === 'favicon'): ?>
                                                    <img src="<?= e($__img['url']) ?>" alt="" class="rounded border" style="width:34px;height:34px;object-fit:contain;background:#fff;">
                                                <?php else: ?>
                                                    <span class="d-inline-block rounded bg-secondary" style="width:34px;height:34px;background-image:url('<?= e($__img['url']) ?>');background-size:cover;background-position:center;"></span>
                                                <?php endif; ?>
                                                <span class="d-block text-truncate small" style="font-size:.65rem;"><?= e($__img['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Apply selected</button>
                                <?php else: ?>
                                    <div class="text-muted small">No images in the folder yet. Upload one from your device first.</div>
                                <?php endif; ?>
                            </form>

                            <?php if ($active): ?>
                                <form method="post" action="<?= e(base_url('admin/settings/remove-image')) ?>" class="mt-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="target" value="<?= e($panel['target']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore default
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>