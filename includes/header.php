<?php
/**
 * Public site header. Set $pageTitle before including this file.
 * Navigation links come from the menu_links table, so the admin controls them.
 */
require_once __DIR__ . '/functions.php';

$user     = current_user();
$siteName = setting('site_name', 'Visit Liberia');
$navLinks = db_all("SELECT label, url FROM menu_links WHERE location = 'header' AND is_active = 1 ORDER BY sort_order, id");
$here     = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName . ' | ' . setting('site_tagline')) ?></title>
    <meta name="description" content="<?= e(excerpt(setting('hero_subtitle'), 155)) ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('index.php') ?>">
            <?php if (setting('site_logo') && is_file(ROOT_PATH . '/' . setting('site_logo'))): ?>
                <img src="<?= e(url(setting('site_logo'))) ?>" alt="" class="brand-logo">
            <?php else: ?>
                <span class="brand-mark" aria-hidden="true">★</span>
            <?php endif; ?>
            <span class="brand-text">
                <strong><?= e($siteName) ?></strong>
                <small><?= e(setting('site_tagline')) ?></small>
            </span>
        </a>

        <button class="nav-toggle" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>

        <nav id="site-nav" class="site-nav" aria-label="Main">
            <ul>
                <?php foreach ($navLinks as $link):
                    $href   = url($link['url']);
                    $active = $href === $here || ($link['url'] === 'index.php' && $here === BASE_URL); ?>
                    <li><a href="<?= e($href) ?>"<?= $active ? ' class="active" aria-current="page"' : '' ?>><?= e($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <a class="btn btn-ghost" href="<?= url(home_for($user)) ?>"><?= $user['role'] === 'tourist' ? 'My bookings' : 'Admin panel' ?></a>
                    <a class="btn btn-primary" href="<?= url('logout.php') ?>">Sign out</a>
                <?php else: ?>
                    <a class="btn btn-ghost" href="<?= url('login.php') ?>">Sign in</a>
                    <a class="btn btn-primary" href="<?= url('register.php') ?>">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main id="main">
<?php foreach (flashes() as $f): ?>
    <div class="container"><div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div></div>
<?php endforeach; ?>
