<?php
// Buffer all output so pages that print HTML before starting the session
// don't trigger "headers already sent" warnings.
ob_start();

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Serve static assets (css, js, images, icons) directly from the app root.
// The built-in server resolves these itself and won't leave the docroot.
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf)$/i', $uri)) {
    return false;
}

$pagesDir = realpath(__DIR__ . '/pages');

if ($uri === '/' || $uri === '') {
    require $pagesDir . '/index.php';
    return true;
}

// Only run .php files that really live inside pages/.
// realpath() resolves any "../" so requests can't reach files elsewhere.
$target = realpath($pagesDir . $uri);
if (
    $target !== false
    && str_starts_with($target, $pagesDir . DIRECTORY_SEPARATOR)
    && is_file($target)
    && strtolower(pathinfo($target, PATHINFO_EXTENSION)) === 'php'
) {
    require $target;
    return true;
}

http_response_code(404);
echo "404 Not Found";
return true;
