<?php
/**
 * Photo credits. The sample photographs come from Wikimedia Commons under
 * free licences that require the author and licence to be named.
 * The list is read from assets/images/photos/credits.json.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$file    = ROOT_PATH . '/assets/images/photos/credits.json';
$credits = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

$pageTitle = 'Photo Credits';
include __DIR__ . '/includes/header.php';
page_hero('Photo Credits', 'The sample photographs on this site are from Wikimedia Commons and are used under free licences.');
?>
<section class="section">
    <div class="container">
        <p class="muted">Photographs were resized for the web. Hotels in this demonstration are sample entries, so their pictures show scenery of the area rather than the hotel itself. Photos uploaded later through the admin panel are not listed here.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Photo</th><th>Used for</th><th>Original title</th><th>Author</th><th>Licence</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($credits as $c): ?>
                        <tr>
                            <td><?= media('assets/images/photos/' . $c['file'], $c['used_for'], 'credit-thumb') ?></td>
                            <td><strong><?= e($c['used_for']) ?></strong><?= $c['note'] ? '<br><small class="muted">' . e($c['note']) . '</small>' : '' ?></td>
                            <td><a href="<?= e($c['source']) ?>" target="_blank" rel="noopener"><?= e($c['title']) ?></a></td>
                            <td><?= e($c['author']) ?></td>
                            <td><?= e($c['licence']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$credits): ?><tr><td colspan="5" class="muted text-center">No sample photographs are installed.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
