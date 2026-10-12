<?php
/**
 * Sign out: clear the session completely.
 */
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];
session_destroy();
session_start();
flash('You have been signed out.', 'info');
redirect('index.php');
