<?php
$host = 'localhost';
$dbname = 'event_booking_db';
$username = 'root';
$password = 'neng0917'; // Laragon default មិនមាន password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}