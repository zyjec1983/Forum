<?php
/**
 * Academic forum controller.
 * Routes student participation and applies ALL security rules server-side as
 * well (one teacher response, one conclusion, time window, logging hack attempts).
 */
class ForumController extends Controller
{
    public function show(): void
    {
        require_login();
        $user     = current_user();
        $salonId  = (int) ($user['salon_id'] ?? 0);
        $assigned = Forum::forSalon($salonId);
        $active   = Forum::active();
        $activeId = $active ? (int) $active['id'] : 0;

        // Forum to display: an explicit id (if accessible) or the active forum
        // when it is assigned to the student's classroom.
        $forum      = null;
        $interactive = false;
        $reqId = (int) ($_GET['id'] ?? 0);

        if ($reqId) {
            $cand = Forum::find($reqId);
            if ($cand && !Forum::isAssignedTo($reqId, $salonId)) {
                flash_set('error', 'You do not have access to that forum.');
                redirect(base_url('forum'));
            }
            if ($cand) {
                $forum       = $cand;
                $interactive = (int) $cand['id'] === $activeId && (int) $cand['is_active'] === 1;
            }
        } else {
            if ($active && Forum::isAssignedTo($activeId, $salonId)) {
                $forum       = $active;
                $interactive = true;
            }
        }

        if (!$forum) {
            $this->view('forum/empty', [
                'user'           => $user,
                'assigned'       => $assigned,
                'activeForumId'  => $activeId,
                'currentForumId' => 0,
                'pageTitle'      => 'Forum',
            ]);
            return;
        }

        $cards = [];
        foreach (Response::teacherResponses((int) $forum['id']) as $response) {
            $cards[] = [
                'response' => $response,
                'replies'  => Response::partnerReplies((int) $response['id']),
            ];
        }

        $this->view('forum/index', [
            'user'           => $user,
            'forum'          => $forum,
            'cards'          => $cards,
            'assigned'       => $assigned,
            'activeForumId'  => $activeId,
            'currentForumId' => (int) $forum['id'],
            'interactive'    => $interactive,
            'hasTeacher'     => Response::hasTeacherResponse((int) $forum['id'], (int) $user['id']),
            'hasConclusion'  => Response::hasConclusion((int) $forum['id'], (int) $user['id']),
            'serverTime'     => date('Y-m-d H:i:s'),
            'status'         => time_status($forum),
            'remaining'      => window_progress($forum),
            'counts'         => Response::counts((int) $forum['id']),
            'pageTitle'      => $forum['title'],
        ]);
    }

    // ------------------------------------------------------------------
    // RESPOND TO THE TEACHER (only once)
    // ------------------------------------------------------------------
    public function respondTeacher(): void
    {
        require_login();
        csrf_check();
        $user = current_user();

        if ($user['role'] !== 'student') {
            SecurityLog::record($user['id'], 'hack_role', 'A non-student role tried to respond to the teacher', client_ip());
            json_out(['ok' => false, 'hack' => true, 'name' => $user['first_name'] . ' ' . $user['last_name'], 'message' => 'Action not allowed for your profile.']);
        }

        $forum = Forum::active();
        if (!$forum) {
            json_out(['ok' => false, 'message' => 'There is no active forum configured.']);
        }
        if (!$this->assertForumAccess($forum, $user)) {
            return;
        }

        $inWindow = $this->assertWindow($forum, $user);
        if (!$inWindow) {
            return;
        }

        if (Response::hasTeacherResponse((int) $forum['id'], (int) $user['id'])) {
            $this->hack('hack_duplicate_teacher', 'Attempt to submit a 2nd teacher response', $user);
        }

        $content = trim($_POST['content'] ?? '');
        if ($content === '' || mb_strlen(strip_tags($content)) < MIN_ANSWER_LEN) {
            json_out(['ok' => false, 'message' => 'Write a response with at least ' . MIN_ANSWER_LEN . ' characters.']);
        }

        $id = Response::create((int) $forum['id'], (int) $user['id'], 'teacher', $content, null);
        $response = Response::findById($id);
        $html = View::renderPartial('forum/_teacher_card', [
            'response' => $response,
            'replies'  => [],
            'isSelf'   => true,
            'canReply' => true,
        ]);
        json_out(['ok' => true, 'html' => $html]);
    }

    // ------------------------------------------------------------------
    // REPLY TO A PARTNER (WhatsApp / professional forum style)
    // ------------------------------------------------------------------
    public function respondPartner(): void
    {
        require_login();
        csrf_check();
        $user = current_user();

        if ($user['role'] !== 'student') {
            SecurityLog::record($user['id'], 'hack_role', 'A non-student role tried to reply', client_ip());
            json_out(['ok' => false, 'hack' => true, 'name' => $user['first_name'] . ' ' . $user['last_name'], 'message' => 'Action not allowed for your profile.']);
        }

        $forum = Forum::active();
        if (!$forum) {
            json_out(['ok' => false, 'message' => 'There is no active forum configured.']);
        }
        if (!$this->assertForumAccess($forum, $user)) {
            return;
        }

        $inWindow = $this->assertWindow($forum, $user);
        if (!$inWindow) {
            return;
        }

        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $parent   = Response::findById($parentId);
        // The target must be a teacher response within the active forum.
        if (!$parent || $parent['type'] !== 'teacher' || (int) $parent['forum_id'] !== (int) $forum['id']) {
            $this->hack('hack_invalid_parent', 'Reply to an invalid target (parent_id=' . $parentId . ')', $user);
        }

        $content = trim($_POST['content'] ?? '');
        if ($content === '' || mb_strlen(strip_tags($content)) < MIN_ANSWER_LEN) {
            json_out(['ok' => false, 'message' => 'Write your reply with at least ' . MIN_ANSWER_LEN . ' characters.']);
        }

        $id = Response::create((int) $forum['id'], (int) $user['id'], 'partner', $content, $parentId);
        // Load the newly created reply with the target student names
        $reply = Database::fetchOne(
            "SELECT r.*, u.first_name, u.last_name, pu.first_name AS parent_fn, pu.last_name AS parent_ln
             FROM responses r
             JOIN users u ON u.id = r.user_id
             LEFT JOIN responses pr ON pr.id = r.parent_id
             LEFT JOIN users pu ON pu.id = pr.user_id
             WHERE r.id = ? LIMIT 1",
            [$id]
        );
        $html = View::renderPartial('forum/_sub_reply', ['reply' => $reply, 'own' => true]);
        json_out(['ok' => true, 'html' => $html]);
    }

