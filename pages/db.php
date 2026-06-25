<?php
require_once __DIR__ . '/../vendor/autoload.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = getenv('MYSQLHOST');
$user     = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');
$port     = getenv('MYSQLPORT') ?: '3306';
$charset  = "utf8mb4";

$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function extractEmoji($string) {
    preg_match('/^(\X)/u', $string, $matches);
    return $matches[1] ?? '';
}
