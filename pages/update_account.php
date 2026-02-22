<?php
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user']['id'];
    
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $hometown = $_POST['from']; 
    $bio = $_POST['bio'];
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

        header("Location: account.php?success=1");
        exit();

    } catch (PDOException $e) {
        die("Error updating profile: " . $e->getMessage());
    }
}
?>