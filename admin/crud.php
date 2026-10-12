<?php
/**
 * Generic Create / Read / Update / Delete screen.
 * One file serves every section listed in entities.php.
 *   crud.php?t=hotels               list
 *   crud.php?t=hotels&a=create      add form
 *   crud.php?t=hotels&a=edit&id=3   edit form
 *   crud.php?t=hotels&a=delete&id=3 delete (POST only)
 * Table and column names come only from entities.php, never from the browser.
 */
require_once __DIR__ . '/_init.php';

$t = $_GET['t'] ?? '';
$E = $entities[$t] ?? null;
if (!$E) {
    http_response_code(404);
    exit('Unknown section.');
}
if (!can_manage($E)) {
    http_response_code(403);
    exit('Access denied. Only an administrator can open this section.');
}

$me        = current_user();
$action    = $_GET['a'] ?? 'list';
$id        = (int)($_GET['id'] ?? 0);
$self      = 'admin/crud.php?t=' . $t;
$canCreate = $E['can_create'] ?? true;

// Load the choices for every select and checkbox list
$options = [];
foreach ($E['fields'] as $name => $f) {
    if (isset($f['options_sql'])) {
        $options[$name] = array_column(db_all($f['options_sql']), 'label', 'id');
    } elseif (isset($f['options'])) {
        $options[$name] = array_is_list($f['options']) ? array_combine($f['options'], $f['options']) : $f['options'];
    }
}

$row = $id ? db_one("SELECT * FROM `$t` WHERE id = ?", [$id]) : null;
if ($id && !$row) {
    flash('That record no longer exists.', 'error');
    redirect($self);
}
$isSelf = $t === 'users' && $row && (int)$row['id'] === (int)$me['id'];

/* ---------------- Delete ---------------- */
if (is_post() && $action === 'delete' && $row) {
    csrf_check();
    if ($isSelf) {
        flash('You cannot delete the account you are signed in with.', 'error');
    } else {
        try {
            db_run("DELETE FROM `$t` WHERE id = ?", [$id]);
            foreach ($E['fields'] as $name => $f) {
                if ($f['type'] === 'image' && !empty($row[$name]) && is_file(ROOT_PATH . '/' . $row[$name])) {
                    unlink(ROOT_PATH . '/' . $row[$name]);
                }
            }
            flash(ucfirst($E['singular']) . ' deleted.');
        } catch (PDOException $ex) {
            // Foreign key: the record is still linked to other data
            flash('This ' . $E['singular'] . ' is linked to other records (for example bookings) and cannot be deleted. Hide or suspend it instead.', 'error');
        }
    }
    redirect($self);
}

/* ---------------- Create / update ---------------- */
$errors = [];
$values = $row ?? [];
$picked = []; // ticked boxes of pivot fields

