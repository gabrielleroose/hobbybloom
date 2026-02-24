<?php
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $userId = $_SESSION['user']['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        session_unset();
        session_destroy();

        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        die("Error deleting account: " . $e->getMessage());
    }
} else {
    header("Location: account.php");
    exit();
}
?>