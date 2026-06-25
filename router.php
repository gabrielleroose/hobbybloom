<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files (css, js, images, icons) directly
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/', $uri)) {
    return false;
}

// Route PHP requests to pages/ folder
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/pages/index.php';
} elseif (file_exists(__DIR__ . '/pages' . $uri)) {
    require __DIR__ . '/pages' . $uri;
} else {
    http_response_code(404);
    echo "404 Not Found";
}
