<?php
/**
 * Authentication: registration (student data capture), sign-in with blocking
 * after 3 failed attempts, and password recovery that shows a random sample key.
 */
class AuthController extends Controller
{
    // ------------------------------------------------------------------
    // REGISTRATION / DATA CAPTURE
    // ------------------------------------------------------------------
    public function showRegister(): void
    {
        if (is_logged()) {
            redirect(base_url('forum'));
        }
        $old    = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $this->view('auth/register', [
            'salones'  => Salon::all(),
            'old'      => $old,
            'pageTitle' => 'Student Registration',
        ]);
    }

    public function register(): void
    {
        csrf_check();
        $email   = mb_strtolower(trim($_POST['email'] ?? ''));
        $fn      = trim($_POST['first_name'] ?? '');
        $ln      = trim($_POST['last_name'] ?? '');
        $salonId = (int) ($_POST['salon_id'] ?? 0);
        $pass    = (string) ($_POST['password'] ?? '');
        $pass2   = (string) ($_POST['password_confirm'] ?? '');

        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The email entered is not valid.';
        } elseif (substr($email, -strlen('@' . ID_DOMAIN)) !== '@' . ID_DOMAIN) {
            $errors[] = 'You must use your institutional email (@' . ID_DOMAIN . ').';
        }

        if (!preg_match(NAME_PATTERN, $fn)) {
            $errors[] = 'Enter a valid first name (letters only).';
        }
        if (!preg_match(NAME_PATTERN, $ln)) {
            $errors[] = 'Enter a valid first surname (letters only).';
        }
        if (!$salonId || !Salon::find($salonId)) {
            $errors[] = 'Select the classroom you belong to.';
        }
        if (mb_strlen($pass) < 6) {
            $errors[] = 'The password must be at least 6 characters long.';
        } elseif ($pass !== $pass2) {
            $errors[] = 'Passwords do not match.';
        }
        if (User::findByEmail($email)) {
            $errors[] = 'This institutional email is already registered.';
        }

        if ($errors) {
            $_SESSION['old'] = $_POST;
            flash_set('error', implode('<br>', array_map('e', $errors)));
            redirect_back();
        }

        $userId = User::create([
            'email'      => $email,
            'first_name' => $fn,
            'last_name'  => $ln,
            'salon_id'   => $salonId,
            'password'   => password_hash($pass, PASSWORD_DEFAULT),
            'role'       => 'student',
        ]);

        $user = User::findById($userId);
        $_SESSION['user'] = $user;
        SecurityLog::record($userId, 'register', 'New student registration (' . $email . ')', client_ip());
        flash_set('success', 'Account created! Welcome <strong>' . e($fn . ' ' . $ln) . '</strong> to the academic forum.');
        redirect(base_url('forum'));
    }

    // ------------------------------------------------------------------
    // SIGN IN
    // ------------------------------------------------------------------
    public function showLogin(): void
    {
        if (is_logged()) {
            redirect(base_url('forum'));
        }
        $this->view('auth/login', ['pageTitle' => 'Sign In']);
    }

    public function login(): void
    {
        csrf_check();
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');

        $user = User::findByEmail($email);
        if (!$user) {
            SecurityLog::record(null, 'login_failed', 'Sign-in attempt with non-existent email: ' . $email, client_ip());
            flash_set('error', 'Incorrect email or password.');
            redirect_back();
        }

        if ((int) $user['locked']) {
            SecurityLog::record($user['id'], 'login_locked', 'Sign-in attempt with a blocked account', client_ip());
            flash_set('error', 'Your account is <strong>blocked</strong> after exceeding the ' . MAX_LOGIN_ATTEMPTS . ' failed attempts. Use the "Recover password" option.');
            redirect_back();
        }

        if (password_verify($pass, $user['password'])) {
            User::resetFailedAttempts($user['id']);
            $_SESSION['user'] = User::findById($user['id']);
            SecurityLog::record($user['id'], 'login', 'Successful sign-in', client_ip());
            flash_set('success', 'Welcome <strong>' . e($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) . '</strong>.');
            redirect($_SESSION['user']['role'] === 'admin' ? base_url('admin') : base_url('forum'));
        }

        $attempts = User::registerFailedAttempt($user['id']);
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            SecurityLog::record($user['id'], 'login_locked', 'Account blocked after reaching ' . MAX_LOGIN_ATTEMPTS . ' failed attempts', client_ip());
            flash_set('error', 'You have reached the maximum of ' . MAX_LOGIN_ATTEMPTS . ' failed attempts. Your account is now <strong>blocked</strong>. Use "Recover password".');
        } else {
            SecurityLog::record($user['id'], 'login_failed', 'Wrong password (attempt ' . $attempts . ' of ' . MAX_LOGIN_ATTEMPTS . ')', client_ip());
            flash_set('error', 'Incorrect email or password. Attempt <strong>' . $attempts . '</strong> of ' . MAX_LOGIN_ATTEMPTS . '.');
        }
        redirect_back();
    }

    // ------------------------------------------------------------------
    // RECOVER PASSWORD
    // ------------------------------------------------------------------
    public function showRecover(): void
    {
        if (is_logged()) {
            redirect(base_url('forum'));
        }
        $recoverKey = flash_get('recover_key');
        $this->view('auth/recover', [
            'pageTitle'  => 'Recover Password',
            'recoverKey' => $recoverKey,
            'email'      => $_GET['email'] ?? '',
        ]);
    }

    public function recover(): void
    {
        csrf_check();
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $user  = User::findByEmail($email);

        if (!$user) {
            flash_set('error', 'There is no account registered with that email in the forum.');
            redirect_back();
        }
        if ($user['role'] === 'admin') {
            flash_set('error', 'The administrator account does not use this recovery mechanism.');
            redirect_back();
        }

        // Generates a random sample key and shows it ONCE to the user.
        $newKey = strtoupper(bin2hex(random_bytes(4)));
        User::setPassword($user['id'], password_hash($newKey, PASSWORD_DEFAULT));
        User::resetFailedAttempts($user['id']);
        SecurityLog::record($user['id'], 'recover', 'Temporary key generated and shown to the user', client_ip());
        flash_set('recover_key', $newKey);
        redirect(base_url('auth/recover?email=' . urlencode($email)));
    }

    public function logout(): void
    {
        SecurityLog::record(current_user()['id'] ?? null, 'logout', 'Sign out', client_ip());
        unset($_SESSION['user']);
        redirect(base_url('auth/login'));
    }
}