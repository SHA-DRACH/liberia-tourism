<?php
/**
 * Reports for a chosen date range: totals, revenue by month, most booked
 * items and payment methods. Can be printed or exported to CSV (Excel).
 */
require_once __DIR__ . '/_init.php';

$valid = fn($d) => $d && strtotime($d) && date('Y-m-d', strtotime($d)) === $d;
$from  = $valid($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01', strtotime('-5 months'));
$to    = $valid($_GET['to'] ?? '')   ? $_GET['to']   : date('Y-m-d');
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];

$bookings = db_all(
    "SELECT b.reference, b.created_at, u.full_name, b.booking_type, COALESCE(p.title, h.name) AS item,
            b.start_date, b.people, b.total, b.status, COALESCE(pay.method, '') AS method, COALESCE(pay.status, 'unpaid') AS pay_status
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN packages p ON p.id = b.package_id
     LEFT JOIN hotels h ON h.id = b.hotel_id
     LEFT JOIN payments pay ON pay.booking_id = b.id
     WHERE b.created_at BETWEEN ? AND ? ORDER BY b.created_at", $range);

// ----- CSV export: opens in Excel -----
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bookings_' . $from . '_to_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Reference', 'Booked on', 'Tourist', 'Type', 'Item', 'Start date', 'People', 'Total (USD)', 'Status', 'Payment method', 'Payment status']);
    foreach ($bookings as $b) {
        fputcsv($out, $b);
    }
    exit;
}

// ----- Figures worked out from the bookings in the range -----
$isRevenue = fn($b) => $b['pay_status'] === 'paid' && $b['status'] !== 'cancelled';
$revenue   = array_sum(array_map(fn($b) => $isRevenue($b) ? $b['total'] : 0, $bookings));
$byStatus  = array_count_values(array_column($bookings, 'status'));
$byMonth   = [];
$byItem    = [];
$byMethod  = [];
$labels    = ['mtn_momo' => 'MTN Mobile Money', 'orange_money' => 'Orange Money', 'cash' => 'Cash on arrival', '' => 'Not yet paid'];
foreach ($bookings as $b) {
    if ($isRevenue($b)) {
        $month = date('M Y', strtotime($b['created_at']));
        $byMonth[$month] = ($byMonth[$month] ?? 0) + $b['total'];
    }
    if ($b['status'] !== 'cancelled') {
        $byItem[$b['item']] = ($byItem[$b['item']] ?? 0) + 1;
        $byMethod[$labels[$b['method']]] = ($byMethod[$labels[$b['method']]] ?? 0) + 1;
    }
}
arsort($byItem);
$byItem = array_slice($byItem, 0, 8, true);

$topDestinations = db_all(
    "SELECT d.name, COUNT(*) AS total FROM bookings b
     JOIN package_destinations pd ON pd.package_id = b.package_id
     JOIN destinations d ON d.id = pd.destination_id
     WHERE b.status <> 'cancelled' AND b.created_at BETWEEN ? AND ?
     GROUP BY d.id, d.name ORDER BY total DESC LIMIT 8", $range);
$newTourists = db_val("SELECT COUNT(*) FROM users WHERE role = 'tourist' AND created_at BETWEEN ? AND ?", $range);

/** Draw a horizontal bar chart from label => value pairs. */
$chart = function (array $data, bool $isMoney = false): void {
    if (!$data) {
        echo '<p class="muted">No data for this period.</p>';
        return;
    }
    $max = max($data) ?: 1;
    echo '<ul class="bars">';
    foreach ($data as $label => $value) {
        echo '<li><span>' . e($label) . '</span><span class="bar-track"><span class="bar-fill" style="display:block;width:' . round($value / $max * 100) . '%"></span></span>'
           . '<strong>' . ($isMoney ? money($value) : (int)$value) . '</strong></li>';
    }
    echo '</ul>';
};

$pageTitle = 'Reports';
$activeNav = 'reports';
include __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <form method="get" class="toolbar-search">
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="<?= e($from) ?>">
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="<?= e($to) ?>">
        <button class="btn btn-navy btn-sm" type="submit">Show report</button>
    </form>
    <div>
        <button class="btn btn-ghost btn-sm" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-ghost btn-sm" href="<?= url('admin/reports.php?from=' . $from . '&to=' . $to . '&export=csv') ?>">Export CSV</a>
    </div>
</div>

<div class="print-head">
    <h2><?= e(setting('site_name')) ?>: Tourism report</h2>
    <p><?= nice_date($from) ?> to <?= nice_date($to) ?> · printed <?= date('j M Y') ?></p>
</div>

<div class="tiles">
    <div class="tile"><strong><?= count($bookings) ?></strong><span>Bookings made</span></div>
    <div class="tile green"><strong><?= money($revenue) ?></strong><span>Revenue received</span></div>
    <div class="tile"><strong><?= (int)($byStatus['confirmed'] ?? 0) + (int)($byStatus['completed'] ?? 0) ?></strong><span>Confirmed or completed</span></div>
    <div class="tile red"><strong><?= (int)($byStatus['cancelled'] ?? 0) ?></strong><span>Cancelled</span></div>
    <div class="tile gold"><strong><?= (int)$newTourists ?></strong><span>New tourists registered</span></div>
</div>

<div class="two-col" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr))">
    <div class="panel"><h2>Revenue by month</h2><?php $chart($byMonth, true); ?></div>
    <div class="panel" style="margin-top:0"><h2>Most booked tours and hotels</h2><?php $chart($byItem); ?></div>
    <div class="panel" style="margin-top:0"><h2>Most visited destinations</h2><?php $chart(array_column($topDestinations, 'total', 'name')); ?></div>
    <div class="panel" style="margin-top:0"><h2>Payment methods</h2><?php $chart($byMethod); ?></div>
</div>

<h2 style="margin-top:2rem">Bookings in this period</h2>
<div class="table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Booked on</th><th>Tourist</th><th>Item</th><th>Start</th><th>People</th><th class="num">Total</th><th>Payment</th><th>Status</th></tr></thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= e($b['reference']) ?></td>
                    <td><?= nice_date($b['created_at']) ?></td>
                    <td><?= e($b['full_name']) ?></td>
                    <td><?= e($b['item']) ?></td>
                    <td><?= nice_date($b['start_date']) ?></td>
                    <td><?= (int)$b['people'] ?></td>
                    <td class="num"><?= money($b['total']) ?></td>
                    <td><?= status_badge($b['pay_status']) ?></td>
                    <td><?= status_badge($b['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?><tr><td colspan="9" class="muted text-center">No bookings were made in this period.</td></tr><?php endif; ?>
        </tbody>
        <?php if ($bookings): ?>
            <tfoot><tr><th colspan="6">Revenue received (paid, not cancelled)</th><th class="num"><?= money($revenue) ?></th><th colspan="2"></th></tr></tfoot>
        <?php endif; ?>
    </table>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
