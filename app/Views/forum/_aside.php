<?php
/**
 * Partial: sidebar with the forums assigned to the student's classroom.
 * Requires: $assigned (list of forums), $activeForumId, $currentForumId.
 */
$__assigned     = $assigned ?? [];
$__activeId     = (int) ($activeForumId ?? 0);
$__currentId    = (int) ($currentForumId ?? 0);
$__guest        = (current_user()['role'] ?? '') === 'guest';
$__staff        = in_array(current_user()['role'] ?? '', ['admin', 'teacher'], true);
$__forumCourses = $forumCourses ?? [];
$__courseCounts = $courseCounts ?? [];
$__salonFilter  = (int) ($salonFilter ?? 0);
?>
<aside>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold small d-flex align-items-center gap-2">
            <i class="bi bi-collection me-1"></i> <?= $__guest ? 'Classroom forums (view)' : 'My forums' ?>
            <span class="badge rounded-pill text-bg-light text-secondary border ms-auto"><?= count($__assigned) ?></span>
        </div>
        <?php if (!$__assigned): ?>
            <div class="card-body small text-muted"><?= $__staff ? 'You have not created any forums yet.' : 'No forums have been assigned to your classroom yet.' ?></div>
        <?php else: ?>
            <div class="list-group list-group-flush small">
                <?php foreach ($__assigned as $__af): ?>
                    <?php
                    $__st  = time_status($__af);
                    $__cur = (int) $__af['id'] === $__currentId;
                    $__act = (int) $__af['id'] === $__activeId;
                    // For staff the "active" shortcut always includes the id
                    // (they have no classroom assignment to resolve on /forum).
                    $__link = (!$__staff && $__act) ? base_url('forum') : base_url('forum?id=' . (int) $__af['id']);
                    ?>
                    <a href="<?= e($__link) ?>" class="list-group-item list-group-item-action px-3 py-2 <?= $__cur ? 'active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold lh-sm <?= $__cur ? 'text-white' : '' ?>"><?= e($__af['title']) ?></div>
                                <div class="small <?= $__cur ? 'text-white-50' : 'text-muted' ?>"><?= e($__af['subject']) ?></div>
                            </div>
                            <span class="badge <?= $__st === 'open' ? 'text-bg-success' : ($__st === 'expired' ? 'text-bg-light text-secondary border' : 'text-bg-warning') ?>">
                                <?= $__st === 'open' ? 'Open' : ($__st === 'expired' ? 'Closed' : 'Scheduled') ?>
                            </span>
                        </div>
                        <div class="small <?= $__cur ? 'text-white-50' : 'text-muted' ?> mt-1">
                            <?= e(pretty_datetime($__af['open_at'])) ?> → <?= e(pretty_datetime($__af['close_at'])) ?>
                        </div>
                        <?php if ($__act): ?>
                            <div class="small <?= $__cur ? 'text-white' : 'text-primary' ?> fw-semibold mt-1">
                                <i class="bi bi-star-fill me-1"></i> Active forum
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($__staff && !empty($__forumCourses) && $__currentId > 0): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold small d-flex align-items-center gap-2">
                <i class="bi bi-mortarboard me-1"></i> Courses
                <span class="badge rounded-pill text-bg-light text-secondary border ms-auto"><?= count($__forumCourses) ?></span>
            </div>
            <div class="list-group list-group-flush small">
                <a href="<?= e(base_url('forum?id=' . $__currentId)) ?>"
                   class="list-group-item list-group-item-action px-3 py-2 d-flex justify-content-between align-items-center <?= $__salonFilter === 0 ? 'active' : '' ?>">
                    <span class="fw-semibold <?= $__salonFilter === 0 ? 'text-white' : '' ?>">All courses</span>
                    <span class="badge rounded-pill text-bg-light text-secondary border"><?= array_sum($__courseCounts) ?></span>
                </a>
                <?php foreach ($__forumCourses as $__fc): ?>
                    <?php $__sel = (int) $__fc['id'] === $__salonFilter; ?>
                    <a href="<?= e(base_url('forum?id=' . $__currentId . '&salon=' . (int) $__fc['id'])) ?>"
                       class="list-group-item list-group-item-action px-3 py-2 d-flex justify-content-between align-items-center <?= $__sel ? 'active' : '' ?>">
                        <span class="fw-semibold <?= $__sel ? 'text-white' : '' ?>">
                            <i class="bi bi-people-fill me-1"></i><?= e($__fc['name']) ?>
                        </span>
                        <span class="badge rounded-pill <?= $__sel ? 'text-bg-light' : 'text-bg-light text-secondary border' ?>">
                            <?= (int) ($__courseCounts[(int) $__fc['id']] ?? 0) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</aside>