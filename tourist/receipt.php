<?php
/**
 * Printable booking receipt. Tourists see their own; admin and staff can open any.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$b  = db_one("SELECT b.*, COALESCE(p.title, h.name) AS item, u.full_name, u.email, u.phone,
                     pay.method, pay.transaction_ref, pay.status AS pay_status, pay.paid_at, pay.payer_phone
              FROM bookings b
              JOIN users u ON u.id = b.user_id
              LEFT JOIN packages p ON p.id = b.package_id
              LEFT JOIN hotels h ON h.id = b.hotel_id
              LEFT JOIN payments pay ON pay.booking_id = b.id
              WHERE b.id = ?", [$id]);
if (!$b || ($user['role'] === 'tourist' && (int)$b['user_id'] !== (int)$user['id'])) {
    flash('That booking could not be found.', 'error');
    redirect(home_for($user));
}
$methods = ['mtn_momo' => 'MTN Mobile Money', 'orange_money' => 'Orange Money', 'cash' => 'Cash on arrival'];

$pageTitle = 'Receipt ' . $b['reference'];
include __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="panel receipt">
            <div class="receipt-head">
                <div>
                    <h1 style="font-size:1.6rem;margin:0"><?= e(setting('site_name')) ?></h1>
                    <p class="muted" style="margin:0"><?= e(setting('contact_address')) ?><br><?= e(setting('contact_phone')) ?></p>
                </div>
                <div style="text-align:right">
                    <strong>BOOKING RECEIPT</strong><br>
                    <?= e($b['reference']) ?><br>
                    <?= status_badge($b['status']) ?>
                </div>
            </div>
            <dl>
                <dt>Customer</dt><dd><?= e($b['full_name']) ?></dd>
                <dt>Email</dt><dd><?= e($b['email']) ?></dd>
                <dt>Phone</dt><dd><?= e($b['phone'] ?: 'Not given') ?></dd>
                <dt>Booked on</dt><dd><?= nice_date($b['created_at'], 'j M Y, g:i a') ?></dd>
                <dt><?= $b['booking_type'] === 'hotel' ? 'Hotel' : 'Tour package' ?></dt><dd><?= e($b['item']) ?></dd>
                <dt><?= $b['booking_type'] === 'hotel' ? 'Check-in / check-out' : 'Tour dates' ?></dt>
                <dd><?= nice_date($b['start_date']) ?><?= $b['end_date'] && $b['end_date'] !== $b['start_date'] ? ' to ' . nice_date($b['end_date']) : '' ?></dd>
                <dt>People</dt><dd><?= (int)$b['people'] ?><?= $b['booking_type'] === 'hotel' ? ' guest(s), ' . (int)$b['rooms'] . ' room(s)' : '' ?></dd>
                <?php if ($b['notes']): ?><dt>Special requests</dt><dd><?= e($b['notes']) ?></dd><?php endif; ?>
                <dt>Payment method</dt><dd><?= e($methods[$b['method']] ?? 'Not yet paid') ?></dd>
                <?php if ($b['transaction_ref']): ?>
                    <dt>Transaction reference</dt><dd><?= e($b['transaction_ref']) ?></dd>
                    <dt>Payment status</dt><dd><?= status_badge($b['pay_status']) ?> <?= $b['pay_status'] === 'paid' ? nice_date($b['paid_at'], 'j M Y, g:i a') : '' ?></dd>
                <?php endif; ?>
            </dl>
            <div class="total-line"><span>Total amount</span><span><output><?= money($b['total']) ?></output> <small class="muted">(about <?= lrd($b['total']) ?>)</small></span></div>
            <p class="muted" style="font-size:.85rem">Please show this receipt, printed or on your phone, at the start of your tour or at hotel check-in. Thank you for travelling with us.</p>
            <p class="no-print">
                <button class="btn btn-navy" type="button" onclick="window.print()">Print receipt</button>
                <a class="btn btn-ghost" href="<?= url($user['role'] === 'tourist' ? 'tourist/index.php' : 'admin/bookings.php') ?>">Back to bookings</a>
            </p>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
