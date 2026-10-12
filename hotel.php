<?php
/**
 * Hotel details with the room reservation form.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$id = (int)($_GET['id'] ?? 0);
$h  = db_one("SELECT h.*, c.name AS county FROM hotels h JOIN counties c ON c.id = h.county_id
              WHERE h.id = ? AND h.status = 'active'", [$id]);
if (!$h) {
    flash('That hotel could not be found.', 'error');
    redirect('hotels.php');
}
$nearby = db_all("SELECT d.*, c.name AS county FROM destinations d JOIN counties c ON c.id = d.county_id
                  WHERE d.county_id = ? AND d.status = 'active' LIMIT 3", [$h['county_id']]);
$user   = current_user();

$pageTitle = $h['name'];
include __DIR__ . '/includes/header.php';
page_hero($h['name'], $h['address'] . ', ' . $h['county'] . ' County', ['Hotels' => 'hotels.php']);
?>
<section class="section">
    <div class="container detail">
        <div class="detail-main">
            <?= media($h['image'], $h['name']) ?>
            <p><?= stars($h['stars']) ?> <span class="muted"><?= (int)$h['stars'] ?>-star accommodation</span></p>
            <div class="prose">
                <?= rich_text($h['description']) ?>
                <h2>Amenities</h2>
                <ul class="chips">
                    <?php foreach (array_filter(array_map('trim', explode(',', (string)$h['amenities']))) as $amenity): ?>
                        <li><?= e($amenity) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <ul class="facts">
                <li><?= icon('pin') ?> <?= e($h['address']) ?>, <?= e($h['county']) ?> County</li>
                <li><?= icon('phone') ?> <?= e($h['phone']) ?></li>
                <li><?= icon('bed') ?> <?= (int)$h['rooms_available'] ?> rooms available</li>
            </ul>

            <?php if ($nearby): ?>
                <h2>Things to see nearby</h2>
                <div class="grid"><?php array_map('card_destination', $nearby); ?></div>
            <?php endif; ?>
        </div>

        <aside class="panel side-card">
            <p class="price"><?= money($h['price_per_night']) ?><small>per room, per night · about <?= lrd($h['price_per_night']) ?></small></p>
            <?php if ($user && $user['role'] === 'tourist'): ?>
                <form method="post" action="<?= url('book.php') ?>" data-booking="hotel" data-price="<?= e($h['price_per_night']) ?>" data-rate="<?= e(setting('usd_to_lrd', '190')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="hotel">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="form-row">
                        <div class="field">
                            <label for="start_date">Check-in</label>
                            <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="field">
                            <label for="end_date">Check-out</label>
                            <input type="date" id="end_date" name="end_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label for="rooms">Rooms</label>
                            <input type="number" id="rooms" name="rooms" value="1" min="1" max="<?= (int)$h['rooms_available'] ?>" required>
                        </div>
                        <div class="field">
                            <label for="people">Guests</label>
                            <input type="number" id="people" name="people" value="2" min="1" max="40" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="notes">Special requests (optional)</label>
                        <textarea id="notes" name="notes" maxlength="500" style="min-height:70px"></textarea>
                    </div>
                    <div class="total-line"><span>Total <small class="muted" id="nights"></small></span><span><output id="total"></output><br><small class="muted" id="total-lrd"></small></span></div>
                    <button class="btn btn-primary btn-block" type="submit">Reserve now</button>
                </form>
            <?php elseif ($user): ?>
                <p class="muted">You are signed in as <?= e($user['role']) ?>. Only tourist accounts can make bookings.</p>
            <?php else: ?>
                <p>Sign in or create a free account to reserve a room.</p>
                <a class="btn btn-primary btn-block" href="<?= url('book.php?type=hotel&id=' . $id) ?>">Sign in to reserve</a>
            <?php endif; ?>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
