<?php
/**
 * Tour package details with the booking form.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$id = (int)($_GET['id'] ?? 0);
$p  = db_one("SELECT p.*, g.full_name AS guide, g.languages, g.experience_years
              FROM packages p LEFT JOIN guides g ON g.id = p.guide_id
              WHERE p.id = ? AND p.status = 'active'", [$id]);
if (!$p) {
    flash('That tour package could not be found.', 'error');
    redirect('packages.php');
}
$stops = db_all("SELECT d.id, d.name FROM destinations d JOIN package_destinations pd ON pd.destination_id = d.id
                 WHERE pd.package_id = ? AND d.status = 'active' ORDER BY d.name", [$id]);
$user  = current_user();

$pageTitle = $p['title'];
include __DIR__ . '/includes/header.php';
page_hero($p['title'], $p['summary'] ?? '', ['Tour Packages' => 'packages.php']);
?>
<section class="section">
    <div class="container detail">
        <div class="detail-main">
            <?= media($p['image'], $p['title']) ?>
            <ul class="facts form-row">
                <li><?= icon('clock') ?> <span><strong>Duration</strong><br><?= (int)$p['duration_days'] ?> day<?= $p['duration_days'] > 1 ? 's' : '' ?></span></li>
                <li><?= icon('users') ?> <span><strong>Group size</strong><br>Up to <?= (int)$p['max_people'] ?> people</span></li>
                <?php if ($p['guide']): ?>
                    <li><?= icon('compass') ?> <span><strong>Your guide</strong><br><?= e($p['guide']) ?> (<?= (int)$p['experience_years'] ?> years, speaks <?= e($p['languages']) ?>)</span></li>
                <?php endif; ?>
            </ul>

            <div class="prose">
                <h2>About this tour</h2>
                <?= rich_text($p['description']) ?>

                <?php if ($stops): ?>
                    <h2>Places you will visit</h2>
                    <ul class="chips">
                        <?php foreach ($stops as $s): ?>
                            <li><a href="<?= url('destination.php?id=' . $s['id']) ?>"><?= e($s['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (trim((string)$p['includes']) !== ''): ?>
                    <h2>What is included</h2>
                    <ul class="facts">
                        <?php foreach (preg_split('/\R/', trim($p['includes'])) as $item): if (trim($item) !== ''): ?>
                            <li><?= icon('check') ?> <?= e($item) ?></li>
                        <?php endif; endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <aside class="panel side-card">
            <p class="price"><?= money($p['price']) ?><small>per person · about <?= lrd($p['price']) ?></small></p>
            <?php if ($user && $user['role'] === 'tourist'): ?>
                <form method="post" action="<?= url('book.php') ?>" data-booking="package" data-price="<?= e($p['price']) ?>" data-rate="<?= e(setting('usd_to_lrd', '190')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="package">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="field">
                        <label for="start_date">Start date</label>
                        <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="field">
                        <label for="people">Number of people</label>
                        <input type="number" id="people" name="people" value="1" min="1" max="<?= (int)$p['max_people'] ?>" required>
                    </div>
                    <div class="field">
                        <label for="notes">Special requests (optional)</label>
                        <textarea id="notes" name="notes" maxlength="500" style="min-height:70px"></textarea>
                    </div>
                    <div class="total-line"><span>Total</span><span><output id="total"></output><br><small class="muted" id="total-lrd"></small></span></div>
                    <button class="btn btn-primary btn-block" type="submit">Book this tour</button>
                </form>
            <?php elseif ($user): ?>
                <p class="muted">You are signed in as <?= e($user['role']) ?>. Only tourist accounts can make bookings.</p>
            <?php else: ?>
                <p>Sign in or create a free account to book this tour.</p>
                <a class="btn btn-primary btn-block" href="<?= url('book.php?type=package&id=' . $id) ?>">Sign in to book</a>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
