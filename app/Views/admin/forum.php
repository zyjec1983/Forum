<?php $activeNav = 'forum';
$__isAdminRole = is_admin_user();
?>
<?php $salonNames = []; foreach (($salones ?? []) as $__s) { $salonNames[(int) $__s['id']] = $__s['name']; } ?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Forum Management</h1>
        <p class="text-muted small mb-0">Create forums, set the time window and assign them to the classrooms that will participate. Only the active forum accepts participation.</p>
    </div>
    <span class="badge <?= $active && time_status($active) === 'open' ? 'text-bg-success' : 'text-bg-light text-secondary border' ?>">
        Active forum status: <?= $active ? (mb_strlen($active['title']) > 40 ? e(mb_substr($active['title'], 0, 40)) . '…' : e($active['title'])) : '—' ?>
    </span>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold small">Create new forum</div>
            <div class="card-body">
                <form method="post" action="<?= e(base_url('admin/forum/create')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Title</label>
                        <input type="text" name="title" class="form-control form-control-sm" required maxlength="190" placeholder="Forum: PISA and AI">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Subject</label>
                        <input type="text" name="subject" class="form-control form-control-sm" required maxlength="190" placeholder="Sociology of Education">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Discussion question</label>
                        <textarea name="question" rows="4" class="form-control form-control-sm" required placeholder="Write the statement the students will respond to..."></textarea>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Opening</label>
                            <input type="datetime-local" name="open_at" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Closing</label>
                            <input type="datetime-local" name="close_at" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="form-text small mb-2">
                        The forum can be viewed at any time, but interaction is only possible within this time range.
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Assigned classrooms</label>
                        <div class="classroom-checkbox-group">
                            <?php foreach ($salones as $__s): ?>
                                <label class="form-check form-check-inline mb-1">
                                    <input class="form-check-input" type="checkbox" name="salons[]" value="<?= (int) $__s['id'] ?>">
                                    <span class="form-check-label small"><?= e($__s['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text small">Only students of the selected classrooms will see this forum.</div>
                    </div>
                    <button class="btn btn-primary btn-sm w-100 fw-bold">Create and activate forum</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold small">Existing forums</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Forum</th>
                            <th>Time window</th>
                            <th>Interactions</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forums as $f): ?>
                            <?php $__st = time_status($f); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($f['title']) ?></div>
                                    <div class="text-muted"><?= e($f['subject']) ?> · Created <?= e(pretty_datetime($f['created_at'])) ?></div>
                                    <div class="mt-1">
                                        <?php foreach ($f['salon_ids'] as $__sid): ?>
                                            <span class="badge text-bg-light text-secondary border"><?= e($salonNames[(int) $__sid] ?? '—') ?></span>
                                        <?php endforeach; ?>
                                        <?php if (!$f['salon_ids']): ?>
                                            <span class="badge text-bg-danger-subtle text-danger border">No classrooms assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <?= e(pretty_datetime($f['open_at'])) ?><br>
                                    <span class="text-muted">→ <?= e(pretty_datetime($f['close_at'])) ?></span>
                                </td>
                                <td><?= (int) $f['total_responses'] ?></td>
                                <td>
                                    <?php if ((int) $f['is_active']): ?>
                                        <span class="badge text-bg-success">Active</span>
                                        <span class="badge <?= $__st === 'open' ? 'text-bg-success' : ($__st === 'expired' ? 'text-bg-danger' : 'text-bg-warning') ?>">
                                            <?= $__st === 'open' ? 'Open' : ($__st === 'expired' ? 'Expired' : 'Pending') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-light text-secondary border">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="<?= (int) $f['id'] ?>"
                                            data-title="<?= e($f['title']) ?>"
                                            data-subject="<?= e($f['subject']) ?>"
                                            data-question="<?= e($f['question']) ?>"
                                            data-open="<?= e(date('Y-m-d\TH:i', strtotime($f['open_at']))) ?>"
                                            data-close="<?= e(date('Y-m-d\TH:i', strtotime($f['close_at']))) ?>"
                                            data-salons="<?= e(implode(',', $f['salon_ids'])) ?>">
                                        Edit
                                    </button>
                                    <?php if ((int) $f['is_active']): ?>
                                        <?php if ($__st === 'expired'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning fw-semibold"
                                                    data-bs-toggle="modal" data-bs-target="#reopenModal"
                                                    data-id="<?= (int) $f['id'] ?>"
                                                    data-open="<?= e(date('Y-m-d\TH:i', strtotime($f['open_at']))) ?>"
                                                    data-close="<?= e(date('Y-m-d\TH:i', strtotime($f['close_at']))) ?>">
                                                Re-open
                                            </button>
                                        <?php else: ?>
                                            <span class="badge text-bg-light text-secondary border"><?= $__st === 'not_started' ? 'Scheduled' : 'Live' ?></span>
                                        <?php endif; ?>
                                    <?php elseif (!(int) $f['is_active']): ?>
                                        <form method="post" action="<?= e(base_url('admin/forum/activate')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="forum_id" value="<?= (int) $f['id'] ?>">
                                            <button class="btn btn-sm btn-outline-primary">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($__isAdminRole): ?>
                                        <form method="post" action="<?= e(base_url('admin/forum/delete')) ?>" class="d-inline"
                                              onsubmit="return confirm('Delete the forum <?= e(addslashes($f['title'])) ?> along with all its responses? This action cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="forum_id" value="<?= (int) $f['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$forums): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No forums created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Re-open modal: only used when an active forum is expired -->
<div class="modal fade" id="reopenModal" tabindex="-1" aria-labelledby="reopenModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(base_url('admin/forum/reopen')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="forum_id" id="reopenForumId">
                <div class="modal-header">
                    <h1 class="modal-title fs-6 fw-bold" id="reopenModalLabel">Re-open forum</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        The participation window of this forum is already closed. Set a new opening and closing time so the students can participate again.
                    </p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Opening</label>
                            <input type="datetime-local" name="open_at" id="reopenOpen" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Closing</label>
                            <input type="datetime-local" name="close_at" id="reopenClose" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold">Re-open forum</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit modal: edit any forum (title, subject, question and time window) -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= e(base_url('admin/forum/edit')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="forum_id" id="editForumId">
                <div class="modal-header">
                    <h1 class="modal-title fs-6 fw-bold" id="editModalLabel">Edit forum</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control form-control-sm" required maxlength="190">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Subject</label>
                        <input type="text" name="subject" id="editSubject" class="form-control form-control-sm" required maxlength="190">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Discussion question</label>
                        <textarea name="question" id="editQuestion" rows="4" class="form-control form-control-sm" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Opening</label>
                            <input type="datetime-local" name="open_at" id="editOpen" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Closing</label>
                            <input type="datetime-local" name="close_at" id="editClose" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small fw-semibold mb-1">Assigned classrooms</label>
                        <div class="classroom-checkbox-group">
                            <?php foreach ($salones as $__s): ?>
                                <label class="form-check form-check-inline mb-1">
                                    <input class="form-check-input edit-salon" type="checkbox" name="salons[]" value="<?= (int) $__s['id'] ?>">
                                    <span class="form-check-label small"><?= e($__s['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('reopenModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('reopenForumId').value = btn.getAttribute('data-id');
            document.getElementById('reopenOpen').value = btn.getAttribute('data-open');
            document.getElementById('reopenClose').value = btn.getAttribute('data-close');
        });
    }
    var edit = document.getElementById('editModal');
    if (edit) {
        edit.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('editForumId').value = btn.getAttribute('data-id');
            document.getElementById('editTitle').value = btn.getAttribute('data-title');
            document.getElementById('editSubject').value = btn.getAttribute('data-subject');
            document.getElementById('editQuestion').value = btn.getAttribute('data-question');
            document.getElementById('editOpen').value = btn.getAttribute('data-open');
            document.getElementById('editClose').value = btn.getAttribute('data-close');
            var salons = (btn.getAttribute('data-salons') || '').split(',').map(function (v) { return v.trim(); });
            edit.querySelectorAll('.edit-salon').forEach(function (cb) {
                cb.checked = salons.indexOf(cb.value) !== -1;
            });
        });
    }
})();
</script>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>