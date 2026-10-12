<?php
/**
 * Payment page. Payment is SIMULATED for this project: no real money moves.
 * The chosen method is recorded and a transaction reference is generated.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cards.php';
require_role('tourist');
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$b  = db_one("SELECT b.*, COALESCE(p.title, h.name) AS item FROM bookings b
              LEFT JOIN packages p ON p.id = b.package_id LEFT JOIN hotels h ON h.id = b.hotel_id
              WHERE b.id = ? AND b.user_id = ?", [$id, $user['id']]);
if (!$b) {
    flash('That booking could not be found.', 'error');
    redirect('tourist/index.php');
}
if ($b['status'] !== 'pending' || db_val('SELECT COUNT(*) FROM payments WHERE booking_id = ?', [$id])) {
    redirect('tourist/receipt.php?id=' . $id);
}

$methods = ['mtn_momo' => 'MTN Mobile Money', 'orange_money' => 'Orange Money', 'cash' => 'Cash on arrival'];
$error   = '';

if (is_post()) {
    csrf_check();
    $method = $_POST['method'] ?? '';
    $phone  = preg_replace('/[^0-9+]/', '', $_POST['payer_phone'] ?? '');

    if (!isset($methods[$method])) {
        $error = 'Please choose a payment method.';
    } elseif ($method !== 'cash' && strlen($phone) < 9) {
        $error = 'Please enter the mobile money number to charge.';
    } else {
        // Mobile money is treated as paid immediately; cash stays pending until arrival
        $prefix = ['mtn_momo' => 'MTN', 'orange_money' => 'ORG', 'cash' => 'CSH'][$method];
        db_run('INSERT INTO payments (booking_id, method, payer_phone, amount, transaction_ref, status) VALUES (?, ?, ?, ?, ?, ?)',
               [$id, $method, $method === 'cash' ? null : $phone, $b['total'],
                $prefix . '-' . strtoupper(bin2hex(random_bytes(5))), $method === 'cash' ? 'pending' : 'paid']);
        flash($method === 'cash' ? 'Booking saved. Please pay in cash on arrival.' : 'Payment received. Thank you!');
        redirect('tourist/receipt.php?id=' . $id);
    }
}

$pageTitle = 'Payment';
include __DIR__ . '/../includes/header.php';
page_hero('Payment', 'Booking ' . $b['reference'], ['My Bookings' => 'tourist/index.php']);
?>
<section class="section">
    <div class="container detail">
        <form method="post" class="panel">
            <?= csrf_field() ?>
            <h2>Choose how to pay</h2>
            <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <div class="radio-cards field">
                <?php foreach ($methods as $value => $label): ?>
                    <label><input type="radio" name="method" value="<?= $value ?>" required<?= ($_POST['method'] ?? 'mtn_momo') === $value ? ' checked' : '' ?>> <?= $label ?></label>
                <?php endforeach; ?>
            </div>
            <div class="field">
                <label for="payer_phone">Mobile money number</label>
                <input type="tel" id="payer_phone" name="payer_phone" value="<?= e($_POST['payer_phone'] ?? $user['phone']) ?>" placeholder="+231 …">
                <span class="hint">Not needed for cash on arrival.</span>
            </div>
            <div class="alert alert-info">This is a demonstration system. Payments are simulated and no money is charged.</div>
            <button class="btn btn-primary" type="submit">Confirm payment of <?= money($b['total']) ?></button>
            <a class="btn btn-ghost" href="<?= url('tourist/index.php') ?>">Pay later</a>
        </form>

        <aside class="panel side-card">
            <h2>Booking summary</h2>
            <ul class="facts">
                <li><?= icon($b['booking_type'] === 'hotel' ? 'bed' : 'compass') ?> <span><strong><?= e($b['item']) ?></strong></span></li>
                <li><?= icon('calendar') ?> <?= nice_date($b['start_date']) ?><?= $b['end_date'] && $b['end_date'] !== $b['start_date'] ? ' to ' . nice_date($b['end_date']) : '' ?></li>
                <li><?= icon('users') ?> <?= (int)$b['people'] ?> <?= $b['people'] > 1 ? 'people' : 'person' ?><?= $b['booking_type'] === 'hotel' ? ', ' . (int)$b['rooms'] . ' room(s)' : '' ?></li>
            </ul>
            <div class="total-line"><span>Total</span><span><output><?= money($b['total']) ?></output><br><small class="muted">about <?= lrd($b['total']) ?></small></span></div>
        </aside>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
