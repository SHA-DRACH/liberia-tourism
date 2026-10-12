<?php
/**
 * Hotels list with filters (keyword, county, price).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$q      = trim($_GET['q'] ?? '');
$county = (int)($_GET['county'] ?? 0);
$budget = (int)($_GET['budget'] ?? 0);

$where  = ["h.status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = '(h.name LIKE ? OR h.address LIKE ? OR h.amenities LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($county) {
    $where[]  = 'h.county_id = ?';
    $params[] = $county;
}
if ($budget > 0) {
    $where[]  = 'h.price_per_night <= ?';
    $params[] = $budget;
}

$hotels   = db_all('SELECT h.*, c.name AS county FROM hotels h JOIN counties c ON c.id = h.county_id
                    WHERE ' . implode(' AND ', $where) . ' ORDER BY h.is_featured DESC, h.price_per_night', $params);
$counties = db_all('SELECT DISTINCT c.id, c.name FROM counties c JOIN hotels h ON h.county_id = c.id ORDER BY c.name');

$pageTitle = 'Hotels';
include __DIR__ . '/includes/header.php';
page_hero('Hotels and Accommodation', 'City hotels, beach lodges and mountain guesthouses for every budget.');
?>
<section class="section">
    <div class="container">
        <form class="filters" method="get">
            <div class="field">
                <label for="q">Keyword</label>
                <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. pool, beachfront">
            </div>
            <div class="field">
                <label for="county">County</label>
                <select id="county" name="county">
                    <option value="">All counties</option>
                    <?php foreach ($counties as $c): ?>
                        <option value="<?= $c['id'] ?>"<?= $county === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="budget">Maximum price per night (USD)</label>
                <input type="number" id="budget" name="budget" min="0" step="5" value="<?= $budget ?: '' ?>" placeholder="Any budget">
            </div>
            <div class="field">
                <button class="btn btn-navy" type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>

        <?php if ($hotels): ?>
            <div class="grid"><?php array_map('card_hotel', $hotels); ?></div>
        <?php else: ?>
            <p class="empty">No hotels match your search. <a href="<?= url('hotels.php') ?>">Clear the filters</a>.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
