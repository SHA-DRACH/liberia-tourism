<?php
/**
 * Tour packages list with filters (keyword, length of trip, budget).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$q      = trim($_GET['q'] ?? '');
$length = $_GET['length'] ?? '';
$budget = (int)($_GET['budget'] ?? 0);

$where  = ["p.status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.summary LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if ($length === 'day') {
    $where[] = 'p.duration_days = 1';
} elseif ($length === 'multi') {
    $where[] = 'p.duration_days > 1';
}
if ($budget > 0) {
    $where[]  = 'p.price <= ?';
    $params[] = $budget;
}

$packages = db_all('SELECT p.*, g.full_name AS guide FROM packages p LEFT JOIN guides g ON g.id = p.guide_id
                    WHERE ' . implode(' AND ', $where) . ' ORDER BY p.is_featured DESC, p.price', $params);

$pageTitle = 'Tour Packages';
include __DIR__ . '/includes/header.php';
page_hero('Tour Packages', 'Guided trips with transport, meals and entry fees arranged for you.');
?>
<section class="section">
    <div class="container">
        <form class="filters" method="get">
            <div class="field">
                <label for="q">Keyword</label>
                <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. surf, rainforest">
            </div>
            <div class="field">
                <label for="length">Length of trip</label>
                <select id="length" name="length">
                    <option value="">Any length</option>
                    <option value="day"<?= $length === 'day' ? ' selected' : '' ?>>Day trip</option>
                    <option value="multi"<?= $length === 'multi' ? ' selected' : '' ?>>Two days or more</option>
                </select>
            </div>
            <div class="field">
                <label for="budget">Maximum price per person (USD)</label>
                <input type="number" id="budget" name="budget" min="0" step="10" value="<?= $budget ?: '' ?>" placeholder="Any budget">
            </div>
            <div class="field">
                <button class="btn btn-navy" type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>

        <?php if ($packages): ?>
            <div class="grid"><?php array_map('card_package', $packages); ?></div>
        <?php else: ?>
            <p class="empty">No packages match your search. <a href="<?= url('packages.php') ?>">Clear the filters</a>.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
