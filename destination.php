<?php
/**
 * Destination details, related tour packages and visitor reviews.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$id = (int)($_GET['id'] ?? 0);
$d  = db_one("SELECT d.*, c.name AS county FROM destinations d JOIN counties c ON c.id = d.county_id
              WHERE d.id = ? AND d.status = 'active'", [$id]);
if (!$d) {
    http_response_code(404);
    flash('That destination could not be found.', 'error');
    redirect('destinations.php');
}

// A signed-in tourist submits a review (shown after the admin approves it)
if (is_post()) {
    csrf_check();
    require_role('tourist');
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5 || mb_strlen($comment) < 10) {
        flash('Please choose a star rating and write at least 10 characters.', 'error');
    } else {
        db_run('INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)',
               [current_user()['id'], $id, $rating, mb_substr($comment, 0, 1000)]);
        flash('Thank you. Your review will appear once it has been approved.');
    }
    redirect('destination.php?id=' . $id . '#reviews');
}

$reviews  = db_all("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id
                    WHERE r.destination_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC", [$id]);
$average  = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$packages = db_all("SELECT p.*, g.full_name AS guide FROM packages p
                    JOIN package_destinations pd ON pd.package_id = p.id
                    LEFT JOIN guides g ON g.id = p.guide_id
                    WHERE pd.destination_id = ? AND p.status = 'active'", [$id]);
$hotels   = db_all("SELECT h.*, c.name AS county FROM hotels h JOIN counties c ON c.id = h.county_id
                    WHERE h.county_id = ? AND h.status = 'active' LIMIT 3", [$d['county_id']]);

$pageTitle = $d['name'];
include __DIR__ . '/includes/header.php';
page_hero($d['name'], $d['summary'] ?? '', ['Destinations' => 'destinations.php']);
?>
<section class="section">
    <div class="container detail">
        <div class="detail-main">
            <?= media($d['image'], $d['name']) ?>
            <div class="prose"><?= rich_text($d['description']) ?></div>

            <?php if ($packages): ?>
                <h2>Tour packages that visit <?= e($d['name']) ?></h2>
                <div class="grid"><?php array_map('card_package', $packages); ?></div>
            <?php endif; ?>

            <h2 id="reviews" style="margin-top:2rem">Visitor reviews</h2>
            <?php if ($reviews): ?>
                <p><?= stars($average) ?> <strong><?= number_format($average, 1) ?></strong> from <?= count($reviews) ?> review<?= count($reviews) > 1 ? 's' : '' ?></p>
                <div class="event-list">
                    <?php foreach ($reviews as $r): ?>
                        <figure class="review">
                            <?= stars($r['rating']) ?>
                            <blockquote>“<?= e($r['comment']) ?>”</blockquote>
                            <figcaption><cite><?= e($r['full_name']) ?>, <?= nice_date($r['created_at']) ?></cite></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">No reviews yet. Be the first to share your experience.</p>
            <?php endif; ?>

            <?php if (has_role('tourist')): ?>
                <form method="post" class="panel" style="margin-top:1.5rem">
                    <?= csrf_field() ?>
                    <h3>Write a review</h3>
                    <fieldset class="field" style="border:0;padding:0;margin:0 0 1rem">
                        <legend><strong>Your rating</strong></legend>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                                <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </fieldset>
                    <div class="field">
                        <label for="comment">Your experience</label>
                        <textarea id="comment" name="comment" required minlength="10" maxlength="1000"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Submit review</button>
                </form>
            <?php elseif (!current_user()): ?>
                <p><a class="btn btn-ghost" href="<?= url('login.php') ?>">Sign in to write a review</a></p>
            <?php endif; ?>
        </div>

        <aside class="panel side-card">
            <h2>Visitor information</h2>
            <ul class="facts">
                <li><?= icon('map') ?> <span><strong>County</strong><br><?= e($d['county']) ?></span></li>
                <li><?= icon('pin') ?> <span><strong>Location</strong><br><?= e($d['location']) ?></span></li>
                <li><?= icon('compass') ?> <span><strong>Category</strong><br><?= e($d['category']) ?></span></li>
                <li><?= icon('calendar') ?> <span><strong>Best time to visit</strong><br><?= e($d['best_time']) ?></span></li>
            </ul>
            <p class="price"><?= $d['entry_fee'] > 0 ? money($d['entry_fee']) . '<small>entry fee · about ' . lrd($d['entry_fee']) . '</small>' : 'Free entry' ?></p>
            <a class="btn btn-primary btn-block" href="<?= url('packages.php') ?>">Find a tour package</a>
            <?php if ($hotels): ?>
                <h3 style="margin-top:1.5rem">Stay nearby</h3>
                <ul class="facts">
                    <?php foreach ($hotels as $h): ?>
                        <li><?= icon('bed') ?> <span><a href="<?= url('hotel.php?id=' . $h['id']) ?>"><?= e($h['name']) ?></a><br><small class="muted"><?= money($h['price_per_night']) ?> per night</small></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
