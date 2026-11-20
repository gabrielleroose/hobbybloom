<?php
// Shared setup for logged-in pages: session + database ($conn) via db.php,
// and the pending follow-request count shown in the nav ($reqCount).
// Prints nothing. The page layout comes from includes/header.php.
require_once __DIR__ . '/db.php';

$reqCount = 0;
if (isset($_SESSION['user']['id'])) {
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE followed_id = ? AND status = 'pending'");
    $stmtCount->execute([$_SESSION['user']['id']]);
    $reqCount = (int)$stmtCount->fetchColumn();
}
