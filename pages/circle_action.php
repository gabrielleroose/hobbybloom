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
$statusMessage = "updated";

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
    $actionType = $_POST['action_type'] ?? '';

    if ($targetId > 0 && $targetId != $userId) {
        
        if ($actionType === 'remove_follower') {
            $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?")
                 ->execute([$targetId, $userId]);
            $statusMessage = "removed";
        } else {
            $stmt = $conn->prepare("SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$userId, $targetId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?")
                     ->execute([$userId, $targetId]);
                $statusMessage = "unfollowed";
            } else {
                $stmt = $conn->prepare("SELECT is_private FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$targetId]);
                $isPrivate = $stmt->fetchColumn();

                $newStatus = ($isPrivate == 1) ? 'pending' : 'accepted';
                $statusMessage = ($newStatus === 'pending') ? 'requested' : 'followed';
                
                $conn->prepare("INSERT INTO user_follows (follower_id, followed_id, status) VALUES (?, ?, ?)")
                     ->execute([$userId, $targetId, $newStatus]);
            }
        }
    }
}

$isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => $statusMessage,
        'action' => $action
    ]);
    exit();
}

if ($hobby === 'activity_redirect') {
    header("Location: activity.php");
} elseif ($hobby === 'account_redirect') {
    header("Location: account.php?success=" . $statusMessage);
} else {
    header("Location: circle_detail.php?hobby=" . urlencode($hobby));
}
exit();
?>