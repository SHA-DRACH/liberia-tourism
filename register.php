<?php
/**
 * Tourist self-registration. Staff and admin accounts are created by the admin only.
 */
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(home_for(current_user()));
}

$data   = ['full_name' => '', 'email' => '', 'phone' => '', 'country' => 'Liberia'];
$errors = [];

if (is_post()) {
    csrf_check();
    foreach ($data as $key => $unused) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';

    if (mb_strlen($data['full_name']) < 3) {
        $errors['full_name'] = 'Please enter your full name.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (db_val('SELECT COUNT(*) FROM users WHERE email = ?', [$data['email']])) {
        $errors['email'] = 'An account with this email address already exists.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Your password must be at least 8 characters.';
    } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
        $errors['password_confirm'] = 'The two passwords do not match.';
    }

    if (!$errors) {
        db_run('INSERT INTO users (full_name, email, phone, country, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)',
               [$data['full_name'], $data['email'], $data['phone'] ?: null, $data['country'] ?: null,
                password_hash($password, PASSWORD_DEFAULT), 'tourist']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = db()->lastInsertId();
        $returnTo = $_SESSION['return_to'] ?? '';
        unset($_SESSION['return_to']);
        flash('Your account has been created. Welcome to ' . setting('site_name') . '!');
        if (str_starts_with($returnTo, BASE_URL)) {
            header('Location: ' . $returnTo);
            exit;
        }
        redirect('tourist/index.php');
    }
}

$err = fn(string $key) => isset($errors[$key]) ? '<span class="field-error">' . e($errors[$key]) . '</span>' : '';

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <form method="post" class="panel auth-card wide" novalidate>
        <?= csrf_field() ?>
        <h1 style="font-size:1.8rem">Create your account</h1>
        <p class="muted">Register to book tours, reserve hotels and review destinations.</p>
        <div class="form-row">
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" required autocomplete="name">
                <?= $err('full_name') ?>
            </div>
            <div class="field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" required autocomplete="email">
                <?= $err('email') ?>
            </div>
        </div>
        <div class="form-row">
            <div class="field">
                <label for="phone">Phone number</label>
                <input type="tel" id="phone" name="phone" value="<?= e($data['phone']) ?>" placeholder="+231 …" autocomplete="tel">
            </div>
            <div class="field">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="<?= e($data['country']) ?>" autocomplete="country-name">
            </div>
        </div>
        <div class="form-row">
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                <span class="hint">At least 8 characters.</span>
                <?= $err('password') ?>
            </div>
            <div class="field">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                <?= $err('password_confirm') ?>
            </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
        <p class="text-center" style="margin:1rem 0 0">Already registered? <a href="<?= url('login.php') ?>">Sign in</a></p>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
