<?php
$__error = flash_get('error');
$__ok    = flash_get('success');
?>
<?php if ($__error !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div class="small"><?= $__error ?></div>
        </div>
    </div>
<?php endif; ?>
<?php if ($__ok !== null): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <div class="small"><?= $__ok ?></div>
        </div>
    </div>
<?php endif; ?>