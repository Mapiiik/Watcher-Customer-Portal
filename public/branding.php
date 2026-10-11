<?php
/**
 * Serves the assets of the portal's profile (logos, colors.css), which live outside the web root
 * in the profile directory the deployment mounts. Only plain file names of the known types.
 */
require dirname(__DIR__) . '/src/bootstrap.php';

$types = [
    'css' => 'text/css; charset=UTF-8',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
];
$file = isset($_GET['file']) && is_string($_GET['file']) ? $_GET['file'] : '';
$type = $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
$path = PROFILE_DIR . '/assets/' . $file;

if ($type === null || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file) || !is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $type);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");
readfile($path);
