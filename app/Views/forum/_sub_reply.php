<?php
/**
 * Partial: a student's reply to another classmate (WhatsApp style).
 * Requires: $reply (with first_name, last_name, parent_fn, parent_ln, content, created_at, id, user_id) and $own.
 */
$__fullName = ($reply['first_name'] ?? '') . ' ' . ($reply['last_name'] ?? '');
$__target   = trim(($reply['parent_fn'] ?? '') . ' ' . ($reply['parent_ln'] ?? ''));
$own        = $own ?? false;
?>
<div class="sub-reply d-flex gap-2 <?= $own ? 'own' : '' ?>" data-reply-id="<?= (int) ($reply['id'] ?? 0) ?>">
    <div class="avatar avatar-xs <?= e(avatar_color($reply['first_name'] ?? 'x')) ?>">
        <?= e(initials($__fullName !== ' ' ? $__fullName : 'One')) ?>
    </div>
    <div class="chat-bubble flex-grow-1">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <span class="small fw-bold">
                <?= e($__fullName !== ' ' ? $__fullName : 'Student') ?>
                <?php if ($own): ?><span class="badge text-bg-primary">You</span><?php endif; ?>
                <?php if ($__target !== ''): ?>
                    <span class="fw-normal text-secondary">replied to <?= e($__target) ?></span>
                <?php endif; ?>
            </span>
            <small class="text-muted" title="<?= e(pretty_datetime($reply['created_at'] ?? '')) ?>">
                <?= e(time_ago($reply['created_at'] ?? date('Y-m-d H:i:s'))) ?>
            </small>
        </div>
        <p class="small mb-0 mt-1 text-body"><?= nl2br(e($reply['content'] ?? '')) ?></p>
    </div>
</div>