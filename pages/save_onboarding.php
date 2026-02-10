<?php
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user']['id'];
    
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $hometown = $_POST['from']; 
    $bio = $_POST['bio'] ?? '';
    $hobbies = $_POST['selected_hobbies'];

    try {
        $stmt = $conn->prepare("UPDATE users SET age = ? WHERE id = ?");
        $stmt->execute([$age, $userId]);

        $check = $conn->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
        $check->execute([$userId]);
        
        if ($check->fetch()) {
            $sql = "UPDATE user_profiles SET gender=?, hometown=?, bio=?, hobbies=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$gender, $hometown, $bio, $hobbies, $userId]);
        } else {
            $sql = "INSERT INTO user_profiles (user_id, gender, hometown, bio, hobbies) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $gender, $hometown, $bio, $hobbies]);
        }

        header("Location: dashboard.php");
        exit();

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>