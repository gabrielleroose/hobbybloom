<?php

$host     = getenv('MYSQLHOST');
$user     = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');
$port     = getenv('MYSQLPORT') ?: '3306';
$charset  = "utf8mb4";

$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$loader = new \Twig\Loader\FilesystemLoader(paths: __DIR__ . '/../templates');

$twig = new \Twig\Environment(loader: $loader, options: [
    'debug' => true,
]);
