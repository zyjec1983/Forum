<?php
$__isStaff = in_array($user['role'] ?? '', ['admin', 'teacher'], true);
$__isAdmin = ($user['role'] ?? '') === 'admin';
$__openTs  = strtotime($forum['open_at']);
$__closeTs = strtotime($forum['close_at']);
$__interactive = ($interactive ?? false) && !$__isStaff;
?>
<?php include APP_PATH . '/Views/shared/_head.php'; ?>

<nav class="navbar navbar-expand navbar-dark app-navbar sticky-top shadow-sm">
    <div class="container-lg">
        <a class="navbar-brand fw-bold small d-flex align-items-center gap-2" href="<?= e(base_url('/')) ?>">
            <span class="avatar avatar-sm avatar-white shadow-sm"><span>EC</span></span>
            <?= e(APP_NAME) ?>
        </a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if ($__isStaff): ?>
                <a href="<?= e(base_url('admin')) ?>" class="btn btn-sm btn-outline-light"><?= $__isAdmin ? 'Admin Panel' : 'My panel' ?></a>
            <?php endif; ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <span class="avatar avatar-xs avatar-slate"><?= e(initials($user['first_name'] . ' ' . $user['last_name'])) ?></span>
                    <span class="d-none d-md-inline small fw-semibold"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-2 shadow" style="width: 280px;">
                    <li>
                        <div class="px-2 pb-2">
                            <div class="fw-bold small"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></div>
                            <div class="small text-muted"><?= e($user['email']) ?></div>
                            <?php if (!empty($user['salon_name'])): ?>
                                <span class="badge text-bg-light text-secondary border mt-1">Classroom <?= e($user['salon_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small" href="<?= e(base_url('auth/logout')) ?>"><i class="bi bi-box-arrow-right me-1"></i> Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="py-4">
    <div class="container-lg">
        <div class="row g-4">
            <div class="col-12 col-lg-3">
                <?= View::renderPartial('forum/_aside', [
                    'assigned'       => $assigned ?? [],
                    'activeForumId'  => $activeForumId ?? 0,
                    'currentForumId' => $currentForumId ?? 0,
                ]) ?>
            </div>

            <div class="col-12 col-lg-9">
        <?php include APP_PATH . '/Views/shared/_flash.php'; ?>

        <?php if (!$__interactive && !$__isStaff): ?>
            <div class="alert alert-info small py-2">
                <i class="bi bi-info-circle me-1"></i>
                This forum is shown <strong>read-only</strong>: only the currently active forum accepts participation.
            </div>
        <?php endif; ?>

        <!-- Time window banner -->
        <div id="forum-app"
             data-forum-id="<?= (int) $forum['id'] ?>"
             data-open="<?= (int) $__openTs ?>"
             data-close="<?= (int) $__closeTs ?>"
             data-server="<?= (int) (strtotime($serverTime)) ?>"
             data-status="<?= e($status) ?>"
             data-role="<?= e($user['role']) ?>"
             data-min-len="<?= MIN_ANSWER_LEN ?>">

            <div class="card border-0 shadow-sm mb-4 window-banner">
                <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small fw-semibold d-flex align-items-center gap-2">
                        <span id="window-badge" class="badge <?= $status === 'open' ? 'text-bg-success' : ($status === 'expired' ? 'text-bg-danger' : 'text-bg-warning') ?>">
                            <?= $status === 'open' ? 'Open' : ($status === 'expired' ? 'Expired' : 'Not started') ?>
                        </span>
                        <span class="text-muted" id="window-msg" data-remaining="<?= (int) $remaining ?>">
                            <?php if ($status === 'open'): ?>
                                Open for participation. Closes in <strong id="cd"></strong>
                            <?php elseif ($status === 'expired'): ?>
                                <?= e(expired_message($forum['close_at'])) ?>
                            <?php else: ?>
                                <?= e(starts_in_message($forum['open_at'])) ?>
                            <?php endif; ?>
                        </span>
                    </span>
                    <span class="small text-muted" title="Interaction is only possible inside the time range">
                        <?= e(pretty_datetime($forum['open_at'])) ?> → <?= e(pretty_datetime($forum['close_at'])) ?>
                    </span>
                </div>
            </div>

            <!-- Teacher question -->
            <header class="card border-0 shadow-sm mb-4 teacher-question">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3 flex-wrap gap-2">
                        <span class="badge rounded-pill text-bg-primary-subtle text-primary fw-bold text-uppercase">Mandatory Forum</span>
                        <span class="small text-secondary fw-medium">Subject: <?= e($forum['subject']) ?></span>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="avatar avatar-md avatar-dark">DOC</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <h3 class="fw-bold small mb-0">
                                    <?php $__teacher = trim(($forum['first_name'] ?? '') . ' ' . ($forum['last_name'] ?? '')); ?>
                                    <?= e($__teacher !== '' ? $__teacher : 'Teacher') ?>
                                    <span class="small fw-normal text-secondary">(Teacher)</span>
                                </h3>
                                <span class="small text-secondary">Posted today</span>
                            </div>
                            <div class="bg-body-tertiary border rounded p-3 small text-body mt-2 leading-relaxed">
                                <p class="fw-semibold mb-2"><?= e($forum['title']) ?></p>
                                <p class="mb-0"><?= nl2br(e($forum['question'])) ?></p>
                            </div>

                            <?php if ($__interactive): ?>
                                <div class="pt-2">
                                    <?php if ($hasTeacher): ?>
                                        <span class="badge text-bg-success small py-2 px-3">&#10003; Response sent to the teacher (only 1 attempt allowed)</span>
                                    <?php else: ?>
                                        <button id="btn-response-teacher" type="button" class="btn btn-primary btn-sm fw-bold k-respond">
                                            Response to the teacher
                                        </button>
                                        <div id="teacher-form-box" class="d-none mt-3 p-3 border rounded-2 bg-info-subtle border-info-subtle">
                                            <textarea id="txt-teacher" rows="3" class="form-control form-control-sm mb-2"
                                                      placeholder="Write your argued response to the teacher (only 1 attempt allowed)..." maxlength="2000"></textarea>
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-sm btn-light" id="btn-teacher-cancel">Cancel</button>
                                                <button type="button" class="btn btn-sm btn-primary fw-bold" id="btn-teacher-send">Publish response to the teacher</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="pt-2">
                                    <span class="badge text-bg-light text-secondary border"><i class="bi bi-eye me-1"></i> Read-only forum</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Student contributions and interactions -->
            <section>
                <h4 class="small text-uppercase fw-bold text-secondary mb-3">Student Contributions and Interactions
                    <span class="badge rounded-pill text-bg-light text-secondary border ms-1"><?= count($cards) ?></span>
                </h4>
                <div id="forum-thread" class="d-flex flex-column gap-1">
                    <?php foreach ($cards as $card): ?>
                        <?= View::renderPartial('forum/_teacher_card', [
                            'response' => $card['response'],
                            'replies'  => $card['replies'],
                            'isSelf'   => (int) $card['response']['user_id'] === (int) $user['id'],
                            'canReply' => $__interactive,
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Mandatory final conclusion -->
            <?php if ($__interactive || $__isAdmin): ?>
            <section class="card border-0 shadow-sm mt-4" style="background-color:#fffbe6;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h3 class="mb-0 fw-bold small text-uppercase text-warning-emphasis">Final Conclusion</h3>
                    </div>
                    <p class="small text-body-secondary mb-3">
                        Each student account includes this final box to synthesize the definitive stance after
                        having interacted with the initial question and the classmates' responses.
                        <strong>Only one submission is allowed.</strong>
                    </p>

                    <?php if ($hasConclusion): ?>
                        <div class="alert alert-success mb-0 small">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Conclusion submitted correctly. You can no longer edit it (only 1 submission allowed).
                        </div>
                    <?php elseif (!$__isAdmin): ?>
                        <textarea id="txt-conclusion" rows="3" class="form-control mb-2" maxlength="2000"
                                  placeholder="Synthesize your final conclusions about <?= e($forum['title']) ?> here..."></textarea>
                        <div class="d-flex justify-content-end">
                            <button type="button" id="btn-conclusion" class="btn btn-warning btn-sm fw-bold">
                                Save Forum Conclusion
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0 small">(Administrator view: cannot submit conclusions)</div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <p class="text-center small text-secondary mt-4 mb-0">
                <i class="bi bi-shield-lock me-1"></i>
                This forum blocks copy, cut, paste, text selection and screenshots. Any attempt is logged with user, date/time and IP.
            </p>
            </div>
            </div>
        </div>
    </div>
</div>

<?php
$scripts = ['security', 'forum'];
include APP_PATH . '/Views/shared/_foot.php';
?>