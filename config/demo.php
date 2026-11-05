<?php
// Demo mode: a shared, read-only account recruiters and visitors can explore
// without signing in with Google. Loaded from pages/db.php on every request.

const DEMO_GOOGLE_ID = 'hobbybloom-demo';

function is_demo_user(): bool {
    return ($_SESSION['google_id'] ?? null) === DEMO_GOOGLE_ID;
}

/**
 * Stop demo visitors from changing anything.
 * - Every POST is blocked, except the pages listed in $allowedPosts.
 * - Pages that connect outside accounts or change the account itself are
 *   blocked even on GET (e.g. linking a visitor's Google Calendar to the
 *   shared demo account).
 */
function enforce_demo_read_only(): void {
    if (!is_demo_user()) {
        return;
    }

    $page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

    $alwaysBlocked = [
        'Google_calendar_connect.php',
        'Google_calendar_callback.php',
        'Google_calendar_sync.php',
        'delete_account.php',
        'update_account.php',
        'save_onboarding.php',
    ];
    // POSTs that are safe for the demo (they only affect the demo's own quiz progress)
    $allowedPosts = [
        'check_answer.php',
    ];

    $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $blocked = in_array($page, $alwaysBlocked, true)
        || ($isPost && !in_array($page, $allowedPosts, true));

    if (!$blocked) {
        return;
    }

    $message = "Demo mode: changes are turned off. Sign in with Google to make your own account.";

    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || ($isPost && empty($_POST)); // fetch() calls with a raw body

    if ($wantsJson) {
        header('Content-Type: application/json');
        // Pages in this app read either `success` or `status`, so send both.
        echo json_encode([
            'success' => false,
            'status'  => 'error',
            'error'   => $message,
            'message' => $message,
        ]);
        exit;
    }

    // Normal form submit or link: go back where they came from with a notice.
    // Only follow same-site referers so this can't become an open redirect.
    $back = 'dashboard.php';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refHost = parse_url($referer, PHP_URL_HOST);
    if ($referer && $refHost === parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
        $path  = parse_url($referer, PHP_URL_PATH) ?: '/dashboard.php';
        $query = parse_url($referer, PHP_URL_QUERY);
        $back  = $path . ($query ? '?' . $query : '');
    }
    $back .= (str_contains($back, '?') ? '&' : '?') . 'demo_blocked=1';
    header('Location: ' . $back);
    exit;
}
