<?php
/**
 * Database configuration.
 * These are the XAMPP defaults. Change them here if your MySQL user or password differs.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'liberia_tourism');
define('DB_USER', 'root');
define('DB_PASS', '');

// Web path of the project (e.g. "/liberia-tourism/"), worked out from the folder location
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$basePath    = ($docRoot !== '' && stripos($projectRoot, $docRoot) === 0)
    ? substr($projectRoot, strlen($docRoot))
    : '/' . basename($projectRoot);
define('BASE_URL', rtrim($basePath, '/') . '/');
define('ROOT_PATH', $projectRoot);

/**
 * Returns the single shared PDO connection.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('<h1>Database connection failed</h1><p>Start MySQL in the XAMPP Control Panel, '
               . 'then import <code>database.sql</code> in phpMyAdmin. See README.md for the steps.</p>');
        }
    }
    return $pdo;
}
