<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <div class="text-center mb-4">
                <div class="avatar avatar-lg avatar-dark mx-auto mb-2"><span>EC</span></div>
                <h1 class="h4 fw-bold mb-1"><?= e(APP_NAME) ?></h1>
                <p class="text-muted small mb-0">Sign in with your institutional email</p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php include APP_PATH . '/Views/shared/_flash.php'; ?>

                    <form method="post" action="<?= e(base_url('auth/login')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Institutional email</label>
                            <input type="email" name="email" class="form-control" placeholder="your.email@ecomundo.edu.ec"
                                   value="<?= e($_GET['email'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Sign in</button>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mt-3 small flex-wrap gap-2">
                        <a href="<?= e(base_url('auth/register')) ?>">Create account</a>
                        <a href="<?= e(base_url('auth/recover')) ?>" class="text-danger text-decoration-none">
                            <i class="bi bi-key"></i> Recover password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . '/Views/shared/_foot.php'; ?>