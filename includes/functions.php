<?php
/**
 * Shared helper functions used by every page.
 */
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

/* ---------- Output and URLs ---------- */

/** Escape a value for safe output in HTML. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Build a link inside the project. External links and anchors are returned unchanged. */
function url(string $path = ''): string
{
    if (preg_match('~^(https?:)?//|^(mailto|tel):|^#~i', $path)) {
        return $path;
    }
    return BASE_URL . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- Database shortcuts (always prepared statements) ---------- */

function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row ?: null;
}

function db_val(string $sql, array $params = [])
{
    return db_run($sql, $params)->fetchColumn();
}

/* ---------- Site settings (content managed by the admin) ---------- */

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db_all('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = (string)$row['svalue'];
        }
    }
    return ($cache[$key] ?? '') !== '' ? $cache[$key] : $default;
}

/* ---------- CSRF protection ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/** Stops the request if the form token is missing or wrong. */
function csrf_check(): void
{
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Your session has expired. Please go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------- Flash messages ---------- */

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function flashes(): array
{
    $list = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $list;
}

/* ---------- Authentication and roles ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = db_one("SELECT * FROM users WHERE id = ? AND status = 'active'", [$_SESSION['user_id']]);
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function has_role(string ...$roles): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role'], $roles, true);
}

/** Where each role lands after signing in. */
function home_for(array $user): string
{
    return $user['role'] === 'tourist' ? 'tourist/index.php' : 'admin/index.php';
}

function require_login(): void
{
    if (!current_user()) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('Please sign in to continue.', 'info');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        exit('Access denied. You do not have permission to open this page.');
    }
}

/* ---------- Formatting ---------- */

function money($usd): string
{
    return '$' . number_format((float)$usd, 2);
}

/** Approximate value in Liberian dollars, using the rate set by the admin. */
function lrd($usd): string
{
    return 'L$' . number_format((float)$usd * (float)setting('usd_to_lrd', '190'));
}

function nice_date(?string $date, string $format = 'j M Y'): string
{
    return $date ? date($format, strtotime($date)) : '';
}

function excerpt(?string $text, int $length = 120): string
{
    $text = trim((string)$text);
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length)) . '…' : $text;
}

function status_badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

function stars($rating): string
{
    $rating = (int)round((float)$rating);
    return '<span class="stars" aria-label="' . $rating . ' out of 5 stars">'
         . str_repeat('★', $rating) . '<span class="stars-off">' . str_repeat('★', 5 - $rating) . '</span></span>';
}

/**
 * Turn plain text written by the admin into safe HTML.
 * Blank line = new paragraph, "## " = heading, "- " = bullet point.
 */
function rich_text(?string $text): string
{
    $html = '';
    $list = false;
    foreach (preg_split('/\R/', trim((string)$text)) as $line) {
        $line = trim($line);
        $isItem = str_starts_with($line, '- ');
        if ($list && !$isItem) {
            $html .= '</ul>';
            $list = false;
        }
        if ($line === '') {
            continue;
        }
        if (str_starts_with($line, '## ')) {
            $html .= '<h2>' . e(substr($line, 3)) . '</h2>';
        } elseif ($isItem) {
            if (!$list) {
                $html .= '<ul>';
                $list = true;
            }
            $html .= '<li>' . e(substr($line, 2)) . '</li>';
        } else {
            $html .= '<p>' . e($line) . '</p>';
        }
    }
    return $html . ($list ? '</ul>' : '');
}

/* ---------- Images ---------- */

/**
 * Show an uploaded image, or a coloured placeholder with the name when no image exists yet.
 */
function media(?string $image, string $label, string $class = ''): string
{
    if ($image && is_file(ROOT_PATH . '/' . $image)) {
        return '<img class="media ' . e($class) . '" src="' . e(url($image)) . '" alt="' . e($label) . '" loading="lazy">';
    }
    $tone = abs(crc32($label)) % 5 + 1;
    return '<div class="media media-ph tone-' . $tone . ' ' . e($class) . '" role="img" aria-label="' . e($label) . '">'
         . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z"/></svg>'
         . '<span>' . e($label) . '</span></div>';
}

/**
 * Validate and store an uploaded image. Returns the saved path,
 * or $current when no new file was chosen. Adds a flash message on failure.
 */
function upload_image(string $field, ?string $current = null): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $current;
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime  = $file['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
    if (!isset($types[$mime]) || $file['size'] > 3 * 1024 * 1024) {
        flash('Image not saved: please upload a JPG, PNG, WEBP or GIF file under 3 MB.', 'error');
        return $current;
    }
    $name = 'uploads/' . bin2hex(random_bytes(8)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], ROOT_PATH . '/' . $name)) {
        flash('Image not saved: the uploads folder is not writable.', 'error');
        return $current;
    }
    if ($current && is_file(ROOT_PATH . '/' . $current)) {
        unlink(ROOT_PATH . '/' . $current);
    }
    return $name;
}

/* ---------- Small inline icons ---------- */

function icon(string $name): string
{
    $paths = [
        'pin'      => '<path d="M12 21s-7-6.1-7-11a7 7 0 0114 0c0 4.900-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.500 20a6.500 6.500 0 0113 0M16 4.700a3.500 3.500 0 010 6.600M18 14.500a6.500 6.500 0 013.500 5.500"/>',
        'calendar' => '<rect x="3.500" y="5" width="17" height="15" rx="2"/><path d="M3.500 10h17M8 3v4M16 3v4"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.500 7l8.500 6 8.500-6"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20.500 20.500L16 16"/>',
        'check'    => '<path d="M4.500 12.500l5 5 10-11"/>',
        'bed'      => '<path d="M3 18V6M3 14h18v4M21 14v-2a3 3 0 00-3-3h-7v5"/><circle cx="7" cy="10.500" r="1.800"/>',
        'compass'  => '<circle cx="12" cy="12" r="9"/><path d="M15.500 8.500l-2 5-5 2 2-5z"/>',
        'map'      => '<path d="M9 4L3.500 6v14L9 18l6 2 5.500-2V4L15 6z"/><path d="M9 4v14M15 6v14"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}
