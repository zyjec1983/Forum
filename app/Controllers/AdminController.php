<?php
/**
 * Admin panel: forum management (time window), classrooms, students,
 * responses and security audit.
 */
class AdminController extends Controller
{
    private function guard(): void
    {
        require_admin();
    }

    public function dashboard(): void
    {
        $this->guard();
        $forum = Forum::active();
        $stats = [
            'students'    => User::studentsCount(),
            'responses'   => Response::total(),
            'conclusions' => Database::count("SELECT COUNT(*) AS c FROM responses WHERE type='conclusion'"),
            'attempts'    => SecurityLog::count(),
            'today'       => SecurityLog::countToday(),
        ];
        $this->view('admin/dashboard', [
            'forum'     => $forum,
            'stats'     => $stats,
            'pageTitle' => 'Admin Panel',
        ]);
    }

    // ------------------------------------------------------------------
    // FORUM MANAGEMENT
    // ------------------------------------------------------------------
    public function forumIndex(): void
    {
        $this->guard();
        $salones = Salon::all();
        $forums  = Forum::all();
        foreach ($forums as &$f) {
            $f['salon_ids'] = Forum::salonsOf((int) $f['id']);
        }
        unset($f);
        $this->view('admin/forum', [
            'forums'    => $forums,
            'active'    => Forum::active(),
            'salones'   => $salones,
            'pageTitle' => 'Forum Management',
        ]);
    }

    /** Reads + validates the classrooms checked in a forum form. */
    private function classroomsFromPost(): array
    {
        $ids = array_values(array_unique(array_map('intval', $_POST['salons'] ?? [])));
        $valid = [];
        foreach ($ids as $id) {
            if ($id > 0 && Salon::find($id)) {
                $valid[] = $id;
            }
        }
        return $valid;
    }

    public function forumCreate(): void
    {
        $this->guard();
        csrf_check();
        $title    = trim($_POST['title'] ?? '');
        $subject  = trim($_POST['subject'] ?? '');
        $question = trim($_POST['question'] ?? '');
        $open     = $_POST['open_at'] ?? '';
        $close    = $_POST['close_at'] ?? '';
        $classrooms = $this->classroomsFromPost();

        $errors = [];
        if ($title === '') $errors[] = 'The title is required.';
        if ($subject === '') $errors[] = 'The subject is required.';
        if (mb_strlen($question) < 10) $errors[] = 'Write the discussion question (minimum 10 characters).';
        if (!$classrooms) $errors[] = 'Assign the forum to at least one classroom.';
        if (!strtotime($open) || !strtotime($close)) {
            $errors[] = 'Set the opening and closing dates of the forum.';
        } elseif (strtotime($close) <= strtotime($open)) {
            $errors[] = 'The closing date must be after the opening date.';
        }

        if ($errors) {
            flash_set('error', implode('<br>', array_map('e', $errors)));
            redirect_back();
        }

        $id = Forum::create([
            'title'     => $title,
            'subject'   => $subject,
            'question'  => $question,
            'open_at'   => date('Y-m-d H:i:s', strtotime($open)),
            'close_at'  => date('Y-m-d H:i:s', strtotime($close)),
            'created_by' => (int) current_user()['id'],
        ]);
        Forum::setSalons($id, $classrooms);
        SecurityLog::record(current_user()['id'], 'forum_created', 'Forum created: ' . $title . ' (' . count($classrooms) . ' classroom(s))', client_ip());
        flash_set('success', 'Forum created. Remember: the participation window is <strong>'
            . e($open) . '</strong> to <strong>' . e($close) . '</strong>.');
        redirect(base_url('admin/forum'));
    }

