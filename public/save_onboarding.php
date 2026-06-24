<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user']['id'];
    
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $username  = trim($_POST['username']);
    $age       = $_POST['age'];
    
    $hometown  = $_POST['from']; 
    $bio       = $_POST['bio'] ?? '';
    $hobbies   = $_POST['selected_hobbies'];

    try {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, age = ?, username = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $age, $username, $userId]);
        
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['first_name'] = $firstName;

        $check = $conn->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
        $check->execute([$userId]);
        
        if ($check->fetch()) {
            $sql = "UPDATE user_profiles SET hometown=?, bio=?, hobbies=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$hometown, $bio, $hobbies, $userId]);
        } else {
            $sql = "INSERT INTO user_profiles (user_id, hometown, bio, hobbies) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $hometown, $bio, $hobbies]);
        }

        header("Location: dashboard.php?success=onboarding");
        exit();

    } catch (PDOException $e) {
        die("Error saving your profile: " . $e->getMessage());
    }
}
?>