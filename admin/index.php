<?php
/**
 * Admin dashboard: key totals, latest bookings and most popular items.
 */
require_once __DIR__ . '/_init.php';

$totals = [
    'tourists'     => db_val("SELECT COUNT(*) FROM users WHERE role = 'tourist'"),
    'bookings'     => db_val('SELECT COUNT(*) FROM bookings'),
    'pending'      => db_val("SELECT COUNT(*) FROM bookings WHERE status = 'pending'"),
    'revenue'      => db_val("SELECT COALESCE(SUM(pay.amount), 0) FROM payments pay JOIN bookings b ON b.id = pay.booking_id
                              WHERE pay.status = 'paid' AND b.status <> 'cancelled'"),
    'destinations' => db_val('SELECT COUNT(*) FROM destinations'),
    'enquiries'    => db_val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
];
$recent = db_all("SELECT b.*, u.full_name, COALESCE(p.title, h.name) AS item FROM bookings b
                  JOIN users u ON u.id = b.user_id
                  LEFT JOIN packages p ON p.id = b.package_id LEFT JOIN hotels h ON h.id = b.hotel_id
                  ORDER BY b.created_at DESC, b.id DESC LIMIT 8");
$popular = db_all("SELECT COALESCE(p.title, h.name) AS item, COUNT(*) AS total FROM bookings b
                   LEFT JOIN packages p ON p.id = b.package_id LEFT JOIN hotels h ON h.id = b.hotel_id
                   WHERE b.status <> 'cancelled' GROUP BY item ORDER BY total DESC LIMIT 6");
$max = $popular ? max(array_column($popular, 'total')) : 1;

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/_header.php';
?>
<div class="tiles">
    <div class="tile"><strong><?= (int)$totals['tourists'] ?></strong><span>Registered tourists</span></div>
    <div class="tile"><strong><?= (int)$totals['bookings'] ?></strong><span>Total bookings</span></div>
    <div class="tile gold"><strong><?= (int)$totals['pending'] ?></strong><span>Bookings awaiting confirmation</span></div>
    <div class="tile green"><strong><?= money($totals['revenue']) ?></strong><span>Revenue received</span></div>
    <div class="tile"><strong><?= (int)$totals['destinations'] ?></strong><span>Destinations</span></div>
    <div class="tile red"><strong><?= (int)$totals['enquiries'] ?></strong><span>New enquiries</span></div>
</div>

<div class="two-col">
    <div>
        <div class="toolbar"><h2 style="margin:0">Latest bookings</h2><a class="btn btn-ghost btn-sm" href="<?= url('admin/bookings.php') ?>">All bookings</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Reference</th><th>Tourist</th><th>Booking</th><th>Start</th><th class="num">Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($recent as $b): ?>
                        <tr>
                            <td><a href="<?= url('tourist/receipt.php?id=' . $b['id']) ?>"><?= e($b['reference']) ?></a></td>
                            <td><?= e($b['full_name']) ?></td>
                            <td><?= e($b['item']) ?></td>
                            <td><?= nice_date($b['start_date']) ?></td>
                            <td class="num"><?= money($b['total']) ?></td>
                            <td><?= status_badge($b['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?><tr><td colspan="6" class="muted text-center">No bookings have been made yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Most booked</h2>
        <?php if ($popular): ?>
            <ul class="bars">
                <?php foreach ($popular as $row): ?>
                    <li>
                        <span><?= e($row['item']) ?></span>
                        <span class="bar-track"><span class="bar-fill" style="width:<?= round($row['total'] / $max * 100) ?>%;display:block"></span></span>
                        <strong><?= (int)$row['total'] ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">The chart appears after the first booking.</p>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