    public function forumEdit(): void
    {
        $this->guard();
        csrf_check();
        $id      = (int) ($_POST['forum_id'] ?? 0);
        $forum   = Forum::find($id);
        if (!$forum) {
            flash_set('error', 'Forum not found.');
            redirect(base_url('admin/forum'));
        }
        $title    = trim($_POST['title'] ?? '');
        $subject  = trim($_POST['subject'] ?? '');
        $question = trim($_POST['question'] ?? '');
        $open     = $_POST['open_at'] ?? '';
        $close    = $_POST['close_at'] ?? '';
        $classrooms = $this->classroomsFromPost();

        $errors = [];
        if ($title === '') $errors[] = 'The title is required.';
        if ($subject === '') $errors[] = 'The subject is required.';
        if (mb_strlen($question) < 10) $errors[] = 'Write the discussion question (minimum 10 characters).';
        if (!$classrooms) $errors[] = 'Assign the forum to at least one classroom.';
        if (!strtotime($open) || !strtotime($close)) {
            $errors[] = 'Set the opening and closing dates of the forum.';
        } elseif (strtotime($close) <= strtotime($open)) {
            $errors[] = 'The closing date must be after the opening date.';
        }

        if ($errors) {
            flash_set('error', implode('<br>', array_map('e', $errors)));
            redirect(base_url('admin/forum'));
        }

        Forum::update($id, [
            'title'    => $title,
            'subject'  => $subject,
            'question' => $question,
            'open_at'  => date('Y-m-d H:i:s', strtotime($open)),
            'close_at' => date('Y-m-d H:i:s', strtotime($close)),
        ]);
        Forum::setSalons($id, $classrooms);
        SecurityLog::record(current_user()['id'], 'forum_edited', 'Forum edited: ' . $title . ' (' . count($classrooms) . ' classroom(s))', client_ip());
        flash_set('success', 'Forum <strong>' . e($title) . '</strong> was updated correctly.');
        redirect(base_url('admin/forum'));
    }

    public function forumActivate(): void
    {
        $this->guard();
        csrf_check();
        $id = (int) ($_POST['forum_id'] ?? 0);
        if (!Forum::exists($id)) {
            flash_set('error', 'Forum not found.');
            redirect(base_url('admin/forum'));
        }
        Forum::activate($id);
        flash_set('success', 'The forum is now active and visible to students.');
        redirect(base_url('admin/forum'));
    }

    public function forumReopen(): void
    {
        $this->guard();
        csrf_check();
        $id    = (int) ($_POST['forum_id'] ?? 0);
        $forum = Forum::find($id);
        if (!$forum) {
            flash_set('error', 'Forum not found.');
            redirect(base_url('admin/forum'));
        }
        $open  = $_POST['open_at'] ?? '';
        $close = $_POST['close_at'] ?? '';

        $errors = [];
        if (!strtotime($open) || !strtotime($close)) {
            $errors[] = 'Set the new opening and closing dates of the forum.';
        } elseif (strtotime($close) <= strtotime($open)) {
            $errors[] = 'The closing date must be after the opening date.';
        }
        if ($errors) {
            flash_set('error', implode('<br>', array_map('e', $errors)));
            redirect(base_url('admin/forum'));
        }

        Forum::updateWindow($id, date('Y-m-d H:i:s', strtotime($open)), date('Y-m-d H:i:s', strtotime($close)));
        SecurityLog::record(current_user()['id'], 'forum_reopened', 'Forum reopened: ' . $forum['title'], client_ip());
        flash_set('success', 'Forum reopened with a new time window: <strong>' . e($open) . '</strong> → <strong>' . e($close) . '</strong>.');
        redirect(base_url('admin/forum'));
    }

    // ------------------------------------------------------------------
    // CLASSROOMS
    // ------------------------------------------------------------------
    public function salones(): void
    {
        $this->guard();
        $this->view('admin/salones', [
            'salones'   => Salon::all(),
            'pageTitle' => 'Classrooms',
        ]);
    }

