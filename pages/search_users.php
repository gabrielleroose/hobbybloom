<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id   = $_SESSION['user']['id'];
$query     = trim($_GET['q'] ?? '');
$mode      = $_GET['mode'] ?? 'search';
$circle_id = intval($_GET['circle_id'] ?? 0);

try {
    if ($mode === 'circle_members') {

        $circleStmt = $conn->prepare("SELECT name FROM circle WHERE circle_id = ?");
        $circleStmt->execute([$circle_id]);
        $circleName = $circleStmt->fetchColumn();

        if (!$circleName) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }


        $stmt = $conn->prepare("
            SELECT u.id, u.username
            FROM users u
            JOIN user_profiles p ON p.user_id = u.id
            WHERE u.id != ?
              AND (
                  p.hobbies = ?
                  OR p.hobbies LIKE ?
                  OR p.hobbies LIKE ?
                  OR p.hobbies LIKE ?
              )
            ORDER BY u.username ASC
        ");
        $stmt->execute([
            $user_id,
            $circleName,                   
            $circleName . ', %',             
            '%, ' . $circleName,             
            '%, ' . $circleName . ', %'      
        ]);

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }

    if ($mode === 'following') {

        $stmt = $conn->prepare("
            SELECT u.id, u.username
            FROM user_follows uf
            JOIN users u ON u.id = uf.followed_id
            WHERE uf.follower_id = ?
            ORDER BY u.username ASC
        ");
        $stmt->execute([$user_id]);
    } else {

        if (strlen($query) < 1) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT u.id, u.username,
                   IF(uf.followed_id IS NOT NULL, 1, 0) AS is_following
            FROM users u
            LEFT JOIN user_follows uf
                ON uf.follower_id = ? AND uf.followed_id = u.id
            WHERE u.id != ?
              AND u.username LIKE ?
            ORDER BY is_following DESC, u.username ASC
            LIMIT 20
        ");
        $like = '%' . $query . '%';
        $stmt->execute([$user_id, $user_id, $like]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'users' => $users]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Query failed']);
}