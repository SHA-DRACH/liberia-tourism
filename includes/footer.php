<?php
/**
 * Public site footer. Links, contact details and social links are managed by the admin.
 */
$footerLinks = db_all("SELECT label, url FROM menu_links WHERE location = 'footer' AND is_active = 1 ORDER BY sort_order, id");
$exploreLinks = db_all("SELECT label, url FROM menu_links WHERE location = 'explore' AND is_active = 1 ORDER BY sort_order, id");
$socials     =['Facebook' => 'facebook_url', 'Instagram' => 'instagram_url', 'X' => 'x_url', 'YouTube' => 'youtube_url'];
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="footer-brand"><span aria-hidden="true">★</span> <?= e(setting('site_name', 'Visit Liberia')) ?></p>
            <p><?= e(setting('footer_about')) ?></p>
            <p class="footer-social">
                <?php foreach ($socials as $name => $key): if (setting($key)): ?>
                    <a href="<?= e(setting($key)) ?>" target="_blank" rel="noopener"><?= $name ?></a>
                <?php endif; endforeach; ?>
            </p>
        </div>
        <div>
            <h2>Explore</h2>
            <ul>
                <?php foreach ($exploreLinks as $link): ?>
                    <li><a href="<?= e(url($link['url'])) ?>"><?= e($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h2>Information</h2>
            <ul>
                <?php foreach ($footerLinks as $link): ?>
                    <li><a href="<?= e(url($link['url'])) ?>"><?= e($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h2>Contact</h2>
            <ul class="footer-contact">
                <li><?= icon('pin') ?> <?= e(setting('contact_address')) ?></li>
                <li><?= icon('phone') ?> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a></li>
                <li><?= icon('mail') ?> <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
                <li><?= icon('clock') ?> <?= e(setting('contact_hours')) ?></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">&copy; <?= date('Y') ?> <?= e(setting('footer_copyright')) ?></div>
    </div>
</footer>

<script>window.APP_BASE = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
