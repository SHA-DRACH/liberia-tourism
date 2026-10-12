<?php
/**
 * Tour guides directory.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$guides = db_all("SELECT g.*, (SELECT COUNT(*) FROM packages p WHERE p.guide_id = g.id AND p.status = 'active') AS tours
                  FROM guides g WHERE g.status = 'active' ORDER BY g.experience_years DESC");

$pageTitle = 'Tour Guides';
include __DIR__ . '/includes/header.php';
page_hero('Tour Guides', 'Meet the local experts who lead our tours.');
?>
<section class="section">
    <div class="container">
        <?php if ($guides): ?>
            <div class="grid">
                <?php foreach ($guides as $g): ?>
                    <article class="card guide reveal">
                        <?= media($g['image'], $g['full_name']) ?>
                        <h3><?= e($g['full_name']) ?></h3>
                        <p class="card-meta" style="justify-content:center">
                            <span><?= icon('clock') ?> <?= (int)$g['experience_years'] ?> years</span>
                            <span><?= icon('compass') ?> <?= (int)$g['tours'] ?> tour<?= $g['tours'] == 1 ? '' : 's' ?></span>
                        </p>
                        <p class="card-text"><?= e($g['bio']) ?></p>
                        <p class="muted"><?= icon('chat') ?> <?= e($g['languages']) ?></p>
                        <p><?= icon('phone') ?> <?= e($g['phone']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">Guide profiles will be published here soon.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
