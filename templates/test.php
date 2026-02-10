<?php

require_once '../vendor/autoload'

$host = "db.luddy.indiana.edu";
$user = "i494f25_team18";
$password = "berms2227penes";
$database = "i494f25_team18";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

// use of PDO's for security, mysqli extensions largely outdated.

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    } catch (PDOException $e) {
        die("database connection failed: " . $e->getMessage());
    }

$sql = "SELECT id, username, email FROM users";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$users = $stmt->fetchALL(PDO::FETCH_ASSOC);


foreach($users as $user) {
    echo "ID: " . $user['id'] . "|";
    echo "username: " . $user['username'] . "|";
    echo "email: " . $user['email'] . "<br>";
    
}

?>