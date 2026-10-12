<?php
/**
 * Reusable card layouts, so every listing page shows items the same way.
 */

function card_destination(array $d): void { ?>
    <article class="card reveal">
        <div class="card-media">
            <?= media($d['image'], $d['name']) ?>
            <span class="card-tag"><?= e($d['category']) ?></span>
        </div>
        <div class="card-body">
            <h3><a href="<?= url('destination.php?id=' . $d['id']) ?>"><?= e($d['name']) ?></a></h3>
            <p class="card-meta"><span><?= icon('pin') ?> <?= e($d['county']) ?> County</span></p>
            <p class="card-text"><?= e(excerpt($d['summary'], 110)) ?></p>
            <div class="card-foot">
                <span class="price"><?= $d['entry_fee'] > 0 ? money($d['entry_fee']) . '<small>entry fee</small>' : 'Free<small>entry</small>' ?></span>
                <span class="link-more">View details →</span>
            </div>
        </div>
    </article>
<?php }

function card_package(array $p): void { ?>
    <article class="card reveal">
        <div class="card-media">
            <?= media($p['image'], $p['title']) ?>
            <span class="card-tag"><?= (int)$p['duration_days'] ?> day<?= $p['duration_days'] > 1 ? 's' : '' ?></span>
        </div>
        <div class="card-body">
            <h3><a href="<?= url('package.php?id=' . $p['id']) ?>"><?= e($p['title']) ?></a></h3>
            <p class="card-meta">
                <span><?= icon('users') ?> Up to <?= (int)$p['max_people'] ?> people</span>
                <?php if (!empty($p['guide'])): ?><span><?= icon('compass') ?> <?= e($p['guide']) ?></span><?php endif; ?>
            </p>
            <p class="card-text"><?= e(excerpt($p['summary'], 110)) ?></p>
            <div class="card-foot">
                <span class="price"><?= money($p['price']) ?><small>per person · <?= lrd($p['price']) ?></small></span>
                <span class="link-more">Book now →</span>
            </div>
        </div>
    </article>
<?php }

function card_hotel(array $h): void { ?>
    <article class="card reveal">
        <div class="card-media">
            <?= media($h['image'], $h['name']) ?>
            <span class="card-tag"><?= e($h['county']) ?></span>
        </div>
        <div class="card-body">
            <h3><a href="<?= url('hotel.php?id=' . $h['id']) ?>"><?= e($h['name']) ?></a></h3>
            <p class="card-meta"><?= stars($h['stars']) ?> <span><?= icon('pin') ?> <?= e($h['address']) ?></span></p>
            <p class="card-text"><?= e(excerpt($h['description'], 110)) ?></p>
            <div class="card-foot">
                <span class="price"><?= money($h['price_per_night']) ?><small>per night · <?= lrd($h['price_per_night']) ?></small></span>
                <span class="link-more">Reserve →</span>
            </div>
        </div>
    </article>
<?php }

function event_item(array $ev): void { ?>
    <article class="event reveal">
        <div class="event-date">
            <strong><?= nice_date($ev['event_date'], 'd') ?></strong>
            <span><?= nice_date($ev['event_date'], 'M Y') ?></span>
        </div>
        <div>
            <h3><?= e($ev['title']) ?></h3>
            <p class="card-meta">
                <span><?= icon('pin') ?> <?= e($ev['location']) ?></span>
                <?php if ($ev['end_date']): ?><span><?= icon('calendar') ?> until <?= nice_date($ev['end_date']) ?></span><?php endif; ?>
            </p>
            <p><?= e($ev['description']) ?></p>
        </div>
    </article>
<?php }

/** Banner at the top of inner pages. */
function page_hero(string $title, string $text = '', array $crumbs = []): void { ?>
    <section class="page-hero">
        <div class="container">
            <p class="crumbs">
                <a href="<?= url('index.php') ?>">Home</a>
                <?php foreach ($crumbs as $label => $link): ?> / <a href="<?= url($link) ?>"><?= e($label) ?></a><?php endforeach; ?>
                / <?= e($title) ?>
            </p>
            <h1><?= e($title) ?></h1>
            <?php if ($text): ?><p><?= e($text) ?></p><?php endif; ?>
        </div>
    </section>
<?php }
