<?php
/**
 * Contact form. Messages are saved as enquiries for the admin to answer.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$user   = current_user();
$data   = ['name' => $user['full_name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '', 'subject' => '', 'message' => ''];
$errors = [];

if (is_post()) {
    csrf_check();
    foreach ($data as $key => $unused) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    if (mb_strlen($data['name']) < 2) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (mb_strlen($data['subject']) < 3) {
        $errors['subject'] = 'Please enter a subject.';
    }
    if (mb_strlen($data['message']) < 10) {
        $errors['message'] = 'Your message should be at least 10 characters.';
    }
    if (!$errors) {
        db_run('INSERT INTO enquiries (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)',
               [$data['name'], $data['email'], $data['phone'] ?: null, $data['subject'], $data['message']]);
        flash('Thank you, your message has been sent. We will reply as soon as possible.');
        redirect('contact.php');
    }
}

$pageTitle = 'Contact';
include __DIR__ . '/includes/header.php';
page_hero('Contact Us', setting('contact_intro'));
?>
<section class="section">
    <div class="container detail">
        <form method="post" class="panel" novalidate>
            <?= csrf_field() ?>
            <h2>Send a message</h2>
            <div class="form-row">
                <div class="field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="<?= e($data['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><span class="field-error"><?= $errors['name'] ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><span class="field-error"><?= $errors['email'] ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="phone">Phone (optional)</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($data['phone']) ?>">
                </div>
                <div class="field">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?= e($data['subject']) ?>" required>
                    <?php if (isset($errors['subject'])): ?><span class="field-error"><?= $errors['subject'] ?></span><?php endif; ?>
                </div>
            </div>
            <div class="field">
                <label for="message">Message</label>
                <textarea id="message" name="message" required><?= e($data['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?><span class="field-error"><?= $errors['message'] ?></span><?php endif; ?>
            </div>
            <button class="btn btn-primary" type="submit">Send message</button>
        </form>

        <aside class="panel side-card">
            <h2>Get in touch</h2>
            <ul class="facts">
                <li><?= icon('pin') ?> <?= e(setting('contact_address')) ?></li>
                <li><?= icon('phone') ?> <?= e(setting('contact_phone')) ?></li>
                <li><?= icon('mail') ?> <?= e(setting('contact_email')) ?></li>
                <li><?= icon('clock') ?> <?= e(setting('contact_hours')) ?></li>
            </ul>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
