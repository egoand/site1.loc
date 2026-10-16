<?php
include_once 'config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Подключение к базе данных
$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем данные пользователя
    $stmt = $pdo->prepare("SELECT id, username, address, phone FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем объявления пользователя
    $stmt2 = $pdo->prepare("SELECT i.id, i.item, i.picture_name, i.found, i.date_and_time, c.item_category
                           FROM items i
                           JOIN categories c ON i.category = c.id
                           WHERE i.author = :user_id");
    $stmt2->execute([':user_id' => $user['id']]);
    $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Профиль пользователя | Бюро Находок</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
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
                <?php if (is_logged_in()): ?>
                    <a href="ad-creation.php" class="auth-link">
                        <div class="auth-button">
                            <i class="fas fa-plus-circle"></i> Разместить объявление
                        </div>
                    </a>
                <?php endif; ?>

                <div class="search-box">
                    <form action="search.php" method="GET" class="search-form">
                        <input class="search-input" type="text" name="search" placeholder="Поиск по сайту">
                        <button class="search-btn" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <div class="auth-buttons-container">
                    <?php if (is_logged_in()): ?>
                        <a href="profile.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars(current_username()) ?>
                            </div>
                        </a>
                        <a href="logout.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-sign-out-alt"></i> Выход
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-sign-in-alt"></i> Вход
                            </div>
                        </a>
                        <a href="register.php" class="auth-link">
                            <div class="auth-button">
                                <i class="fas fa-user-plus"></i> Регистрация
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="main-content">
        <main class="profile-container">
            <div class="profile-header">
                <h1 class="profile-title">
                    <i class="fas fa-user-circle"></i> Мой профиль
                </h1>
                <!-- <div class="profile-actions">
                    <a href="edit-profile.php" class="action-button">
                        <i class="fas fa-edit"></i> Редактировать профиль
                    </a>
                </div> -->
            </div>
            
            <div class="profile-grid">
                <!-- Информация о пользователе -->
                <div class="profile-info-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-id-card"></i> Личная информация
                        </h2>
                        <div class="card-icon">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i> Имя пользователя:
                            </div>
                            <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i> Email:
                            </div>
                            <div class="info-value"><?= htmlspecialchars($user['address']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-phone"></i> Телефон:
                            </div>
                            <div class="info-value"><?= htmlspecialchars($user['phone']) ?></div>
                        </div>
                    </div>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= count($items) ?></div>
                            <div class="stat-label">Всего объявлений</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?= count(array_filter($items, function($item) { return $item['found']; })) ?>
                            </div>
                            <div class="stat-label">Найдено</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?= count(array_filter($items, function($item) { return !$item['found']; })) ?>
                            </div>
                            <div class="stat-label">Потеряно</div>
                        </div>
                    </div>
                </div>
                
                <!-- Мои объявления -->
                <div class="ads-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-bullhorn"></i> Мои объявления
                        </h2>
                        <div class="card-actions">
                            <a href="ad-creation.php" class="action-button small">
                                <i class="fas fa-plus"></i> Создать новое
                            </a>
                        </div>
                    </div>
                    
                    <?php if (empty($items)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3>У вас еще нет объявлений</h3>
                            <p>Создайте первое объявление, чтобы найти или вернуть потерянные вещи</p>
                            <a href="ad-creation.php" class="action-button">
                                <i class="fas fa-plus-circle"></i> Создать объявление
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="ads-grid">
                            <?php foreach ($items as $item): ?>
                            <a href="rewrite.php?id=<?= $item['id'] ?>" class="ad-card-link">
                                <div class="ad-card">
                                    <div class="ad-image-container">
                                        <?php if (!empty($item['picture_name'])): ?>
                                            <?php
                                            $imagePath = '/sites/site.loc/item-images/' . $item['picture_name'];
                                            $absolutePath = 'C:/xampp/htdocs' . $imagePath;
                                            ?>
                                            <?php if (file_exists($absolutePath)): ?>
                                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                                     alt="<?= htmlspecialchars($item['item']) ?>" 
                                                     class="ad-image"
                                                     loading="lazy">
                                                <div class="ad-status <?= $item['found'] ? 'found' : 'lost' ?>">
                                                    <?= $item['found'] ? 'Найдено' : 'Потеряно' ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-image"></i>
                                                    <span>Нет изображения</span>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                                <span>Нет изображения</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="ad-content">
                                        <h3 class="ad-title"><?= htmlspecialchars($item['item']) ?></h3>
                                        
                                        <div class="ad-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <span><?= htmlspecialchars($item['date_and_time']) ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-tag"></i>
                                                <span><?= htmlspecialchars($item['item_category']) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="ad-actions">
                                            <span class="edit-link">
                                                <i class="fas fa-edit"></i> Редактировать
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($items) > 6): ?>
                        <div class="view-more">
                            <a href="my-ads.php" class="view-all-link">
                                <i class="fas fa-list"></i> Смотреть все объявления
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>