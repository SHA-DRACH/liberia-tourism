<?php
/**
 * Destinations list with search and filters (keyword, county, category).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$q        = trim($_GET['q'] ?? '');
$county   = (int)($_GET['county'] ?? 0);
$category = trim($_GET['category'] ?? '');
$categories = ['Beach', 'Nature & Wildlife', 'Historical', 'Cultural', 'Waterfall'];

// Build the WHERE clause from the filters the visitor chose
$where  = ["d.status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = '(d.name LIKE ? OR d.summary LIKE ? OR d.location LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($county) {
    $where[]  = 'd.county_id = ?';
    $params[] = $county;
}
if (in_array($category, $categories, true)) {
    $where[]  = 'd.category = ?';
    $params[] = $category;
}

$destinations = db_all('SELECT d.*, c.name AS county FROM destinations d JOIN counties c ON c.id = d.county_id
                        WHERE ' . implode(' AND ', $where) . ' ORDER BY d.is_featured DESC, d.name', $params);
$counties     = db_all('SELECT id, name FROM counties ORDER BY name');

$pageTitle = 'Destinations';
include __DIR__ . '/includes/header.php';
page_hero('Destinations', 'Beaches, rainforest, waterfalls and historic sites across the fifteen counties of Liberia.');
?>
<section class="section">
    <div class="container">
        <form class="filters" method="get">
            <div class="field">
                <label for="q">Keyword</label>
                <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. beach, Monrovia">
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
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option<?= $category === $cat ? ' selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <button class="btn btn-navy" type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>

        <p class="muted"><?= count($destinations) ?> destination<?= count($destinations) === 1 ? '' : 's' ?> found</p>
        <?php if ($destinations): ?>
            <div class="grid"><?php array_map('card_destination', $destinations); ?></div>
        <?php else: ?>
            <p class="empty">No destinations match your search. <a href="<?= url('destinations.php') ?>">Clear the filters</a>.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
