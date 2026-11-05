<?php
// Shared Google settings. Secrets come from environment variables only.

// The Sign-In client ID is public (it's visible in the login page HTML),
// so a fallback here is safe. Override with GOOGLE_SIGNIN_CLIENT_ID if needed.
if (!defined('GOOGLE_SIGNIN_CLIENT_ID')) {
    define(
        'GOOGLE_SIGNIN_CLIENT_ID',
        getenv('GOOGLE_SIGNIN_CLIENT_ID') ?: '268931984946-slb4joliqufslsotd5iet5qir3jnrp9g.apps.googleusercontent.com'
    );
}

// Calendar OAuth credentials (secret lives in Railway env vars).
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
}

/**
 * Base URL of the running site, e.g. https://hobbybloom-production.up.railway.app
 * Uses APP_URL if set; otherwise derives it from the request.
 * Railway sits behind a proxy, so X-Forwarded-Proto tells us the real scheme.
 */
function app_base_url(): string {
    $configured = getenv('APP_URL');
    if ($configured) {
        return rtrim($configured, '/');
    }
    $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO']
        ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', app_base_url() . '/Google_calendar_callback.php');
}
