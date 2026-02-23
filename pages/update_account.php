<?php
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user']['id'];
    
    $username = trim($_POST['username']);
    $age = $_POST['age'];
    $hometown = $_POST['from']; 
    $bio = $_POST['bio'];
    $hobbies = $_POST['selected_hobbies'] ?? '';
    $profileColor = $_POST['profile_color'] ?? '#1f5077';

    try {
        $stmt = $conn->prepare("UPDATE users SET username = ?, age = ? WHERE id = ?");
        $stmt->execute([$username, $age, $userId]);
        
        $_SESSION['user']['name'] = $username;

        $check = $conn->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
        $check->execute([$userId]);
        
        if ($check->fetch()) {
            $sql = "UPDATE user_profiles SET hometown=?, bio=?, hobbies=?, profile_color=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$hometown, $bio, $hobbies, $profileColor, $userId]);
        } else {
            $sql = "INSERT INTO user_profiles (user_id, hometown, bio, hobbies, profile_color) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $hometown, $bio, $hobbies, $profileColor]);
        }

        header("Location: account.php?success=1");
        exit();

    } catch (PDOException $e) {
        die("Error updating profile: " . $e->getMessage());
    }
}
?>