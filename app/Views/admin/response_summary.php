<?php
/**
 * View: Participation summary of the ACTIVE forum, one block per course.
 * Students are listed per classroom BEFORE their responses/statistics, and the
 * courses never mix (use the course selector to focus a single classroom).
 * Requires: $salons, $salonFilter, $activeForum, $courses, $totals.
 */
$activeNav = 'summary';
$__isAdminRole = is_admin_user();
$__salons      = $salons ?? [];
$__salonFilter = (int) ($salonFilter ?? 0);
$__nameOf = function (int $id) use ($__salons) {
    foreach ($__salons as $__s) {
        if ((int) $__s['id'] === $id) {
            return $__s['name'];
        }
    }
    return '';
};
?>
<?php include APP_PATH . '/Views/admin/_admin_head.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Participation Summary<?= $activeForum ? ' · ' . e($activeForum['title']) : '' ?></h1>
        <p class="text-muted small mb-0">
            Every student of each course assigned to the forum<?= $activeForum ? ': &ldquo;' . e($activeForum['title']) . '&rdquo;' : '' ?>.
            Students appear before their statistics; courses are shown separately.
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($activeForum): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('forum?id=' . (int) $activeForum['id'])) ?>"><i class="bi bi-eye me-1"></i>Open forum (read-only)</a>
        <?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin/responses')) ?>"><i class="bi bi-list-ul me-1"></i>Response detail</a>
    </div>
</div>

<?php if (!$activeForum): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="fs-1 mb-3"><i class="bi bi-calendar2-x"></i></div>
            <h2 class="h6 fw-bold mb-2">There is no active forum</h2>
            <p class="text-secondary small mb-0">Activate a forum to review the participation summary.</p>
        </div>
    </div>
<?php else: ?>
    <!-- Course selector: keeps every course separate -->
    <form method="get" action="<?= e(base_url('admin/responses/summary')) ?>" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
            <label class="small fw-semibold text-secondary text-nowrap mb-0" for="summary-course">Course</label>
            <select name="salon" id="summary-course" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">All courses (<?= count($__salons) ?>)</option>
                <?php foreach ($__salons as $__s): ?>
                    <option value="<?= (int) $__s['id'] ?>" <?= $__salonFilter === (int) $__s['id'] ? 'selected' : '' ?>>
                        <?= e($__s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="small text-muted">
                Showing: <strong><?= $__salonFilter ? e($__nameOf($__salonFilter)) : 'all courses separately' ?></strong>
            </span>
        </div>
    </form>

    <!-- Totals of the current selection -->
    <div class="row g-2 mb-3">
        <?php
        $__cards = [
            ['Students',      $totals['students'],      'text-bg-primary'],
            ['Participated',  $totals['participated'],  'text-bg-success'],
            ['Without reply', $totals['pending'],       'text-bg-secondary'],
            ['To teacher',    $totals['teacher'],       'text-bg-info'],
            ['Partner replies', $totals['partner'],     'text-bg-dark'],
            ['Conclusions',   $totals['conclusion'],    'text-bg-warning'],
        ];
        ?>
        <?php foreach ($__cards as $__c): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-2 text-center">
                        <div class="fs-4 fw-bold lh-1"><?= (int) $__c[1] ?></div>
                        <div class="small text-muted text-truncate"><?= e($__c[0]) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$courses): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4 text-muted small">
                <?= $__salonFilter ? 'That course has no students yet.' : 'The active forum has no courses with students assigned.' ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    /* Group the flat rows per classroom so courses are never mixed. */
    $__byCourse = [];
    foreach ($courses as $__row) {
        $__byCourse[(int) $__row['salon_id']][] = $__row;
    }
    ?>

    <?php foreach ($__byCourse as $__cid => $__rows): ?>
        <?php
        $__students = count($__rows);
        $__t = $__p = $__c = $__part = 0;
        foreach ($__rows as $__row) {
            $__t += $__row['teacher_responses'];
            $__p += $__row['partner_replies'];
            $__c += $__row['conclusions'];
            if ($__row['teacher_responses'] || $__row['partner_replies'] || $__row['conclusions']) {
                $__part++;
            }
        }
        $__pct = $__students > 0 ? (int) round($__part * 100 / $__students) : 0;
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold small d-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard"></i>
                    <?= e($__rows[0]['salon_name']) ?>
                    <span class="badge rounded-pill text-bg-light text-secondary border"><?= $__students ?> student<?= $__students === 1 ? '' : 's' ?></span>
                </span>
                <span class="small text-muted">
                    Participated <strong><?= $__part ?>/<?= $__students ?></strong> (<?= $__pct ?>%)
                    · to teacher <strong><?= $__t ?></strong> · replies <strong><?= $__p ?></strong> · conclusions <strong><?= $__c ?></strong>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:34px;">#</th>
                            <th>Student</th>
                            <th class="text-center">Teacher response</th>
                            <th class="text-center">Partner replies</th>
                            <th class="text-center">Conclusion</th>
                            <th class="text-center">Status</th>
                            <th style="width:110px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($__rows as $__i => $__row): ?>
                            <?php
                            $__done = $__row['teacher_responses'] || $__row['partner_replies'] || $__row['conclusions'];
                            $__args = http_build_query(['salon' => (int) $__row['salon_id'], 'search' => $__row['last_name']]);
                            ?>
                            <tr>
                                <td class="text-muted"><?= $__i + 1 ?></td>
                                <td>
                                    <span class="avatar avatar-xs avatar-slate me-2"><?= e(initials($__row['first_name'] . ' ' . $__row['last_name'])) ?></span>
                                    <?= e($__row['last_name'] . ', ' . $__row['first_name']) ?>
                                    <div class="text-muted"><?= e($__row['email']) ?></div>
                                </td>
                                <td class="text-center"><?= (int) $__row['teacher_responses'] ?></td>
                                <td class="text-center"><?= (int) $__row['partner_replies'] ?></td>
                                <td class="text-center">
                                    <?php if ((int) $__row['conclusions']): ?>
                                        <span class="badge text-bg-success">Submitted</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-light text-secondary border">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($__done): ?>
                                        <span class="badge text-bg-success-subtle text-success-emphasis">Participated</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning-subtle text-warning-emphasis">No reply yet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin/responses?' . $__args)) ?>"
                                       title="Ver solo las respuestas de este alumno en este curso">View responses</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include APP_PATH . '/Views/admin/_admin_foot.php'; ?>