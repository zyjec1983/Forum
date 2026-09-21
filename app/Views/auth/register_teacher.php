<?php $old = $old ?? []; ?>
<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="text-center mb-4">
                <div class="avatar avatar-lg avatar-dark mx-auto mb-2"><span>P</span></div>
                <h1 class="h4 fw-bold mb-1"><?= e(APP_NAME) ?></h1>
                <p class="text-muted small mb-0">Create your teacher account to manage your forums</p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-1">Teacher registration</h2>
                    <p class="text-muted small mb-4">As a teacher you can manage your classrooms, create forums and follow the responses of your students.</p>

                    <?php include APP_PATH . '/Views/shared/_flash.php'; ?>

                    <form method="post" action="<?= e(base_url('auth/register-teacher')) ?>" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Institutional email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="teacher@ecomundo.edu.ec"
                                   value="<?= e($old['email'] ?? '') ?>" required>
                            <div class="form-text">
                                <?php if (!empty($anyDomain)): ?>
                                    Any email domain is currently accepted.
                                <?php else: ?>
                                    Only emails with the domain <code><?= e(implode(', ', $domainsList)) ?></code> are accepted.
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">First name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" placeholder="e.g. Amelia"
                                       value="<?= e($old['first_name'] ?? '') ?>" required maxlength="60">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">First surname <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" placeholder="e.g. Rodriguez"
                                       value="<?= e($old['last_name'] ?? '') ?>" required maxlength="60">
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required minlength="6" autocomplete="new-password">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small">Confirm password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirm" class="form-control" placeholder="Repeat the password" required minlength="6" autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold mt-4 py-2">
                            Create teacher account
                        </button>
                    </form>

                    <div class="text-center mt-3 small">
                        Already have an account? <a href="<?= e(base_url('auth/login')) ?>">Sign in</a> ·
                        <a href="<?= e(base_url('auth/register')) ?>">Soy estudiante</a>
                    </div>
                </div>
            </div>

            <p class="text-center text-muted small mt-4 mb-0">Exclusive use for the institution's students and teachers.</p>
        </div>
    </div>
</div>

<?php include APP_PATH . '/Views/shared/_foot.php'; ?>