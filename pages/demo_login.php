<?php
// "Explore the demo": sign the visitor in as the shared, read-only demo account.
require_once 'db.php';

$stmt = $conn->prepare("SELECT id, email, username FROM users WHERE google_id = ?");
$stmt->execute([DEMO_GOOGLE_ID]);
$demo = $stmt->fetch();

if (!$demo) {
    // Demo data hasn't been loaded into this database yet.
    header('Location: /?demo=unavailable');
    exit;
}

// Start a fresh session so nothing from a previous login carries over.
$_SESSION = [];
session_regenerate_id(true);

$_SESSION['user'] = [
    'id'    => $demo['id'],
    'email' => $demo['email'],
    'name'  => $demo['username'],
];
$_SESSION['google_id'] = DEMO_GOOGLE_ID;

header('Location: dashboard.php');
exit;
