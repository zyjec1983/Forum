<?php
/** HTML footer partial. Requires: $__scripts (array of js names in /public/js, optional). */
$__scripts = $scripts ?? [];
?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <?php foreach ($__scripts as $__s): ?>
        <script src="<?= e(asset('js/' . $__s . '.js')) ?>"></script>
    <?php endforeach; ?>
</body>
</html>