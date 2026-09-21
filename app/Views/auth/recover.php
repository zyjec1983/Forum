<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <div class="text-center mb-4">
                <div class="avatar avatar-lg avatar-dark mx-auto mb-2"><span>RC</span></div>
                <h1 class="h4 fw-bold mb-1">Recover password</h1>
                <p class="text-muted small mb-0">Enter your email to get a temporary access key</p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php include APP_PATH . '/Views/shared/_flash.php'; ?>

                    <?php if (!empty($recoverKey)): ?>
                        <div class="text-center py-2">
                            <p class="small text-muted mb-2">Temporary key generated for <code><?= e($email) ?></code>.</p>
                            <div class="alert alert-success py-3">
                                <div class="text-muted small mb-2">Your new random key (write it down manually):</div>
                                <div class="recover-key fw-bold fs-3 track"><?= e($recoverKey) ?></div>
                                <div class="text-muted small mt-2">Use it to sign in. It is recommended to change it afterwards.</div>
                            </div>
                            <a href="<?= e(base_url('auth/login?email=' . urlencode($email))) ?>" class="btn btn-success w-100 fw-semibold py-2">
                                Go to sign in
                            </a>
                            <div class="text-muted small mt-3">
                                Remember: with <strong>3 failed attempts</strong> your account gets blocked.
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="post" action="<?= e(base_url('auth/recover')) ?>" novalidate>
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Institutional email</label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="your.email@ecomundo.edu.ec"
                                       value="<?= e($_GET['email'] ?? '') ?>" required autofocus>
                                <div class="form-text">On submit, a random sample key will be generated and shown on screen.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Generate temporary key</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3 small">
                        <a href="<?= e(base_url('auth/login')) ?>">Back to sign in</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/Views/shared/_foot.php'; ?>