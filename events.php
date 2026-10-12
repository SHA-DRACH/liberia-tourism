<?php
/**
 * Events and festivals calendar.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$upcoming = db_all("SELECT * FROM events WHERE status = 'active' AND COALESCE(end_date, event_date) >= CURDATE() ORDER BY event_date");
$past     = db_all("SELECT * FROM events WHERE status = 'active' AND COALESCE(end_date, event_date) < CURDATE() ORDER BY event_date DESC LIMIT 6");

$pageTitle = 'Events';
include __DIR__ . '/includes/header.php';
page_hero('Events and Festivals', 'National celebrations and cultural events to plan your visit around.');
?>
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Upcoming events</h2></div>
        <?php if ($upcoming): ?>
            <div class="event-list two"><?php array_map('event_item', $upcoming); ?></div>
        <?php else: ?>
            <p class="empty">No upcoming events have been announced yet. Please check again soon.</p>
        <?php endif; ?>

        <?php if ($past): ?>
            <div class="section-head" style="margin-top:3rem"><h2>Past events</h2></div>
            <div class="event-list two"><?php array_map('event_item', $past); ?></div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
