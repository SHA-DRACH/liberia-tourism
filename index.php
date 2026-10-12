<?php
/**
 * Home page. Every heading, paragraph, button and link shown here is
 * stored in the database and can be changed from Admin > Site content.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';

$destinations = db_all("SELECT d.*, c.name AS county FROM destinations d JOIN counties c ON c.id = d.county_id
                        WHERE d.status = 'active' ORDER BY d.is_featured DESC, d.id LIMIT 6");
$packages     = db_all("SELECT p.*, g.full_name AS guide FROM packages p LEFT JOIN guides g ON g.id = p.guide_id
                        WHERE p.status = 'active' ORDER BY p.is_featured DESC, p.id LIMIT 3");
$hotels       = db_all("SELECT h.*, c.name AS county FROM hotels h JOIN counties c ON c.id = h.county_id
                        WHERE h.status = 'active' ORDER BY h.is_featured DESC, h.id LIMIT 3");
$events       = db_all("SELECT * FROM events WHERE status = 'active' AND COALESCE(end_date, event_date) >= CURDATE()
                        ORDER BY event_date LIMIT 4");
$reviews      = db_all("SELECT r.*, u.full_name, d.name AS destination FROM reviews r
                        JOIN users u ON u.id = r.user_id JOIN destinations d ON d.id = r.destination_id
                        WHERE r.status = 'approved' ORDER BY r.created_at DESC, r.id DESC LIMIT 3");
$stats = [
    'Destinations'  => db_val("SELECT COUNT(*) FROM destinations WHERE status = 'active'"),
    'Counties'      => db_val('SELECT COUNT(*) FROM counties'),
    'Tour packages' => db_val("SELECT COUNT(*) FROM packages WHERE status = 'active'"),
    'Local guides'  => db_val("SELECT COUNT(*) FROM guides WHERE status = 'active'"),
];

$heroImage = setting('hero_image');
$hasHero   = $heroImage && is_file(ROOT_PATH . '/' . $heroImage);
include __DIR__ . '/includes/header.php';
?>

<section class="hero<?= $hasHero ? ' has-image' : '' ?>"<?= $hasHero ? ' style="background-image:url(\'' . e(url($heroImage)) . '\')"' : '' ?>>
    <svg class="hero-star" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z"/></svg>
    <div class="container">
        <div class="hero-inner">
            <span class="eyebrow">Republic of Liberia · West Africa</span>
            <h1><?= e(setting('hero_title')) ?></h1>
            <p class="lead"><?= e(setting('hero_subtitle')) ?></p>

            <div class="search-box" role="search">
                <?= icon('search') ?>
                <label class="skip-link" for="site-search">Search destinations, tours and hotels</label>
                <input type="search" id="site-search" placeholder="Search destinations, tours and hotels…" autocomplete="off">
                <div class="search-results" id="search-results" hidden></div>
            </div>

            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url(setting('hero_btn_link', 'destinations.php'))) ?>"><?= e(setting('hero_btn_text')) ?></a>
                <?php if (setting('hero_btn2_text')): ?>
                    <a class="btn btn-outline-light" href="<?= e(url(setting('hero_btn2_link', 'packages.php'))) ?>"><?= e(setting('hero_btn2_text')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="hero-stripes" aria-hidden="true"></div>
</section>

<section class="stats" aria-label="At a glance">
    <div class="container stats-grid">
        <?php foreach ($stats as $label => $number): ?>
            <div><strong><?= (int)$number ?></strong><span><?= $label ?></span></div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2><?= e(setting('home_dest_title')) ?></h2>
                <p><?= e(setting('home_dest_text')) ?></p>
            </div>
            <a class="btn btn-ghost" href="<?= url('destinations.php') ?>">All destinations</a>
        </div>
        <div class="grid"><?php array_map('card_destination', $destinations); ?></div>
    </div>
</section>

<section class="section section-alt">
    <div class="container split">
        <div class="reveal"><?= media(setting('about_image'), 'Liberia') ?></div>
        <div class="reveal">
            <h2><?= e(setting('about_title')) ?></h2>
            <p><?= nl2br(e(setting('about_text'))) ?></p>
            <a class="btn btn-navy" href="<?= e(url(setting('about_btn_link', 'page.php?slug=about'))) ?>"><?= e(setting('about_btn_text')) ?></a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2><?= e(setting('home_pkg_title')) ?></h2>
                <p><?= e(setting('home_pkg_text')) ?></p>
            </div>
            <a class="btn btn-ghost" href="<?= url('packages.php') ?>">All packages</a>
        </div>
        <div class="grid"><?php array_map('card_package', $packages); ?></div>
    </div>
</section>

<section class="section section-alt">
    <div class="container features">
        <?php foreach ([1 => 'compass', 2 => 'check', 3 => 'map'] as $n => $iconName): ?>
            <div class="feature reveal">
                <?= icon($iconName) ?>
                <h3><?= e(setting("feature{$n}_title")) ?></h3>
                <p><?= e(setting("feature{$n}_text")) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2><?= e(setting('home_hotel_title')) ?></h2>
                <p><?= e(setting('home_hotel_text')) ?></p>
            </div>
            <a class="btn btn-ghost" href="<?= url('hotels.php') ?>">All hotels</a>
        </div>
        <div class="grid"><?php array_map('card_hotel', $hotels); ?></div>
    </div>
</section>

<?php if ($events): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2><?= e(setting('home_event_title')) ?></h2>
            <a class="btn btn-ghost" href="<?= url('events.php') ?>">Full calendar</a>
        </div>
        <div class="event-list two"><?php array_map('event_item', $events); ?></div>
    </div>
</section>
<?php endif; ?>

<?php if ($reviews): ?>
<section class="section">
    <div class="container">
        <div class="section-head"><h2><?= e(setting('home_review_title')) ?></h2></div>
        <div class="grid">
            <?php foreach ($reviews as $r): ?>
                <figure class="review reveal">
                    <?= stars($r['rating']) ?>
                    <blockquote>“<?= e($r['comment']) ?>”</blockquote>
                    <figcaption><cite><?= e($r['full_name']) ?> on <a href="<?= url('destination.php?id=' . $r['destination_id']) ?>"><?= e($r['destination']) ?></a></cite></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section cta">
    <div class="container">
        <h2><?= e(setting('cta_title')) ?></h2>
        <p><?= e(setting('cta_text')) ?></p>
        <a class="btn btn-light" href="<?= e(url(setting('cta_btn_link', 'register.php'))) ?>"><?= e(setting('cta_btn_text')) ?></a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
