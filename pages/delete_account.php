<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user']['id'];

    try {
        $conn->beginTransaction();

        $stmt1 = $conn->prepare("DELETE FROM user_profiles WHERE user_id = ?");
        $stmt1->execute([$userId]);

        $stmt2 = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? OR followed_id = ?");
        $stmt2->execute([$userId, $userId]);

        $stmt3 = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt3->execute([$userId]);

        $conn->commit();

        session_unset();
        session_destroy();

        header("Location: login.php?deleted=success");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Error deleting account: " . $e->getMessage());
    }
} else {
    header("Location: account.php");
    exit();
}