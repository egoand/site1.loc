<?php
session_start();

$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT id, item_category FROM categories";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
    $error = "Ошибка загрузки категорий: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Политика конфиденциальности | Бюро Находок</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="privacy_policy.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo-container">
                <a href="main.php" class="logo">
                    <i class="fas fa-search"></i> Бюро Находок
                </a>
            </div>
            
            <div class="top-bar-all">
                <div class="search-box">
                    <form action="search.php" method="GET" class="search-form">
                        <input class="search-input" type="text" name="search" placeholder="Поиск по сайту">
                        <button class="search-btn" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <?php 
                // Проверка авторизации через сессию
                $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']);
                $username = $_SESSION['username'] ?? 'Пользователь';
                ?>
                
                <?php if ($isLoggedIn): ?>
                    <a href="ad-creation.php" class="auth-link">
                        <div class="auth-button">
                            <i class="fas fa-plus-circle"></i>
                            Разместить объявление
                        </div>
                    </a>
                <?php endif; ?>
                
                <div class="auth-buttons-container">
                    <?php if ($isLoggedIn): ?>
                        <a href="profile.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars($username) ?>
                            </div>
                        </a>
                        <a href="logout.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-sign-out-alt"></i>
                                Выход
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-sign-in-alt"></i>
                                Вход
                            </div>
                        </a>
                        <a href="register.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-user-plus"></i>
                                Регистрация
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <div class="main-content">
        <aside class="sidebar">
            <h2 class="sidebar-title">
                <i class="fas fa-list"></i> Категории
            </h2>
            
            <ul class="category-list">
                <li class="category-item">
                    <a href="catalog.php" class="category-link all-categories">
                        <i class="fas fa-th-large"></i> Все категории
                    </a>
                </li>
                
                <?php foreach($categories as $row): ?>
                <li class="category-item">
                    <a href="catalog.php?category=<?= $row['id'] ?>" class="category-link">
                        <i class="fas fa-tag"></i> <?= htmlspecialchars($row["item_category"]) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        
        <div class="privacy-content">
            <h1>Политика конфиденциальности</h1>
            
            <div class="policy-text">
                <?php
                $txt_file = 'Пользовательское соглашение.txt';
                if (file_exists($txt_file)) {
                    $content = file_get_contents($txt_file);
                    echo nl2br(htmlspecialchars($content));
                } else {
                    echo '<p style="color: #666; text-align: center; font-style: italic;">
                          Файл политики конфиденциальности не найден.</p>';
                }
                ?>
            </div>
            
            <div class="last-updated">
                Последнее обновление: <?= date('d.m.Y') ?>
            </div>
        </div>
    </div>

</body>
</html>