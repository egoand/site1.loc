<?php
session_start();

$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// проверка, залогинен ли пользователь
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

// получить имя пользователя
function current_username(): ?string
{
    return $_SESSION['username'] ?? null;
}