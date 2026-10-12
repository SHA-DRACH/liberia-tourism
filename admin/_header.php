<?php
/**
 * Admin layout: sidebar and top bar. Set $pageTitle and $activeNav before including.
 * The sidebar is built from entities.php, so each role only sees what it may manage.
 */
$me        = current_user();
$activeNav = $activeNav ?? '';
$newEnquiries    = (int)db_val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
$pendingBookings = (int)db_val("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$pendingReviews  = (int)db_val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
$counts = ['enquiries' => $newEnquiries, 'reviews' => $pendingReviews];

// Group the sections for the sidebar
$groups = [];
foreach ($entities as $key => $entity) {
    if (can_manage($entity)) {
        $groups[$entity['group']][$key] = $entity['title'];
    }
}
$navLink = function (string $key, string $href, string $label, int $count = 0) use ($activeNav): string {
    return '<li><a href="' . url($href) . '"' . ($activeNav === $key ? ' class="active" aria-current="page"' : '') . '>' . e($label)
         . ($count ? ' <span class="count">' . $count . '</span>' : '') . '</a></li>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(ucfirst($pageTitle ?? 'Admin')) ?> | <?= e(setting('site_name')) ?> Admin</title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin">
<aside class="admin-side" id="admin-side">
    <a class="admin-brand" href="<?= url('admin/index.php') ?>"><span aria-hidden="true">★</span> <?= e(setting('site_name')) ?></a>
    <nav aria-label="Admin">
        <ul>
            <?= $navLink('dashboard', 'admin/index.php', 'Dashboard') ?>
            <?= $navLink('bookings', 'admin/bookings.php', 'Bookings', $pendingBookings) ?>
            <?= $navLink('reports', 'admin/reports.php', 'Reports') ?>
        </ul>
        <?php foreach ($groups as $group => $items): ?>
            <h2><?= e($group) ?></h2>
            <ul>
                <?php if ($group === 'Site content' && has_role('admin')): ?>
                    <?= $navLink('settings', 'admin/settings.php', 'Home page and settings') ?>
                <?php endif; ?>
                <?php foreach ($items as $key => $title): ?>
                    <?= $navLink($key, 'admin/crud.php?t=' . $key, $title, $counts[$key] ?? 0) ?>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
        <h2>Account</h2>
        <ul>
            <li><a href="<?= url('index.php') ?>" target="_blank" rel="noopener">View website ↗</a></li>
            <li><a href="<?= url('logout.php') ?>">Sign out</a></li>
        </ul>
    </nav>
</aside>

<div class="admin-main">
    <header class="admin-top">
        <button class="nav-toggle" aria-controls="admin-side" aria-expanded="false" aria-label="Open menu"><span></span><span></span><span></span></button>
        <h1><?= e(ucfirst($pageTitle ?? 'Admin')) ?></h1>
        <div class="admin-user"><?= e($me['full_name']) ?> <?= status_badge($me['role']) ?></div>
    </header>
    <div class="admin-content">
        <?php foreach (flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
