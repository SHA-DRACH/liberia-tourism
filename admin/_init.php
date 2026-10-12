<?php
/**
 * Loaded first by every admin page: helpers plus the access check.
 * Only admin and staff accounts may enter the admin panel.
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('admin', 'staff');

$entities = require __DIR__ . '/entities.php';

/** True when the signed-in user may open the given admin section. */
function can_manage(array $entity): bool
{
    return has_role(...($entity['roles'] ?? ['admin', 'staff']));
}