    public function salonesSave(): void
    {
        $this->guard();
        csrf_check();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash_set('error', 'The classroom name is required.');
            redirect_back();
        }
        $exists = Database::fetchOne("SELECT id FROM salones WHERE name = ? LIMIT 1", [$name]);
        if ($exists) {
            flash_set('error', 'That classroom already exists.');
            redirect_back();
        }
        Salon::create($name);
        SecurityLog::record(current_user()['id'], 'salon_created', 'Classroom created: ' . $name, client_ip());
        flash_set('success', 'Classroom <strong>' . e($name) . '</strong> added.');
        redirect(base_url('admin/salones'));
    }

    public function salonesDelete(): void
    {
        $this->guard();
        csrf_check();
        $id    = (int) ($_POST['salon_id'] ?? 0);
        $salon = Salon::find($id);
        if (!$salon) {
            flash_set('error', 'Classroom not found.');
            redirect(base_url('admin/salones'));
        }
        Salon::delete($id);
        SecurityLog::record(current_user()['id'], 'salon_deleted', 'Classroom deleted: ' . $salon['name'], client_ip());
        flash_set('success', 'Classroom deleted. Students were left without an assigned classroom.');
        redirect(base_url('admin/salones'));
    }

    // ------------------------------------------------------------------
    // STUDENTS
    // ------------------------------------------------------------------
    public function students(): void
    {
        $this->guard();
        $this->view('admin/students', [
            'students' => User::all([
                'salon_id' => $_GET['salon_id'] ?? '',
                'search'   => $_GET['search'] ?? '',
                'role'     => 'student',
            ]),
            'salones' => Salon::all(),
            'filters' => $_GET,
            'pageTitle' => 'Students',
        ]);
    }

    public function studentSave(): void
    {
        $this->guard();
        csrf_check();
        $email   = mb_strtolower(trim($_POST['email'] ?? ''));
        $fn      = trim($_POST['first_name'] ?? '');
        $ln      = trim($_POST['last_name'] ?? '');
        $salonId = (int) ($_POST['salon_id'] ?? 0);

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if ($fn === '' || $ln === '') $errors[] = 'First name and surname are required.';
        if (User::findByEmail($email)) $errors[] = 'That email already exists.';

        if ($errors) {
            flash_set('error', implode('<br>', array_map('e', $errors)));
            redirect(base_url('admin/students'));
        }

        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $id = User::create([
            'email'      => $email,
            'first_name' => $fn,
            'last_name'  => $ln,
            'salon_id'   => $salonId,
            'password'   => password_hash($random, PASSWORD_DEFAULT),
            'role'       => 'student',
        ]);
        SecurityLog::record($id, 'student_created', 'Student created by administrator: ' . $email, client_ip());
        flash_set('success', 'Student created. Initial password: <strong>' . e($random) . '</strong> (must be changed on first sign-in).');
        redirect(base_url('admin/students'));
    }

    public function studentToggle(): void
    {
        $this->guard();
        csrf_check();
        $id   = (int) ($_POST['user_id'] ?? 0);
        $user = User::findById($id);
        if (!$user || $user['role'] !== 'student') {
            flash_set('error', 'Student not found.');
            redirect(base_url('admin/students'));
        }
        $locked = User::toggleLock($id);
        SecurityLog::record($id, $locked ? 'student_locked' : 'student_unlocked', 'Student status changed by the administrator', client_ip());
        flash_set('success', $locked ? 'Student blocked.' : 'Student unblocked.');
        redirect(base_url('admin/students'));
    }

    // ------------------------------------------------------------------
    // AUDIT / RESPONSES
    // ------------------------------------------------------------------
    public function logs(): void
    {
        $this->guard();
        $this->view('admin/logs', [
            'logs'    => SecurityLog::all(['event' => $_GET['event'] ?? '', 'search' => $_GET['search'] ?? '']),
            'filters' => $_GET,
            'eventLabel' => [SecurityLog::class, 'eventLabel'],
            'pageTitle' => 'Security Log / Audit',
        ]);
    }

    public function responses(): void
    {
        $this->guard();
        $active = Forum::active();
        $this->view('admin/responses', [
            'responses' => Response::allFiltered(['type' => $_GET['type'] ?? '', 'search' => $_GET['search'] ?? '']),
            'summary'   => $active ? Response::partnerJoinedActions((int) $active['id']) : [],
            'filters'   => $_GET,
            'activeForum' => $active,
            'pageTitle' => 'Forum Responses',
        ]);
    }
}