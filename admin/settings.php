<?php
/**
 * Site content editor: every text, image and button link shown on the
 * home page, contact page and footer. Administrators only.
 */
require_once __DIR__ . '/_init.php';
require_role('admin');

$all    = db_all('SELECT * FROM settings ORDER BY grp, sort_order');
$settingGroups = [];
foreach ($all as $s) {
    $settingGroups[$s['grp']][] = $s;
}
// Show the tabs in a sensible order
$order  = array_values(array_intersect(['General', 'Home page', 'Contact', 'Footer'], array_keys($settingGroups)));
$tab    = in_array($_GET['tab'] ?? '', $order, true) ? $_GET['tab'] : $order[0];

if (is_post()) {
    csrf_check();
    foreach ($settingGroups[$tab] as $s) {
        $key = $s['skey'];
        if ($s['type'] !== 'image' && !array_key_exists($key, $_POST)) {
            continue; // field was not on the submitted form
        }
        if ($s['type'] === 'image') {
            $value = upload_image($key, $s['svalue'] ?: null);
            if (!empty($_POST[$key . '_remove']) && $value === ($s['svalue'] ?: null)) {
                if ($value && is_file(ROOT_PATH . '/' . $value)) {
                    unlink(ROOT_PATH . '/' . $value);
                }
                $value = '';
            }
        } else {
            $value = trim($_POST[$key] ?? '');
            if ($s['type'] === 'number' && !is_numeric($value)) {
                flash($s['label'] . ' must be a number, so it was not changed.', 'error');
                continue;
            }
            if ($s['type'] === 'url' && $value !== '' && !preg_match('~^https?://~i', $value)) {
                flash($s['label'] . ' must start with http:// or https://, so it was not changed.', 'error');
                continue;
            }
        }
        db_run('UPDATE settings SET svalue = ? WHERE skey = ?', [(string)$value, $key]);
    }
    flash($tab . ' content saved. The website has been updated.');
    redirect('admin/settings.php?tab=' . urlencode($tab));
}

$pageTitle = 'Home page and settings';
$activeNav = 'settings';
include __DIR__ . '/_header.php';
?>
<nav class="tabs" aria-label="Content sections">
    <?php foreach ($order as $name): ?>
        <a href="<?= url('admin/settings.php?tab=' . urlencode($name)) ?>"<?= $name === $tab ? ' class="active" aria-current="page"' : '' ?>><?= e($name) ?></a>
    <?php endforeach; ?>
</nav>

<form method="post" enctype="multipart/form-data" class="panel admin-form">
    <?= csrf_field() ?>
    <?php foreach ($settingGroups[$tab] as $s): $key = $s['skey']; ?>
        <div class="field<?= $s['type'] === 'textarea' ? ' wide' : '' ?>">
            <label for="<?= e($key) ?>"><?= e($s['label']) ?></label>
            <?php if ($s['type'] === 'textarea'): ?>
                <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3" style="min-height:80px"><?= e($s['svalue']) ?></textarea>
            <?php elseif ($s['type'] === 'image'): ?>
                <?php if ($s['svalue']): ?>
                    <div class="current-image">
                        <?= media($s['svalue'], '', 'thumb') ?>
                        <label class="check"><input type="checkbox" name="<?= e($key) ?>_remove" value="1"> Remove this image</label>
                    </div>
                <?php endif; ?>
                <input type="file" id="<?= e($key) ?>" name="<?= e($key) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
                <span class="hint">JPG, PNG, WEBP or GIF, up to 3 MB.</span>
            <?php else: ?>
                <input type="<?= $s['type'] === 'number' ? 'number' : ($s['type'] === 'url' ? 'url' : 'text') ?>" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($s['svalue']) ?>"<?= $s['type'] === 'number' ? ' step="any" min="0"' : '' ?>>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="field wide form-actions">
        <button class="btn btn-primary" type="submit">Save <?= e(strtolower($tab)) ?> content</button>
        <a class="btn btn-ghost" href="<?= url('index.php') ?>" target="_blank" rel="noopener">View website ↗</a>
    </div>
</form>
<p class="muted" style="margin-top:1rem">Menu links are edited under <a href="<?= url('admin/crud.php?t=menu_links') ?>">Menu links</a>, and full pages such as About or FAQ under <a href="<?= url('admin/crud.php?t=pages') ?>">Pages</a>.</p>
<?php include __DIR__ . '/_footer.php'; ?>
