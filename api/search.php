<?php
/**
 * JSON search endpoint used by the live search box on the home page.
 * Example: api/search.php?q=beach
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    exit('[]');
}
$like = '%' . $q . '%';

$rows = db_all(
    "(SELECT 'Destination' AS type, id, name AS title, 'destination.php' AS page FROM destinations
        WHERE status = 'active' AND (name LIKE ? OR summary LIKE ? OR category LIKE ?) LIMIT 5)
     UNION ALL
     (SELECT 'Tour package', id, title, 'package.php' FROM packages
        WHERE status = 'active' AND (title LIKE ? OR summary LIKE ?) LIMIT 4)
     UNION ALL
     (SELECT 'Hotel', id, name, 'hotel.php' FROM hotels
        WHERE status = 'active' AND (name LIKE ? OR address LIKE ?) LIMIT 4)",
    [$like, $like, $like, $like, $like, $like, $like]
);

$results = [];
foreach ($rows as $row) {
    $results[] = ['type' => $row['type'], 'title' => $row['title'], 'url' => url($row['page'] . '?id=' . $row['id'])];
}
echo json_encode($results);
