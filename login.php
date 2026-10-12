<?php
/**
 * Sign in for all roles. Admin and staff go to the admin panel, tourists to their dashboard.
 */
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(home_for(current_user()));
}

$email = '';
$error = '';
if (is_post()) {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $user  = db_one('SELECT * FROM users WHERE email = ?', [$email]);

    if (!$user || !password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        $error = 'The email address or password is incorrect.';
    } elseif ($user['status'] !== 'active') {
        $error = 'This account has been suspended. Please contact the administrator.';
    } else {
        session_regenerate_id(true); // new session ID on login prevents session fixation
        $_SESSION['user_id'] = $user['id'];
        $returnTo = $_SESSION['return_to'] ?? '';
        unset($_SESSION['return_to']);
        flash('Welcome back, ' . $user['full_name'] . '.');
        // Only return to pages inside this site
        if ($user['role'] === 'tourist' && str_starts_with($returnTo, BASE_URL)) {
            header('Location: ' . $returnTo);
            exit;
        }
        redirect(home_for($user));
    }
}

$pageTitle = 'Sign in';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <form method="post" class="panel auth-card">
        <?= csrf_field() ?>
        <h1 style="font-size:1.8rem">Sign in</h1>
        <p class="muted">Welcome back. Enter your details to continue.</p>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="username" autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
        <p class="text-center" style="margin:1rem 0 0">New here? <a href="<?= url('register.php') ?>">Create a free account</a></p>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