    // ------------------------------------------------------------------
    // FINAL CONCLUSION (only once)
    // ------------------------------------------------------------------
    public function saveConclusion(): void
    {
        require_login();
        csrf_check();
        $user = current_user();

        if ($user['role'] !== 'student') {
            SecurityLog::record($user['id'], 'hack_role', 'A non-student role tried to submit a conclusion', client_ip());
            json_out(['ok' => false, 'hack' => true, 'name' => $user['first_name'] . ' ' . $user['last_name'], 'message' => 'Action not allowed for your profile.']);
        }

        $forum = Forum::active();
        if (!$forum) {
            json_out(['ok' => false, 'message' => 'There is no active forum configured.']);
        }
        if (!$this->assertForumAccess($forum, $user)) {
            return;
        }

        $inWindow = $this->assertWindow($forum, $user);
        if (!$inWindow) {
            return;
        }

        if (Response::hasConclusion((int) $forum['id'], (int) $user['id'])) {
            $this->hack('hack_duplicate_conclusion', 'Attempt to submit a 2nd conclusion', $user);
        }

        $content = trim($_POST['content'] ?? '');
        if ($content === '' || mb_strlen(strip_tags($content)) < MIN_ANSWER_LEN) {
            json_out(['ok' => false, 'message' => 'Write your conclusion with at least ' . MIN_ANSWER_LEN . ' characters.']);
        }

        Response::create((int) $forum['id'], (int) $user['id'], 'conclusion', $content, null);
        json_out(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // SECURITY REPORT FROM THE BROWSER
    // Blocks of copy/cut/paste/select/screenshot/console.
    // ------------------------------------------------------------------
    public function reportSecurity(): void
    {
        require_login();
        csrf_check();
        $user   = current_user();
        $event  = substr(trim($_POST['event'] ?? ''), 0, 60);
        $detail = mb_substr(trim($_POST['detail'] ?? ''), 0, 255);

        $allowed = [
            'copy', 'cut', 'paste', 'select', 'contextmenu',
            'printscreen', 'devtools', 'drag',
        ];
        if (!in_array($event, $allowed, true) && strpos($event, 'hack_') !== 0) {
            SecurityLog::record($user['id'], 'hack_' . $event, 'Unknown security event sent', client_ip());
            json_out(['ok' => false]);
        }

        SecurityLog::record(
            $user['id'],
            'attempt_' . $event,
            $detail !== '' ? $detail : 'Blocked action: ' . SecurityLog::eventLabel('attempt_' . $event),
            client_ip()
        );
        json_out(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Private utilities
    // ------------------------------------------------------------------

    /** Rejects active-forum participation when the student's classroom is not assigned. */
    private function assertForumAccess(array $forum, array $user): bool
    {
        $salonId = (int) ($user['salon_id'] ?? 0);
        if ($salonId > 0 && !Forum::isAssignedTo((int) $forum['id'], $salonId)) {
            SecurityLog::record(
                $user['id'],
                'hack_role',
                'Attempt to participate in a forum not assigned to their classroom',
                client_ip()
            );
            json_out([
                'ok'      => false,
                'hack'    => true,
                'name'    => $user['first_name'] . ' ' . $user['last_name'],
                'message' => 'Action not allowed for your profile.',
            ]);
            return false;
        }
        return true;
    }

    /** Checks the time window and responds by blocking when necessary. */
    private function assertWindow(array $forum, array $user): bool
    {
        $status = time_status($forum);
        if ($status === 'open') {
            return true;
        }
        if ($status === 'expired') {
            SecurityLog::record($user['id'], 'time_block', 'Attempt to participate with the forum already expired', client_ip());
            json_out(['ok' => false, 'message' => expired_message($forum['close_at'])]);
        } else {
            SecurityLog::record($user['id'], 'time_not_started', 'Attempt to participate before the opening', client_ip());
            json_out(['ok' => false, 'message' => starts_in_message($forum['open_at'])]);
        }
        return false;
    }

    /** Blocks (hacking attempt) and generates the alert with the student name. */
    private function hack(string $event, string $detail, array $user): void
    {
        SecurityLog::record($user['id'], $event, $detail, client_ip());
        json_out([
            'ok'      => false,
            'hack'    => true,
            'name'    => $user['first_name'] . ' ' . $user['last_name'],
            'message' => 'You cannot perform this action (forum rule). Your attempt has been logged. Contact the teacher.',
        ]);
    }
}