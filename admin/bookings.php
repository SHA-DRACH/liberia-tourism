<?php
/**
 * Booking management: confirm, complete or cancel bookings and record cash payments.
 */
require_once __DIR__ . '/_init.php';

// Which status changes are allowed from each current status
$moves = [
    'pending'   => ['confirmed' => 'Confirm', 'cancelled' => 'Cancel'],
    'confirmed' => ['completed' => 'Complete', 'cancelled' => 'Cancel'],
];

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $b  = db_one('SELECT * FROM bookings WHERE id = ?', [$id]);
    $to = $_POST['status'] ?? '';

    if ($b && isset($_POST['cash_received'])) {
        db_run("UPDATE payments SET status = 'paid', paid_at = NOW() WHERE booking_id = ? AND method = 'cash'", [$id]);
        flash('Cash payment recorded for ' . $b['reference'] . '.');
    } elseif ($b && isset($moves[$b['status']][$to])) {
        db_run('UPDATE bookings SET status = ? WHERE id = ?', [$to, $id]);
        flash('Booking ' . $b['reference'] . ' is now ' . $to . '.');
    } else {
        flash('That change is not allowed.', 'error');
    }
    redirect('admin/bookings.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
$status   = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$q        = trim($_GET['q'] ?? '');

$where  = ['1 = 1'];
$params = [];
if ($status) {
    $where[]  = 'b.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(b.reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}

$bookings = db_all(
    'SELECT b.*, u.full_name, u.phone, COALESCE(p.title, h.name) AS item, pay.status AS pay_status, pay.method
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN packages p ON p.id = b.package_id
     LEFT JOIN hotels h ON h.id = b.hotel_id
     LEFT JOIN payments pay ON pay.booking_id = b.id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY b.created_at DESC, b.id DESC LIMIT 200', $params);
$methods = ['mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', 'cash' => 'Cash'];

$pageTitle = 'Bookings';
$activeNav = 'bookings';
include __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <form method="get" class="toolbar-search">
        <label class="skip-link" for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Reference, name or email…">
        <label class="skip-link" for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>"<?= $status === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-navy btn-sm" type="submit">Filter</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Reference</th><th>Tourist</th><th>Booking</th><th>Dates</th><th>People</th><th class="num">Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><a href="<?= url('tourist/receipt.php?id=' . $b['id']) ?>"><strong><?= e($b['reference']) ?></strong></a><br><small class="muted"><?= nice_date($b['created_at']) ?></small></td>
                    <td><?= e($b['full_name']) ?><br><small class="muted"><?= e($b['phone']) ?></small></td>
                    <td><?= e($b['item']) ?><br><small class="muted"><?= $b['booking_type'] === 'hotel' ? 'Hotel, ' . (int)$b['rooms'] . ' room(s)' : 'Tour package' ?></small></td>
                    <td><?= nice_date($b['start_date']) ?><?= $b['end_date'] && $b['end_date'] !== $b['start_date'] ? '<br>to ' . nice_date($b['end_date']) : '' ?></td>
                    <td><?= (int)$b['people'] ?></td>
                    <td class="num"><?= money($b['total']) ?></td>
                    <td>
                        <?php if ($b['pay_status']): ?>
                            <?= status_badge($b['pay_status']) ?><br><small class="muted"><?= e($methods[$b['method']]) ?></small>
                        <?php else: ?>
                            <span class="badge">Not paid</span>
                        <?php endif; ?>
                    </td>
                    <td><?= status_badge($b['status']) ?></td>
                    <td class="actions">
                        <?php foreach ($moves[$b['status']] ?? [] as $to => $label): ?>
                            <form method="post"<?= $to === 'cancelled' ? ' data-confirm="Cancel booking ' . e($b['reference']) . '?"' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="status" value="<?= $to ?>">
                                <button class="btn btn-sm <?= $to === 'cancelled' ? 'btn-danger' : 'btn-navy' ?>" type="submit"><?= $label ?></button>
                            </form>
                        <?php endforeach; ?>
                        <?php if ($b['method'] === 'cash' && $b['pay_status'] === 'pending' && $b['status'] !== 'cancelled'): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button class="btn btn-ghost btn-sm" type="submit" name="cash_received" value="1">Cash received</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?><tr><td colspan="9" class="muted text-center">No bookings found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
