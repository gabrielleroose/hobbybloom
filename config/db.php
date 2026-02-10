<?php


$host = "db.luddy.indiana.edu";
$user = "i494f25_team18";
$password = "berms2227penes";
$database = "i494f25_team18";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


// use of PDO's for security, mysqli extensions largely outdated.


try {
    $pdo = new PDO(dsn: $dsn, username: $user, password: $password, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    } catch (PDOException $e) {
        die("database connection failed: " . $e->getMessage());
    }

$loader = new \Twig\Loader\FilesystemLoader(paths: __DIR__ . '/../templates');

$twig = new \Twig\Environment(loader: $loader, options: [
    'debug' => true,
]);

