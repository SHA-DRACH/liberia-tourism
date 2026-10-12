<?php
/**
 * Tourist profile: update personal details and change password.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cards.php';
require_role('tourist');
$user = current_user();

if (is_post()) {
    csrf_check();
    if (($_POST['form'] ?? '') === 'password') {
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $user['password_hash'])) {
            flash('Your current password is incorrect.', 'error');
        } elseif (strlen($new) < 8 || $new !== ($_POST['confirm_password'] ?? '')) {
            flash('The new password must be at least 8 characters and match the confirmation.', 'error');
        } else {
            db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('Your password has been changed.');
        }
    } else {
        $name = trim($_POST['full_name'] ?? '');
        if (mb_strlen($name) < 3) {
            flash('Please enter your full name.', 'error');
        } else {
            db_run('UPDATE users SET full_name = ?, phone = ?, country = ? WHERE id = ?',
                   [$name, trim($_POST['phone'] ?? '') ?: null, trim($_POST['country'] ?? '') ?: null, $user['id']]);
            flash('Your profile has been updated.');
        }
    }
    redirect('tourist/profile.php');
}

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
page_hero('My Profile', 'Keep your contact details up to date.', ['My Bookings' => 'tourist/index.php']);
?>
<section class="section">
    <div class="container">
        <div class="dash-tabs">
            <a class="btn btn-ghost btn-sm" href="<?= url('tourist/index.php') ?>">My bookings</a>
            <a class="btn btn-navy btn-sm" href="<?= url('tourist/profile.php') ?>">My profile</a>
        </div>
        <div class="split" style="align-items:start">
            <form method="post" class="panel">
                <?= csrf_field() ?>
                <h2>Personal details</h2>
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
                    <span class="hint">Contact the administrator to change your email address.</span>
                </div>
                <div class="field">
                    <label for="phone">Phone number</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>">
                </div>
                <div class="field">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="<?= e($user['country']) ?>">
                </div>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </form>

            <form method="post" class="panel">
                <?= csrf_field() ?>
                <input type="hidden" name="form" value="password">
                <h2>Change password</h2>
                <div class="field">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                </div>
                <button class="btn btn-navy" type="submit">Change password</button>
            </form>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
