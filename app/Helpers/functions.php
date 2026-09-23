<?php
/**
 * Global application helpers.
 */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return base_url('public/' . ltrim($path, '/'));
}

function url_current(): string
{
    return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'];
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function redirect_back(): void
{
    $back = $_SERVER['HTTP_REFERER'] ?? base_url('');
    redirect($back);
}

// ------------------------------------------------
// CSRF
// ------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf']) || !is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(419);
        exit('Your session expired or the security token does not match. Reload the page and try again.');
    }
}

// ------------------------------------------------
// Flash messages
// ------------------------------------------------
function flash_set(string $key, $value): void
{
    $_SESSION['flash'][$key] = $value;
}

function flash_get(string $key)
{
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

// ------------------------------------------------
// Network / Session / Access
// ------------------------------------------------
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        if (filter_var(trim($first), FILTER_VALIDATE_IP)) {
            $ip = trim($first);
        }
    }
    return $ip;
}

function client_user_agent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function require_login(): void
{
    if (!is_logged()) {
        redirect(base_url('auth/login'));
    }
}

function require_student(): void
{
    require_login();
    if ((current_user()['role'] ?? '') !== 'student') {
        http_response_code(403);
        exit('Action not allowed for administrators.');
    }
}

function require_admin(): void
{
    require_login();
    if ((current_user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. This area is exclusive to the forum administrator.');
    }
}

/**
 * Area for the administrator and the teachers.
 * Destructive actions within the panel are still restricted to the admin.
 */
function require_staff(): void
{
    require_login();
    if (!in_array(current_user()['role'] ?? '', ['admin', 'teacher'], true)) {
        http_response_code(403);
        exit('Access denied. This area is exclusive to the administrator and teachers.');
    }
}

function is_staff(): bool
{
    return in_array(current_user()['role'] ?? '', ['admin', 'teacher'], true);
}

function is_admin_user(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/** Where a logged user goes after sign-in / registration. */
function redirect_after_login(): void
{
    redirect(is_staff() ? base_url('admin') : base_url('forum'));
}

// ------------------------------------------------
// Registration domain settings
// ------------------------------------------------
function setting_get(string $key, ?string $default = null): ?string
{
    return Settings::get($key, $default);
}

function any_domain_allowed(): bool
{
    return Settings::anyDomainAllowed();
}

function allowed_domains(): array
{
    return Settings::domains();
}

function domain_allowed(string $email): bool
{
    return Settings::emailDomainAllowed($email);
}

// ------------------------------------------------
// Login wallpaper / favicon (admin appearance settings)
// ------------------------------------------------
/** URL of the configured login wallpaper, or null when using the default. */
function login_wallpaper_url(): ?string
{
    $file = (string) Settings::get('login_wallpaper', '');
    return $file !== '' ? asset('img/' . rawurlencode($file)) : null;
}

/** Human readable file size (for the image picker). */
function file_size_text(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024) . ' KB';
    return $bytes . ' B';
}

/** <link rel="icon"> for the configured favicon (or an empty string). */
function favicon_tag(): string
{
    $file = (string) Settings::get('favicon', '');
    if ($file === '') {
        return '';
    }
    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'jpg' => 'image/jpeg',
        'jpeg'=> 'image/jpeg',
        'webp'=> 'image/webp',
        'gif' => 'image/gif',
    ][$ext] ?? 'image/png';
    return '<link rel="icon" type="' . e($mime) . '" href="' . e(asset('img/' . rawurlencode($file))) . '">';
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------
// Formatting
// ------------------------------------------------
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $init  = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $init .= mb_substr($p, 0, 1);
    }
    return mb_strtoupper($init === '' ? '?' : $init);
}

function avatar_color(string $seed): string
{
    $palette = ['bg-emerald', 'bg-indigo', 'bg-teal', 'bg-violet', 'bg-rose', 'bg-sky', 'bg-orange', 'bg-cyan'];
    $idx     = crc32($seed) % count($palette);
    return $palette[$idx];
}

function pretty_datetime(string $datetime): string
{
    return date('d/m/Y H:i', strtotime($datetime));
}

function pretty_date(string $datetime): string
{
    return date('d/m/Y', strtotime($datetime));
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return $diff < 120 ? '1 min ago' : floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' h ago';
    $days = floor($diff / 86400);
    if ($days < 7)    return $days . ' days ago';
    if ($days < 30)   return floor($days / 7) . ' weeks ago';
    if ($days < 365)  return floor($days / 30) . ' months ago';
    return floor($days / 365) . ' years ago';
}

function diff_parts(int $seconds): array
{
    return [
        'days'    => intdiv($seconds, 86400),
        'hours'   => intdiv($seconds % 86400, 3600),
        'minutes' => intdiv($seconds % 3600, 60),
        'seconds' => $seconds % 60,
    ];
}

function expired_message(string $closeAt): string
{
    $p = diff_parts(max(0, time() - strtotime($closeAt)));
    return sprintf(
        'The forum expired %d day(s), %d hour(s), %d minute(s) and %d second(s) ago.',
        $p['days'], $p['hours'], $p['minutes'], $p['seconds']
    );
}

function starts_in_message(string $openAt): string
{
    $p = diff_parts(max(0, strtotime($openAt) - time()));
    return sprintf(
        'The forum has not opened yet. It will start in %d day(s), %d hour(s), %d minute(s) and %d second(s).',
        $p['days'], $p['hours'], $p['minutes'], $p['seconds']
    );
}

/**
 * Status of the time window of a forum:
 *  'not_started' => not open yet | 'open' => inside the range | 'expired' => already closed
 */
function time_status(array $forum): string
{
    $open  = strtotime($forum['open_at']);
    $close = strtotime($forum['close_at']);
    $now   = time();
    if ($now < $open) return 'not_started';
    if ($now > $close) return 'expired';
    return 'open';
}

// Seconds left in the window (negative if closed, 0 if not open yet)
function window_progress(array $forum): int
{
    $open  = strtotime($forum['open_at']);
    $close = strtotime($forum['close_at']);
    $now   = time();
    if ($now < $open) return 0;
    return $close - $now;
}