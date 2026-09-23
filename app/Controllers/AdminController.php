<?php
/**
 * Panel for the administrator and teachers:
 * forum management (time window), classrooms, students, responses, security
 * audit, teacher accounts (admin only) and global settings (domains).
 *
 * Role rules:
 *  - A teacher only sees the classrooms / forums / students they own.
 *  - Destructive actions (delete, audit cleaning) are exclusive to the admin.
 */
class AdminController extends Controller
{
    private function guard(): void
    {
        require_staff();
    }

    private function isAdmin(): bool
    {
        return is_admin_user();
    }

    private function uid(): int
    {
        return (int) (current_user()['id'] ?? 0);
    }

    /** Teacher id owning the current scope (null = admin sees everything). */
    private function scope(): ?int
    {
        return $this->isAdmin() ? null : $this->uid();
    }

    private function assertAdmin(): void
    {
        if (!$this->isAdmin()) {
            http_response_code(403);
            exit('Only the administrator can perform this action. This attempt has been logged.');
        }
    }

    public function dashboard(): void
    {
        $this->guard();
        $forum = Forum::active();

        if ($this->scope() !== null) {
            $teacherId  = $this->scope();
            $salones    = Salon::all($teacherId);
            $students   = User::all(['role' => 'student', 'teacher_id' => $teacherId]);
            $forums     = Forum::all($teacherId);
            $studentIds = array_column($students, 'id');

            $stats = [
                'students'    => count($students),
                'responses'   => Response::total($teacherId),
                'conclusions' => 0,
                'attempts'    => SecurityLog::count(array_merge([$teacherId], $studentIds)),
                'today'       => SecurityLog::countToday(array_merge([$teacherId], $studentIds)),
                'forums'      => count($forums),
                'salones'     => count($salones),
            ];
            foreach ($forums as $f) {
                $stats['conclusions'] += (int) Database::count(
                    "SELECT COUNT(*) AS c FROM responses WHERE type='conclusion' AND forum_id = ?",
                    [(int) $f['id']]
                );
            }
            $pageTitle = 'Teacher Panel';
        } else {
            $stats = [
                'students'    => User::studentsCount(),
                'responses'   => Response::total(),
                'conclusions' => Database::count("SELECT COUNT(*) AS c FROM responses WHERE type='conclusion'"),
                'attempts'    => SecurityLog::count(),
                'today'       => SecurityLog::countToday(),
                'forums'      => (int) Database::count("SELECT COUNT(*) AS c FROM forums"),
                'salones'     => (int) Database::count("SELECT COUNT(*) AS c FROM salones"),
            ];
            $pageTitle = 'Admin Panel';
        }

        $this->view('admin/dashboard', [
            'forum'     => $forum,
            'stats'     => $stats,
            'pageTitle' => $pageTitle,
        ]);
    }

