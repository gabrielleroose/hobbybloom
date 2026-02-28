<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$action = $_POST['action'] ?? '';
$hobby = $_POST['hobby'] ?? 'General';

if ($action === 'toggle_circle') {
    $stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $hobbiesStr = $stmt->fetchColumn();
    $hobbiesArr = $hobbiesStr ? array_map('trim', explode(',', $hobbiesStr)) : [];
    
    if (in_array($hobby, $hobbiesArr)) {
        $hobbiesArr = array_diff($hobbiesArr, [$hobby]);
    } else {
        $hobbiesArr[] = $hobby;
    }
    
    $newHobbies = implode(', ', $hobbiesArr);
    $conn->prepare("UPDATE user_profiles SET hobbies = ? WHERE user_id = ?")->execute([$newHobbies, $userId]);
}

if ($action === 'toggle_follow') {
    $targetId = $_POST['target_id'] ?? 0;
    
    if ($targetId > 0 && $targetId != $userId) {
        $stmt = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$userId, $targetId]);
        
        if ($stmt->fetch()) {
            $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?")->execute([$userId, $targetId]);
        } else {
            $conn->prepare("INSERT INTO user_follows (follower_id, followed_id) VALUES (?, ?)")->execute([$userId, $targetId]);
        }
    }
}

if ($hobby === 'activity_redirect') {
    header("Location: activity.php");
} else {
    header("Location: circle_detail.php?hobby=" . urlencode($hobby));
}
exit();
?>