if (is_post() && ($action === 'edit' && $row || $action === 'create' && $canCreate)) {
    csrf_check();
    $data = [];
    foreach ($E['fields'] as $name => $f) {
        if (!empty($f['locked'])) {
            continue;
        }
        $raw   = $_POST[$name] ?? '';
        $label = $f['label'];
        switch ($f['type']) {
            case 'pivot':
                $picked[$name] = array_values(array_intersect(array_map('intval', (array)$raw), array_keys($options[$name])));
                continue 2;
            case 'image':
                $data[$name] = upload_image($name, $row[$name] ?? null);
                if (!empty($_POST[$name . '_remove']) && $data[$name] === ($row[$name] ?? null)) {
                    $data[$name] = null;
                }
                continue 2;
            case 'bool':
                $data[$name] = empty($raw) ? 0 : 1;
                continue 2;
            case 'password':
                if ($raw === '' && $row) {
                    continue 2; // keep the current password
                }
                if (strlen($raw) < 8) {
                    $errors[] = 'The password must be at least 8 characters.';
                }
                $data[$f['column']] = password_hash($raw, PASSWORD_DEFAULT);
                continue 2;
        }

        $value = trim((string)$raw);
        if ($value === '') {
            if (!empty($f['required'])) {
                $errors[] = "$label is required.";
            }
            // Empty optional numbers, dates and selects are stored as NULL / 0
            $data[$name] = match ($f['type']) {
                'number', 'money' => 0,
                'date', 'select'  => null,
                default           => '',
            };
            continue;
        }
        if ($f['type'] === 'select' && !array_key_exists($value, $options[$name])) {
            $errors[] = "Please choose a valid option for $label.";
        } elseif ($f['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "$label is not a valid email address.";
        } elseif (in_array($f['type'], ['number', 'money'], true) && (!is_numeric($value) || $value < ($f['min'] ?? 0))) {
            $errors[] = "$label must be a number of at least " . ($f['min'] ?? 0) . '.';
        } elseif ($f['type'] === 'date' && !strtotime($value)) {
            $errors[] = "$label is not a valid date.";
        } elseif (isset($f['pattern']) && !preg_match('~^' . $f['pattern'] . '$~', $value)) {
            $errors[] = "$label has an invalid format.";
        }
        $data[$name] = $value;
    }

    // An admin cannot demote or suspend their own account by mistake
    if ($isSelf) {
        $data['role']   = $row['role'];
        $data['status'] = $row['status'];
    }

    if (!$errors) {
        try {
            $columns = array_keys($data);
            if ($row) {
                $set = implode(', ', array_map(fn($c) => "`$c` = ?", $columns));
                db_run("UPDATE `$t` SET $set WHERE id = ?", [...array_values($data), $id]);
            } else {
                $marks = implode(', ', array_fill(0, count($columns), '?'));
                db_run("INSERT INTO `$t` (`" . implode('`, `', $columns) . "`) VALUES ($marks)", array_values($data));
                $id = (int)db()->lastInsertId();
            }
            // Many-to-many links: replace the old set with the ticked boxes
            foreach ($picked as $name => $ids) {
                $f = $E['fields'][$name];
                db_run("DELETE FROM `{$f['pivot_table']}` WHERE `{$f['self_key']}` = ?", [$id]);
                foreach ($ids as $otherId) {
                    db_run("INSERT INTO `{$f['pivot_table']}` (`{$f['self_key']}`, `{$f['other_key']}`) VALUES (?, ?)", [$id, $otherId]);
                }
            }
            flash(ucfirst($E['singular']) . ($row ? ' updated.' : ' created.'));
            redirect($self);
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errors[] = 'Another record already uses the same unique value (for example the same email, name or slug).';
        }
    }
    unset($data['password_hash']);
    $values = array_merge($values, $data);
}

/* ---------------- Page output ---------------- */
$isForm    = $action === 'edit' && $row || $action === 'create' && $canCreate;
$pageTitle = $isForm ? ($row ? 'Edit ' : 'Add ') . $E['singular'] : $E['title'];
$activeNav = $t;
include __DIR__ . '/_header.php';

/** Format one table cell according to its field type. */
$cell = function (string $col, array $r) use ($E, $options): string {
    $f = $E['fields'][$col] ?? ['type' => 'text'];
    $v = $r[$col] ?? '';
    if ($f['type'] === 'image') {
        return media($v, '', 'thumb');
    }
    if ($f['type'] === 'bool') {
        return $v ? '<span class="badge badge-yes">Yes</span>' : '<span class="muted">No</span>';
    }
    if (in_array($col, ['status', 'role'], true)) {
        return status_badge((string)$v);
    }
    if ($f['type'] === 'select') {
        return e($options[$col][$v] ?? '—');
    }
    if ($f['type'] === 'money') {
        return money($v);
    }
    if ($f['type'] === 'date' || $col === 'created_at') {
        return e(nice_date($v));
    }
    if ($col === 'rating') {
        return stars($v);
    }
    return e(excerpt((string)$v, 70));
};

if ($isForm): ?>
    <p><a href="<?= url($self) ?>">← Back to <?= e(strtolower($E['title'])) ?></a></p>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="panel admin-form">
        <?= csrf_field() ?>
        <?php foreach ($E['fields'] as $name => $f):
            $value  = $values[$name] ?? ($row ? '' : ($f['default'] ?? ''));
            $req    = !empty($f['required']) ? ' required' : '';
            $locked = !empty($f['locked']) || ($isSelf && in_array($name, ['role', 'status'], true));
            $wide   = in_array($f['type'], ['textarea', 'pivot'], true) ? ' wide' : ''; ?>
            <div class="field<?= $wide ?>">
                <?php if ($f['type'] !== 'bool'): ?>
                    <label for="<?= $name ?>"><?= e($f['label']) ?><?= $req ? ' <span class="req">*</span>' : '' ?></label>
                <?php endif; ?>

                <?php if ($locked): ?>
                    <div class="locked"><?= nl2br(e($f['type'] === 'select' ? ($options[$name][$value] ?? '—') : $value)) ?></div>

                <?php elseif ($f['type'] === 'textarea'): ?>
                    <textarea id="<?= $name ?>" name="<?= $name ?>" rows="<?= $f['rows'] ?? 5 ?>"<?= $req ?>><?= e($value) ?></textarea>

                <?php elseif ($f['type'] === 'select'): ?>
                    <select id="<?= $name ?>" name="<?= $name ?>"<?= $req ?>>
                        <?php if (!empty($f['required'])): ?>
                            <option value="">Choose…</option>
                        <?php elseif (!empty($f['nullable'])): ?>
                            <option value="">— None —</option>
                        <?php endif; ?>
                        <?php foreach ($options[$name] as $optValue => $optLabel): ?>
                            <option value="<?= e($optValue) ?>"<?= (string)$value === (string)$optValue ? ' selected' : '' ?>><?= e($optLabel) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($f['type'] === 'bool'): ?>
                    <label class="check"><input type="checkbox" name="<?= $name ?>" value="1"<?= $value ? ' checked' : '' ?>> <?= e($f['label']) ?></label>

                <?php elseif ($f['type'] === 'image'): ?>
                    <?php if ($value): ?>
                        <div class="current-image">
                            <?= media($value, '', 'thumb') ?>
                            <label class="check"><input type="checkbox" name="<?= $name ?>_remove" value="1"> Remove this photo</label>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="<?= $name ?>" name="<?= $name ?>" accept="image/jpeg,image/png,image/webp,image/gif">
                    <span class="hint">JPG, PNG, WEBP or GIF, up to 3 MB.</span>

                <?php elseif ($f['type'] === 'pivot'):
                    $ticked = $picked[$name] ?? ($row ? array_column(db_all("SELECT `{$f['other_key']}` AS v FROM `{$f['pivot_table']}` WHERE `{$f['self_key']}` = ?", [$id]), 'v') : []); ?>
                    <div class="check-grid">
                        <?php foreach ($options[$name] as $optValue => $optLabel): ?>
                            <label class="check"><input type="checkbox" name="<?= $name ?>[]" value="<?= e($optValue) ?>"<?= in_array($optValue, $ticked) ? ' checked' : '' ?>> <?= e($optLabel) ?></label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($f['type'] === 'password'): ?>
                    <input type="password" id="<?= $name ?>" name="<?= $name ?>" minlength="8" autocomplete="new-password"<?= $row ? '' : ' required' ?>>

                <?php else:
                    $htmlType = ['money' => 'number', 'number' => 'number', 'date' => 'date', 'email' => 'email', 'tel' => 'tel'][$f['type']] ?? 'text'; ?>
                    <input type="<?= $htmlType ?>" id="<?= $name ?>" name="<?= $name ?>" value="<?= e($value) ?>"<?= $req ?>
                        <?= $f['type'] === 'money' ? ' step="0.01" min="0"' : '' ?>
                        <?= $f['type'] === 'number' ? ' step="1" min="' . ($f['min'] ?? 0) . '"' : '' ?>
                        <?= isset($f['pattern']) ? ' pattern="' . e($f['pattern']) . '"' : '' ?>>
                <?php endif; ?>

                <?php if (!empty($f['hint']) && !$locked): ?><span class="hint"><?= e($f['hint']) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="field wide form-actions">
            <button class="btn btn-primary" type="submit"><?= $row ? 'Save changes' : 'Create ' . e($E['singular']) ?></button>
            <a class="btn btn-ghost" href="<?= url($self) ?>">Cancel</a>
        </div>
    </form>

<?php else:
    // ----- List with search and pagination -----
    $q       = trim($_GET['q'] ?? '');
    $where   = '';
    $params  = [];
    if ($q !== '' && !empty($E['search'])) {
        $where  = 'WHERE ' . implode(' OR ', array_map(fn($c) => "`$c` LIKE ?", $E['search']));
        $params = array_fill(0, count($E['search']), "%$q%");
    }
    $perPage = 15;
    $total   = (int)db_val("SELECT COUNT(*) FROM `$t` $where", $params);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = min($pages, max(1, (int)($_GET['p'] ?? 1)));
    $rows    = db_all("SELECT * FROM `$t` $where ORDER BY {$E['order']} LIMIT $perPage OFFSET " . ($page - 1) * $perPage, $params);
    ?>
    <div class="toolbar">
        <form method="get" class="toolbar-search">
            <input type="hidden" name="t" value="<?= e($t) ?>">
            <label class="skip-link" for="q">Search</label>
            <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($E['title'])) ?>…">
            <button class="btn btn-navy btn-sm" type="submit">Search</button>
        </form>
        <?php if ($canCreate): ?>
            <a class="btn btn-primary" href="<?= url($self . '&a=create') ?>">+ Add <?= e($E['singular']) ?></a>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php foreach ($E['list'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <?php foreach ($E['list'] as $col => $label): ?><td><?= $cell($col, $r) ?></td><?php endforeach; ?>
                        <td class="actions">
                            <a class="btn btn-ghost btn-sm" href="<?= url($self . '&a=edit&id=' . $r['id']) ?>"><?= $canCreate ? 'Edit' : 'Open' ?></a>
                            <form method="post" action="<?= url($self . '&a=delete&id=' . $r['id']) ?>" data-confirm="Delete this <?= e($E['singular']) ?>? This cannot be undone.">
                                <?= csrf_field() ?>
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="<?= count($E['list']) + 1 ?>" class="muted text-center">Nothing found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="muted"><?= $total ?> record<?= $total === 1 ? '' : 's' ?></p>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pages">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                <?php else: ?><a href="<?= url($self . '&p=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif;

include __DIR__ . '/_footer.php';