    // ------------------------------------------------------------------
    // FORUM MANAGEMENT
    // ------------------------------------------------------------------
    public function forumIndex(): void
    {
        $this->guard();
        $scope  = $this->scope();
        $salones = Salon::all($scope);
        $forums  = Forum::all($scope);
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

    /** Reads + validates the classrooms checked in a forum form, scoped for teachers. */
    private function classroomsFromPost(): array
    {
        $ids   = array_values(array_unique(array_map('intval', $_POST['salons'] ?? [])));
        $valid = [];
        foreach ($ids as $id) {
            if ($id <= 0) {
                continue;
            }
            $salon = Salon::find($id);
            if (!$salon) {
                continue;
            }
            if ($this->scope() !== null && !Salon::ownedBy($id, $this->scope())) {
                continue;
            }
            $valid[] = $id;
        }
        return $valid;
    }

    private function ownsForum(array $forum): bool
    {
        $scope = $this->scope();
        return $scope === null || (int) $forum['created_by'] === $scope;
    }

    public function forumCreate(): void
    {
        $this->guard();
        csrf_check();
        $title      = trim($_POST['title'] ?? '');
        $subject    = trim($_POST['subject'] ?? '');
        $question   = trim($_POST['question'] ?? '');
        $open       = $_POST['open_at'] ?? '';
        $close      = $_POST['close_at'] ?? '';
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
            'created_by' => $this->uid(),
        ]);
        Forum::setSalons($id, $classrooms);
        SecurityLog::record($this->uid(), 'forum_created', 'Forum created: ' . $title . ' (' . count($classrooms) . ' classroom(s))', client_ip());
        flash_set('success', 'Forum created. Remember: the participation window is <strong>'
            . e($open) . '</strong> to <strong>' . e($close) . '</strong>.');
        redirect(base_url('admin/forum'));
    }

    public function forumEdit(): void
    {
        $this->guard();
        csrf_check();
        $id    = (int) ($_POST['forum_id'] ?? 0);
        $forum = Forum::find($id);
        if (!$forum || !$this->ownsForum($forum)) {
            flash_set('error', 'Forum not found.');
            redirect(base_url('admin/forum'));
        }
        $title      = trim($_POST['title'] ?? '');
        $subject    = trim($_POST['subject'] ?? '');
        $question   = trim($_POST['question'] ?? '');
        $open       = $_POST['open_at'] ?? '';
        $close      = $_POST['close_at'] ?? '';
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
        SecurityLog::record($this->uid(), 'forum_edited', 'Forum edited: ' . $title . ' (' . count($classrooms) . ' classroom(s))', client_ip());
        flash_set('success', 'Forum <strong>' . e($title) . '</strong> was updated correctly.');
        redirect(base_url('admin/forum'));
    }

    public function forumActivate(): void
    {
        $this->guard();
        csrf_check();
        $id = (int) ($_POST['forum_id'] ?? 0);
        $forum = Forum::find($id);
        if (!$forum || !$this->ownsForum($forum)) {
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
        if (!$forum || !$this->ownsForum($forum)) {
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
        SecurityLog::record($this->uid(), 'forum_reopened', 'Forum reopened: ' . $forum['title'], client_ip());
        flash_set('success', 'Forum reopened with a new time window: <strong>' . e($open) . '</strong> → <strong>' . e($close) . '</strong>.');
        redirect(base_url('admin/forum'));
    }

    public function forumDelete(): void
    {
        $this->guard();
        csrf_check();
        $id    = (int) ($_POST['forum_id'] ?? 0);
        $forum = Forum::find($id);
        if (!$forum || !$this->ownsForum($forum)) {
            flash_set('error', 'Forum not found.');
            redirect(base_url('admin/forum'));
        }
        Forum::deleteForum($id);
        SecurityLog::record($this->uid(), 'forum_deleted', 'Forum deleted: ' . $forum['title'], client_ip());
        flash_set('success', 'Forum <strong>' . e($forum['title']) . '</strong> deleted along with its responses.');
        redirect(base_url('admin/forum'));
    }

    // ------------------------------------------------------------------
    // CLASSROOMS
    // ------------------------------------------------------------------
    public function salones(): void
    {
        $this->guard();
        $teacherId = $this->scope();
        $this->view('admin/salones', [
            'salones'   => Salon::all($teacherId),
            'teachers'  => $this->isAdmin() ? User::all(['role' => 'teacher']) : [],
            'pageTitle' => 'Classrooms',
        ]);
    }

    public function salonesSave(): void
    {
        $this->guard();
        csrf_check();
        $name   = trim($_POST['name'] ?? '');
        $owner  = (int) ($_POST['teacher_id'] ?? 0);
        if ($name === '') {
            flash_set('error', 'The classroom name is required.');
            redirect_back();
        }
        $exists = Database::fetchOne("SELECT id FROM salones WHERE name = ? LIMIT 1", [$name]);
        if ($exists) {
            flash_set('error', 'That classroom already exists.');
            redirect_back();
        }
        if (!$this->isAdmin()) {
            $owner = $this->uid();
        } elseif ($owner > 0) {
            $ownerUser = User::findById($owner);
            if (!$ownerUser || $ownerUser['role'] !== 'teacher') {
                $owner = null;
            }
        } else {
            $owner = null;
        }
        Salon::create($name, $owner);
        SecurityLog::record($this->uid(), 'salon_created', 'Classroom created: ' . $name, client_ip());
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
        if ($this->scope() !== null && !Salon::ownedBy($id, $this->scope())) {
            flash_set('error', 'You can only delete your own classrooms.');
            redirect(base_url('admin/salones'));
        }
        Salon::delete($id);
        SecurityLog::record($this->uid(), 'salon_deleted', 'Classroom deleted: ' . $salon['name'], client_ip());
        flash_set('success', 'Classroom deleted. Students were left without an assigned classroom.');
        redirect(base_url('admin/salones'));
    }

    // ------------------------------------------------------------------
    // STUDENTS
    // ------------------------------------------------------------------
    public function students(): void
    {
        $this->guard();
        $teacherId = $this->scope();
        $this->view('admin/students', [
            'students' => User::all([
                'salon_id'   => $_GET['salon_id'] ?? '',
                'search'     => $_GET['search'] ?? '',
                'role'       => 'student',
                'teacher_id' => $teacherId,
            ]),
            'salones'   => Salon::all($teacherId),
            'filters'   => $_GET,
            'pageTitle' => 'Students',
        ]);
    }

    private function ownsStudentSalon(array $user): bool
    {
        $scope = $this->scope();
        if ($scope === null) {
            return true;
        }
        $salonId = (int) $user['salon_id'];
        return $salonId > 0 && Salon::ownedBy($salonId, $scope);
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
        $salon = $salonId ? Salon::find($salonId) : null;
        if (!$salon) {
            $errors[] = 'Select a classroom for the student.';
        } elseif ($this->scope() !== null && !Salon::ownedBy($salonId, $this->scope())) {
            $errors[] = 'Select one of your own classrooms.';
        }
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
        SecurityLog::record($id, 'student_created', 'Student created: ' . $email, client_ip());
        flash_set('success', 'Student created. Initial password: <strong>' . e($random) . '</strong> (must be changed on first sign-in).');
        redirect(base_url('admin/students'));
    }

    public function studentToggle(): void
    {
        $this->guard();
        csrf_check();
        $id   = (int) ($_POST['user_id'] ?? 0);
        $user = User::findById($id);
        if (!$user || $user['role'] !== 'student' || !$this->ownsStudentSalon($user)) {
            flash_set('error', 'Student not found.');
            redirect(base_url('admin/students'));
        }
        $locked = User::toggleLock($id);
        SecurityLog::record($id, $locked ? 'student_locked' : 'student_unlocked', 'Student status changed (' . ($this->isAdmin() ? 'admin' : 'teacher') . ')', client_ip());
        flash_set('success', $locked ? 'Student blocked.' : 'Student unblocked.');
        redirect(base_url('admin/students'));
    }

    public function studentDelete(): void
    {
        $this->guard();
        $this->assertAdmin();
        csrf_check();
        $id    = (int) ($_POST['user_id'] ?? 0);
        $user  = User::findById($id);
        if (!$user || $user['role'] !== 'student') {
            flash_set('error', 'Student not found.');
            redirect(base_url('admin/students'));
        }
        User::deleteUser($id);
        SecurityLog::record($this->uid(), 'student_deleted', 'Student deleted: ' . $user['email'], client_ip());
        flash_set('success', 'Student <strong>' . e($user['first_name'] . ' ' . $user['last_name']) . '</strong> deleted along with their data.');
        redirect(base_url('admin/students'));
    }

    // ------------------------------------------------------------------
    // INVITED ACCOUNTS (guest -> read-only access for parents/auditors)
    // ------------------------------------------------------------------
    public function guests(): void
    {
        $this->guard();
        $teacherId = $this->scope();
        $this->view('admin/guests', [
            'guests'    => User::all([
                'search'     => $_GET['search'] ?? '',
                'role'       => 'guest',
                'teacher_id' => $teacherId,
            ]),
            'salones'   => Salon::all($teacherId),
            'filters'   => $_GET,
            'pageTitle' => 'Guest Accounts',
        ]);
    }

    private function ownsGuestSalon(array $user): bool
    {
        $scope = $this->scope();
        if ($scope === null) {
            return true;
        }
        $salonId = (int) $user['salon_id'];
        return $salonId > 0 && Salon::ownedBy($salonId, $scope);
    }

    public function guestSave(): void
    {
        $this->guard();
        csrf_check();
        $email   = mb_strtolower(trim($_POST['email'] ?? ''));
        $fn      = trim($_POST['first_name'] ?? '');
        $ln      = trim($_POST['last_name'] ?? '');
        $pass    = (string) ($_POST['password'] ?? '');
        $salonId = (int) ($_POST['salon_id'] ?? 0);

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if ($fn === '') $errors[] = 'Enter a name for the guest (e.g. "Parent of Ana").';
        if (mb_strlen($pass) < 6) $errors[] = 'The password must be at least 6 characters long.';
        $salon = $salonId ? Salon::find($salonId) : null;
        if (!$salon) {
            $errors[] = 'Select the classroom the guest will be able to view.';
        } elseif ($this->scope() !== null && !Salon::ownedBy($salonId, $this->scope())) {
            $errors[] = 'Select one of your own classrooms.';
        }
        if (User::findByEmail($email)) $errors[] = 'That email already exists.';

        if ($errors) {
            flash_set('error', implode('<br>', array_map('e', $errors)));
            redirect(base_url('admin/guests'));
        }

        $id = User::create([
            'email'      => $email,
            'first_name' => $fn,
            'last_name'  => $ln,
            'salon_id'   => $salonId,
            'password'   => password_hash($pass, PASSWORD_DEFAULT),
            'role'       => 'guest',
        ]);
        SecurityLog::record($id, 'guest_created', 'Guest account created: ' . $email, client_ip());
        flash_set('success', 'Guest account <strong>' . e($email) . '</strong> created (classroom <strong>' . e($salon['name']) . '</strong>). Share the email and password with the parent or auditor.');
        redirect(base_url('admin/guests'));
    }

    public function guestToggle(): void
    {
        $this->guard();
        csrf_check();
        $id   = (int) ($_POST['user_id'] ?? 0);
        $user = User::findById($id);
        if (!$user || $user['role'] !== 'guest' || !$this->ownsGuestSalon($user)) {
            flash_set('error', 'Guest account not found.');
            redirect(base_url('admin/guests'));
        }
        $locked = User::toggleLock($id);
        SecurityLog::record($id, $locked ? 'guest_locked' : 'guest_unlocked', 'Guest status changed (' . ($this->isAdmin() ? 'admin' : 'teacher') . ')', client_ip());
        flash_set('success', $locked ? 'Guest account blocked.' : 'Guest account unblocked.');
        redirect(base_url('admin/guests'));
    }

    public function guestDelete(): void
    {
        $this->guard();
        csrf_check();
        $id   = (int) ($_POST['user_id'] ?? 0);
        $user = User::findById($id);
        if (!$user || $user['role'] !== 'guest' || !$this->ownsGuestSalon($user)) {
            flash_set('error', 'Guest account not found.');
            redirect(base_url('admin/guests'));
        }
        User::deleteUser($id);
        SecurityLog::record($this->uid(), 'guest_deleted', 'Guest account deleted: ' . $user['email'], client_ip());
        flash_set('success', 'Guest account <strong>' . e($user['email']) . '</strong> deleted.');
        redirect(base_url('admin/guests'));
    }

    // ------------------------------------------------------------------
    // TEACHERS (admin only)
    // ------------------------------------------------------------------
    public function teachers(): void
    {
        $this->guard();
        $this->assertAdmin();
        $teachers = User::all(['role' => 'teacher']);
        foreach ($teachers as &$t) {
            $t['students_count'] = (int) Database::count(
                "SELECT COUNT(*) AS c FROM users u JOIN salones s ON s.id = u.salon_id WHERE s.teacher_id = ? AND u.role = 'student'",
                [(int) $t['id']]
            );
            $t['forums_count'] = (int) Database::count(
                "SELECT COUNT(*) AS c FROM forums WHERE created_by = ?",
                [(int) $t['id']]
            );
        }
        unset($t);
        $this->view('admin/teachers', [
            'teachers'  => $teachers,
            'pageTitle' => 'Teachers',
        ]);
    }

    public function teacherDelete(): void
    {
        $this->guard();
        $this->assertAdmin();
        csrf_check();
        $id     = (int) ($_POST['user_id'] ?? 0);
        $user   = User::findById($id);
        if (!$user || $user['role'] !== 'teacher') {
            flash_set('error', 'Teacher not found.');
            redirect(base_url('admin/teachers'));
        }
        User::deleteUser($id);
        SecurityLog::record($this->uid(), 'teacher_deleted', 'Teacher deleted: ' . $user['email'], client_ip());
        flash_set('success', 'Teacher <strong>' . e($user['first_name'] . ' ' . $user['last_name']) . '</strong> deleted along with their classrooms and forums.');
        redirect(base_url('admin/teachers'));
    }

    // ------------------------------------------------------------------
    // SETTINGS (domains)
    // ------------------------------------------------------------------
    public function settings(): void
    {
        $this->guard();
        $this->view('admin/settings', [
            'anyDomain'   => any_domain_allowed(),
            'domains'     => allowed_domains(),
            'images'      => $this->mediaFiles(),
            'wallpaper'   => (string) Settings::get('login_wallpaper', ''),
            'favicon'     => (string) Settings::get('favicon', ''),
            'pageTitle'   => 'Configuration',
        ]);
    }

    public function settingsSave(): void
    {
        $this->guard();
        csrf_check();

        $anyDomain = isset($_POST['allow_any_domain']) ? '1' : '0';
        Settings::set('allow_any_domain', $anyDomain);

        // One domain per line; normalized without the leading '@'.
        $rawDomains = preg_split('/[\r\n,]+/', trim((string) ($_POST['accepted_domains'] ?? '')));
        $clean      = [];
        foreach ($rawDomains as $d) {
            $d = strtolower(trim($d));
            $d = ltrim($d, '@');
            if ($d === '' || strpos($d, '.') === false) {
                continue;
            }
            if (filter_var($d, FILTER_VALIDATE_DOMAIN) !== false && strlen($d) <= 250) {
                $clean[] = $d;
            }
        }
        $clean = array_values(array_unique($clean));
        Settings::set('accepted_domains', implode(',', $clean));

        SecurityLog::record($this->uid(), 'settings_updated', 'Registration settings updated (any domain: ' . e($anyDomain) . ')', client_ip());
        flash_set('success', 'Configuration saved. Accepted domains: <strong>' . ($clean ? e(implode(', ', $clean)) : 'none') . '</strong>.');
        redirect(base_url('admin/settings'));
    }

    // ------------------------------------------------------------------
    // SETTINGS (appearance: login wallpaper + favicon)
    // ------------------------------------------------------------------
    private const MEDIA_TARGETS = ['wallpaper' => 'login_wallpaper', 'favicon' => 'favicon'];
    private const IMG_EXTS      = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    private const MAX_IMG_BYTES = 4194304; // 4 MB

    private function imgDir(): string
    {
        $dir = PUBLIC_PATH . DS . 'img';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /** Images already stored in /public/img (for the folder picker). */
    private function mediaFiles(): array
    {
        $files = [];
        foreach (glob($this->imgDir() . DS . '*') ?: [] as $abs) {
            if (!is_file($abs)) {
                continue;
            }
            $name = basename($abs);
            if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::IMG_EXTS, true)) {
                continue;
            }
            $files[] = [
                'name' => $name,
                'url'  => asset('img/' . rawurlencode($name)),
                'size' => file_size_text((int) filesize($abs)),
            ];
        }
        usort($files, function ($a, $b) { return strcmp($a['name'], $b['name']); });
        return $files;
    }

    private function assertMediaTarget(string $target): string
    {
        if (!isset(self::MEDIA_TARGETS[$target])) {
            flash_set('error', 'Invalid appearance setting.');
            redirect(base_url('admin/settings'));
        }
        return self::MEDIA_TARGETS[$target];
    }

    /** Upload an image from the device to /public/img and apply it. */
    public function settingsUploadImage(): void
    {
        $this->guard();
        $this->assertAdmin();
        csrf_check();

        $key = $this->assertMediaTarget((string) ($_POST['target'] ?? ''));
        $file = $_FILES['image'] ?? null;

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) $file['size'] <= 0) {
            flash_set('error', 'No image was received. Choose a file first.');
            redirect(base_url('admin/settings'));
        }
        if ((int) $file['size'] > self::MAX_IMG_BYTES) {
            flash_set('error', 'The image exceeds the 4 MB limit.');
            redirect(base_url('admin/settings'));
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::IMG_EXTS, true)) {
            flash_set('error', 'Only JPG, PNG, WEBP, GIF or SVG images are allowed.');
            redirect(base_url('admin/settings'));
        }

        if ($ext === 'svg') {
            $content = strtolower((string) file_get_contents($file['tmp_name']));
            if (strpos($content, '<script') !== false || strpos($content, 'onload=') !== false
                || strpos($content, 'onerror=') !== false || strpos($content, 'javascript:') !== false) {
                flash_set('error', 'The SVG file contains unsafe content and was rejected.');
                redirect(base_url('admin/settings'));
            }
        } elseif (@getimagesize($file['tmp_name']) === false) {
            flash_set('error', 'The file is not a valid image.');
            redirect(base_url('admin/settings'));
        }

        $name = $key . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $this->imgDir() . DS . $name)) {
            flash_set('error', 'Could not save the image. Check the permissions of /public/img.');
            redirect(base_url('admin/settings'));
        }

        Settings::set($key, $name);
        SecurityLog::record($this->uid(), 'settings_updated', ($key === 'login_wallpaper' ? 'Login wallpaper' : 'Favicon') . ' changed to ' . $name, client_ip());
        flash_set('success', 'Image uploaded and applied.');
        redirect(base_url('admin/settings'));
    }

    /** Apply an image that already exists in /public/img. */
    public function settingsPickImage(): void
    {
        $this->guard();
        $this->assertAdmin();
        csrf_check();

        $key  = $this->assertMediaTarget((string) ($_POST['target'] ?? ''));
        $name = (string) ($_POST['image'] ?? '');

        if ($name === '' || basename($name) !== $name) {
            flash_set('error', 'Invalid image name.');
            redirect(base_url('admin/settings'));
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, self::IMG_EXTS, true)) {
            flash_set('error', 'That file is not an allowed image.');
            redirect(base_url('admin/settings'));
        }
        if (!is_file($this->imgDir() . DS . $name)) {
            flash_set('error', 'The image no longer exists in /public/img.');
            redirect(base_url('admin/settings'));
        }

        Settings::set($key, $name);
        SecurityLog::record($this->uid(), 'settings_updated', ($key === 'login_wallpaper' ? 'Login wallpaper' : 'Favicon') . ' changed to ' . $name, client_ip());
        flash_set('success', 'Image applied.');
        redirect(base_url('admin/settings'));
    }

    /** Restore the default appearance (no custom wallpaper / favicon). */
    public function settingsRemoveImage(): void
    {
        $this->guard();
        $this->assertAdmin();
        csrf_check();

        $key = $this->assertMediaTarget((string) ($_POST['target'] ?? ''));
        Settings::set($key, '');
        SecurityLog::record($this->uid(), 'settings_updated', ($key === 'login_wallpaper' ? 'Login wallpaper' : 'Favicon') . ' restored to default', client_ip());
        flash_set('success', 'Default restored.');
        redirect(base_url('admin/settings'));
    }

    // ------------------------------------------------------------------
    // AUDIT / RESPONSES
    // ------------------------------------------------------------------
    public function logs(): void
    {
        $this->guard();
        $userIds = null;
        if ($this->scope() !== null) {
            $teacherId = $this->scope();
            $students  = User::all(['role' => 'student', 'teacher_id' => $teacherId]);
            $guests    = User::all(['role' => 'guest', 'teacher_id' => $teacherId]);
            $userIds   = array_merge([$teacherId], array_column($students, 'id'), array_column($guests, 'id'));
        }
        $this->view('admin/logs', [
            'logs'    => SecurityLog::all([
                'event'    => $_GET['event'] ?? '',
                'search'   => $_GET['search'] ?? '',
                'user_ids' => $userIds,
            ]),
            'filters'    => $_GET,
            'scoped'     => $userIds !== null,
            'eventLabel' => [SecurityLog::class, 'eventLabel'],
            'pageTitle'  => 'Security Log / Audit',
        ]);
    }

    public function responses(): void
    {
        $this->guard();
        $active = Forum::active();
        $teacherScope = $this->scope();
        $summary = [];
        if ($active && ($teacherScope === null || (int) $active['created_by'] === $teacherScope)) {
            $summary = Response::partnerJoinedActions((int) $active['id']);
        }
        $rows = Response::studentActivity([
            'type'       => $_GET['type'] ?? '',
            'search'     => $_GET['search'] ?? '',
            'teacher_id' => $teacherScope,
        ]);
        $this->view('admin/responses', [
            'grouped'    => $this->responsesGrouped($rows),
            'summary'    => $summary,
            'filters'    => $_GET,
            'activeForum'=> $active,
            'pageTitle'  => 'Forum Responses',
        ]);
    }

    /** Groups participation rows per student, sorted last name / first name (case-insensitive). */
    private function responsesGrouped(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $r) {
            $key = (int) $r['user_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'user'      => [
                        'first_name' => $r['first_name'],
                        'last_name'  => $r['last_name'],
                        'email'      => $r['email'],
                        'salon_name' => $r['salon_name'] ?? '',
                    ],
                    'responses' => [],
                ];
            }
            $grouped[$key]['responses'][] = $r;
        }
        $names = [];
        foreach ($grouped as $key => $g) {
            $names[$key] = mb_strtoupper($g['user']['last_name'] . ' ' . $g['user']['first_name']);
        }
        asort($names, SORT_STRING);
        $sorted = [];
        foreach (array_keys($names) as $key) {
            $sorted[] = $grouped[$key];
        }
        return $sorted;
    }

    public function responsesExport(): void
    {
        $this->guard();
        csrf_check();
        $teacherScope = $this->scope();
        $rows = Response::studentActivity([
            'type'       => $_POST['type'] ?? '',
            'search'     => $_POST['search'] ?? '',
            'teacher_id' => $teacherScope,
        ]);
        $grouped      = $this->responsesGrouped($rows);
        $pdf          = new Pdf();
        $typeLabel    = [
            'teacher'    => 'Respuesta al docente',
            'partner'    => 'Respuesta a companero',
            'conclusion' => 'Conclusion final',
        ];

        $forums = [];
        foreach ($rows as $r) {
            if (!empty($r['forum_title'])) {
                $forums[$r['forum_title']] = 1;
            }
        }
        $multiForum = count($forums) > 1;
        $participants = count($grouped);
        $total = 0;
        foreach ($grouped as $g) {
            $total += count($g['responses']);
        }

        $pdf->writeBold('FORO ACADEMICO ECOMUNDO');
        $pdf->writeBold('REPORTE DE ACTIVIDAD DE LOS PARTICIPANTES');
        $pdf->write('Generado: ' . date('d/m/Y H:i') . ' (hora local de Ecuador)');
        $pdf->write('Ambito: ' . ($teacherScope === null ? 'TODOS LOS ESTUDIANTES' : 'MIS ESTUDIANTES'));
        if ($teacherScope !== null) {
            $teacher = User::findById($teacherScope);
            if ($teacher) {
                $pdf->write('Docente: ' . $teacher['first_name'] . ' ' . $teacher['last_name']);
            }
        }
        $pdf->write('Participantes: ' . $participants . '   Respuestas: ' . $total);
        $pdf->blank();

        foreach ($grouped as $g) {
            $user   = $g['user'];
            $count  = count($g['responses']);
            $pdf->rule('=');
            $pdf->writeBold('ESTUDIANTE: ' . mb_strtoupper($user['last_name']) . ', ' . $user['first_name']);
            $pdf->write('Correo: ' . $user['email']);
            if (!empty($user['salon_name'])) {
                $pdf->write('Salon: ' . $user['salon_name']);
            }
            $pdf->write('Total de respuestas: ' . $count);
            $pdf->rule('-');
            $i = 0;
            foreach ($g['responses'] as $r) {
                $i++;
                $label  = isset($typeLabel[$r['type']]) ? $typeLabel[$r['type']] : $r['type'];
                $target = '';
                if ($r['type'] === 'partner' && trim($r['parent_fn'] . ' ' . $r['parent_ln']) !== '') {
                    $target = ' | Respondio a: ' . trim($r['parent_fn'] . ' ' . $r['parent_ln']);
                }
                if ($multiForum && !empty($r['forum_title'])) {
                    $target .= ' | Foro: ' . $r['forum_title'];
                }
                $pdf->paragraph('[' . $i . '] ' . pretty_datetime($r['created_at']) . ' | ' . $label . $target);
                $pdf->paragraph((string) $r['content'], 4);
            }
            $pdf->blank();
        }
        $pdf->writeBold('FIN DEL REPORTE');

        $name = 'respuestas-' . date('Ymd-His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Cache-Control: no-store');
        echo $pdf->render('Reporte de respuestas', 'ECOMUNDO');
        exit;
    }

    public function responseDelete(): void
    {
        $this->guard();
        $this->assertAdmin();
        csrf_check();
        $id  = (int) ($_POST['response_id'] ?? 0);
        $row = Database::fetchOne("SELECT r.*, u.first_name, u.last_name, f.title AS forum_title
                                   FROM responses r
                                   JOIN users u ON u.id = r.user_id
                                   LEFT JOIN forums f ON f.id = r.forum_id
                                   WHERE r.id = ? LIMIT 1", [$id]);
        if (!$row) {
            flash_set('error', 'Response not found.');
            redirect(base_url('admin/responses'));
        }
        Response::delete($id);
        SecurityLog::record($this->uid(), 'response_deleted', 'Response ' . $id . ' deleted (author: ' . $row['email'] . ')', client_ip());
        flash_set('success', 'Response deleted.');
        redirect(base_url('admin/responses'));
    }

    public function logDelete(): void
    {
        $this->guard();
        csrf_check();
        $id  = (int) ($_POST['log_id'] ?? 0);
        $row = SecurityLog::find($id);
        if (!$row || !$this->ownsLog($row)) {
            flash_set('error', 'Log entry not found.');
            redirect(base_url('admin/logs'));
        }
        SecurityLog::delete($id);
        flash_set('success', 'Log entry deleted.');
        redirect(base_url('admin/logs'));
    }

    private function ownsLog(array $row): bool
    {
        $scope = $this->scope();
        if ($scope === null) {
            return true;
        }
        if ((int) ($row['user_id'] ?? 0) === $scope) {
            return true;
        }
        $ids = array_column(User::all(['role' => 'student', 'teacher_id' => $scope]), 'id');
        $ids = array_merge($ids, array_column(User::all(['role' => 'guest', 'teacher_id' => $scope]), 'id'));
        $ids = array_map('intval', $ids);
        return in_array((int) $row['user_id'], $ids, true);
    }

public function logsClear(): void
    {
        $this->guard();
        csrf_check();
        $scope = $this->scope();
        if ($scope === null) {
            SecurityLog::clear();
            $detail = 'Security log cleared by the administrator';
        } else {
            $ids = array_merge(
                [$scope],
                array_column(User::all(['role' => 'student', 'teacher_id' => $scope]), 'id'),
                array_column(User::all(['role' => 'guest', 'teacher_id' => $scope]), 'id')
            );
            SecurityLog::clearFor($ids);
            $detail = 'Security log cleared by the teacher (own scope)';
        }
        SecurityLog::record($this->uid(), 'logs_cleared', $detail, client_ip());
        flash_set('success', 'Security log cleared.');
        redirect(base_url('admin/logs'));
    }
}