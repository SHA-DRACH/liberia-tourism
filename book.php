<?php
/**
 * Creates a booking. The price is always calculated on the server,
 * never trusted from the browser.
 */
require_once __DIR__ . '/includes/functions.php';

$type = ($_REQUEST['type'] ?? '') === 'hotel' ? 'hotel' : 'package';
$id   = (int)($_REQUEST['id'] ?? 0);
$back = $type . '.php?id=' . $id;

require_login();
if (!is_post()) {
    redirect($back); // opened directly or after signing in: show the booking form
}
csrf_check();
require_role('tourist');

$start  = $_POST['start_date'] ?? '';
$people = (int)($_POST['people'] ?? 0);
$notes  = mb_substr(trim($_POST['notes'] ?? ''), 0, 500);
$errors = [];

$startTime = strtotime($start);
if (!$startTime || $start !== date('Y-m-d', $startTime) || $start < date('Y-m-d')) {
    $errors[] = 'Please choose a valid date that is not in the past.';
}

if ($type === 'package') {
    $item = db_one("SELECT * FROM packages WHERE id = ? AND status = 'active'", [$id]);
    if (!$item) {
        redirect('packages.php');
    }
    if ($people < 1 || $people > $item['max_people']) {
        $errors[] = 'This tour takes between 1 and ' . $item['max_people'] . ' people.';
    }
    $rooms = 1;
    $end   = $startTime ? date('Y-m-d', strtotime('+' . ($item['duration_days'] - 1) . ' days', $startTime)) : null;
    $total = $item['price'] * $people;
} else {
    $item = db_one("SELECT * FROM hotels WHERE id = ? AND status = 'active'", [$id]);
    if (!$item) {
        redirect('hotels.php');
    }
    $end     = $_POST['end_date'] ?? '';
    $rooms   = (int)($_POST['rooms'] ?? 0);
    $endTime = strtotime($end);
    $nights  = ($startTime && $endTime) ? (int)round(($endTime - $startTime) / 86400) : 0;
    if (!$endTime || $end !== date('Y-m-d', $endTime) || $nights < 1) {
        $errors[] = 'Check-out must be at least one day after check-in.';
    }
    if ($rooms < 1 || $rooms > $item['rooms_available']) {
        $errors[] = 'Please choose between 1 and ' . $item['rooms_available'] . ' rooms.';
    }
    if ($people < 1 || $people > 40) {
        $errors[] = 'Please enter the number of guests.';
    }
    $total = $item['price_per_night'] * $nights * $rooms;
}

if ($errors) {
    foreach ($errors as $error) {
        flash($error, 'error');
    }
    redirect($back);
}

$reference = 'LTB-' . strtoupper(bin2hex(random_bytes(4)));
db_run(
    'INSERT INTO bookings (reference, user_id, booking_type, package_id, hotel_id, start_date, end_date, people, rooms, total, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [$reference, current_user()['id'], $type, $type === 'package' ? $id : null, $type === 'hotel' ? $id : null,
     $start, $end, $people, $rooms, $total, $notes ?: null]
);

flash('Booking ' . $reference . ' created. Choose how you would like to pay.');
redirect('tourist/pay.php?id=' . db()->lastInsertId());
