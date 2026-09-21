<?php
/**
 * Partial: a student participation card (teacher response) with its replies,
 * professional forum style.
 * Requires: $response (id, user_id, content, created_at, first_name, last_name, salon_name),
 *           $replies (list of replies), $isSelf, $canReply.
 */
$__owner   = ($response['first_name'] ?? '') . ' ' . ($response['last_name'] ?? '');
$isSelf    = $isSelf ?? false;
$canReply  = $canReply ?? true;
$__isAdmin = (current_user()['role'] ?? '') === 'admin';
$__userId  = (int) (current_user()['id'] ?? 0);
?>
<div class="card student-card border-0 shadow-sm mb-4 <?= $isSelf ? 'border-start border-4 border-primary' : '' ?>" data-response-id="<?= (int) $response['id'] ?>">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex gap-3">
            <div class="avatar <?= e(avatar_color($response['first_name'] ?? 'x')) ?>">
                <?= e(initials($__owner !== ' ' ? $__owner : '?')) ?>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h6 class="mb-0 fw-bold small"><?= e($__owner !== ' ' ? $__owner : 'Student') ?></h6>
                        <?php if ($isSelf): ?><span class="badge text-bg-primary">You</span><?php endif; ?>
                        <?php if (!empty($response['salon_name'])): ?>
                            <span class="badge text-bg-light text-secondary border"><?= e($response['salon_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted" title="<?= e(pretty_datetime($response['created_at'] ?? '')) ?>">
                        <?= e(time_ago($response['created_at'] ?? date('Y-m-d H:i:s'))) ?>
                    </small>
                </div>

                <p class="mb-0 mt-2 small text-body"><?= nl2br(e($response['content'] ?? '')) ?></p>

                <?php if (!$__isAdmin && $canReply): ?>
                    <div class="mt-2">
                        <button type="button" class="btn btn-link btn-sm p-0 small fw-semibold link-body-emphasis text-decoration-none k-reply"
                                onclick="Forum.togglePartner(this)">
                            Reply to a partner
                        </button>
                    </div>

                    <div class="partner-form d-none mt-2">
                        <div class="bg-body-tertiary border rounded p-3">
                            <textarea class="form-control form-control-sm partner-input" rows="2"
                                      placeholder="Write your reply to <?= e($__owner) ?>..." maxlength="2000"></textarea>
                            <div class="d-flex justify-content-end gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-light" onclick="Forum.cancelPartner(this)">Cancel</button>
                                <button type="button" class="btn btn-sm btn-dark"
                                        onclick="Forum.submitPartner(this, <?= (int) $response['id'] ?>)"
                                        data-target="<?= e($__owner) ?>">Reply</button>
                            </div>
                        </div>
                    </div>
                <?php elseif (!$__isAdmin): ?>
                    <div class="mt-2">
                        <span class="badge text-bg-light text-secondary border">Interaction closed</span>
                    </div>
                <?php endif; ?>

                <div class="sub-replies ps-0 ps-sm-4 mt-3">
                    <?php foreach ($replies ?? [] as $__rep): ?>
                        <?= View::renderPartial('forum/_sub_reply', [
                            'reply' => $__rep,
                            'own'   => (int) ($__rep['user_id'] ?? 0) === $__userId,
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>