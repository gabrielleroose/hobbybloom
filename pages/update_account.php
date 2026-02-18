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


        $sql = "INSERT INTO user_profiles (user_id, gender, hometown, bio, hobbies) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                gender = VALUES(gender), 
                hometown = VALUES(hometown), 
                bio = VALUES(bio), 
                hobbies = VALUES(hobbies)";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId, $gender, $hometown, $bio, $hobbies]);


        header("Location: account.php?success=1");
        exit();

    } catch (PDOException $e) {
        die("Error updating profile: " . $e->getMessage());
    }
}
?>