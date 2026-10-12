<?php
/**
 * Shows any page written by the admin (About, FAQ, Terms and so on).
 * Example: page.php?slug=about
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$page = db_one("SELECT * FROM pages WHERE slug = ? AND status = 'active'", [$_GET['slug'] ?? '']);
if (!$page) {
    http_response_code(404);
    $page = ['title' => 'Page not found', 'content' => 'Sorry, the page you are looking for does not exist or has been removed.'];
}

$pageTitle = $page['title'];
include __DIR__ . '/includes/header.php';
page_hero($page['title']);
?>
<section class="section">
    <div class="container">
        <article class="panel prose" style="max-width:820px;margin-inline:auto"><?= rich_text($page['content']) ?></article>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
