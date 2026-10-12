<?php
/**
 * Tourist dashboard: list of the tourist's own bookings, with cancel.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cards.php';
require_role('tourist');
$user = current_user();

// Cancel a booking (only the owner, and only while it is pending or confirmed)
if (is_post()) {
    csrf_check();
    $done = db_run("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed')",
                   [(int)($_POST['cancel_id'] ?? 0), $user['id']])->rowCount();
    flash($done ? 'Your booking has been cancelled.' : 'That booking can no longer be cancelled.', $done ? 'success' : 'error');
    redirect('tourist/index.php');
}

$bookings = db_all(
    "SELECT b.*, COALESCE(p.title, h.name) AS item, pay.status AS pay_status, pay.method
     FROM bookings b
     LEFT JOIN packages p ON p.id = b.package_id
     LEFT JOIN hotels h   ON h.id = b.hotel_id
     LEFT JOIN payments pay ON pay.booking_id = b.id
     WHERE b.user_id = ? ORDER BY b.created_at DESC, b.id DESC", [$user['id']]);

$count = fn(string $status) => count(array_filter($bookings, fn($b) => $b['status'] === $status));
$spent = array_sum(array_map(fn($b) => $b['pay_status'] === 'paid' && $b['status'] !== 'cancelled' ? $b['total'] : 0, $bookings));

$pageTitle = 'My Bookings';
include __DIR__ . '/../includes/header.php';
page_hero('My Bookings', 'Welcome, ' . $user['full_name'] . '. Here are all your trips.');
?>
<section class="section">
    <div class="container">
        <div class="dash-tabs">
            <a class="btn btn-navy btn-sm" href="<?= url('tourist/index.php') ?>">My bookings</a>
            <a class="btn btn-ghost btn-sm" href="<?= url('tourist/profile.php') ?>">My profile</a>
            <a class="btn btn-ghost btn-sm" href="<?= url('packages.php') ?>">Book a tour</a>
            <a class="btn btn-ghost btn-sm" href="<?= url('hotels.php') ?>">Reserve a hotel</a>
        </div>

        <div class="tiles">
            <div class="tile"><strong><?= count($bookings) ?></strong><span>Total bookings</span></div>
            <div class="tile"><strong><?= $count('pending') ?></strong><span>Awaiting confirmation</span></div>
            <div class="tile"><strong><?= $count('confirmed') ?></strong><span>Confirmed</span></div>
            <div class="tile"><strong><?= money($spent) ?></strong><span>Total paid</span></div>
        </div>

        <?php if ($bookings): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Reference</th><th>Booking</th><th>Dates</th><th>People</th><th class="num">Total</th><th>Payment</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><strong><?= e($b['reference']) ?></strong></td>
                                <td><?= e($b['item']) ?><br><small class="muted"><?= $b['booking_type'] === 'hotel' ? 'Hotel, ' . (int)$b['rooms'] . ' room(s)' : 'Tour package' ?></small></td>
                                <td><?= nice_date($b['start_date']) ?><?= $b['end_date'] && $b['end_date'] !== $b['start_date'] ? ' to ' . nice_date($b['end_date']) : '' ?></td>
                                <td><?= (int)$b['people'] ?></td>
                                <td class="num"><?= money($b['total']) ?></td>
                                <td><?= $b['pay_status'] ? status_badge($b['pay_status']) : '<span class="badge">Not paid</span>' ?></td>
                                <td><?= status_badge($b['status']) ?></td>
                                <td class="actions">
                                    <?php if (!$b['pay_status'] && $b['status'] === 'pending'): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= url('tourist/pay.php?id=' . $b['id']) ?>">Pay</a>
                                    <?php else: ?>
                                        <a class="btn btn-ghost btn-sm" href="<?= url('tourist/receipt.php?id=' . $b['id']) ?>">Receipt</a>
                                    <?php endif; ?>
                                    <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
                                        <form method="post" data-confirm="Cancel booking <?= e($b['reference']) ?>?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="cancel_id" value="<?= $b['id'] ?>">
                                            <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty">You have no bookings yet. <a href="<?= url('packages.php') ?>">Browse tour packages</a> to plan your first trip.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
