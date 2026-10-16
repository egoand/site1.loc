<?php
$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получение категорий
$sql = "SELECT id, item_category FROM categories";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение недавно найденных предметов
$foundItems = $pdo->query("
    SELECT i.* FROM items i 
    WHERE i.found = 1 
    ORDER BY i.date_and_time DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// Получение недавно потерянных предметов
$lostItems = $pdo->query("
    SELECT i.* FROM items i 
    WHERE i.found = 0 
    ORDER BY i.date_and_time DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

include_once 'config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Главная | Бюро Находок</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="main.css">
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
        <div class="sidebar-container">
            <div class="sidebar" id="sidebar">
                <button class="categories-toggle" id="categoriesToggle">
                    <i class="fas fa-list"></i> 
                    <span>Категории</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                
                <div class="categories-dropdown" id="categoriesDropdown">
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
                </div>
            </div>
        </div>
        
        <main class="main-container">
            <div class="info-box">
                <h2 class="info-title">
                    <i class="fas fa-gavel"></i> Правовая информация
                </h2>
                <div class="info-content">
                    <p>Согласно Статье 229 Гражданского Кодекса Российской Федерации:</p>
                    <p>1. Нашедший и возвративший вещь лицу, управомоченному на ее получение, вправе получить от этого лица, а в случаях перехода вещи в муниципальную собственность - от соответствующего органа местного самоуправления возмещение необходимых расходов, связанных с хранением, сдачей или реализацией вещи, а также затрат на обнаружение лица, управомоченного получить вещь.</p>
                    <p>2. Нашедший вещь вправе потребовать от лица, управомоченного на получение вещи, вознаграждение за находку в размере до двадцати процентов стоимости вещи. Если найденная вещь представляет ценность только для лица, управомоченного на ее получение, размер вознаграждения определяется по соглашению с этим лицом.</p>
                    <p>Право на вознаграждение не возникает, если нашедший вещь не заявил о находке или пытался ее утаить.</p>
                </div>
            </div>
            
            <div class="items-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-search"></i> Недавно найдено
                    </h2>
                    <a href="catalog.php?status=found" class="view-all-link">
                        <i class="fas fa-arrow-right"></i> Смотреть все
                    </a>
                </div>
                
                <div class="items-grid">
                    <?php foreach ($foundItems as $item): ?>
                    <a href="details.php?id=<?= $item['id'] ?>" class="item-card">
                        <div class="item-image-container">
                            <?php if (!empty($item['picture_name'])): ?>
                                <?php
                                $imagePath = '/sites/site.loc/item-images/' . $item['picture_name'];
                                $absolutePath = 'C:/xampp/htdocs' . $imagePath;
                                ?>
                                <?php if (file_exists($absolutePath)): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                                         alt="<?= htmlspecialchars($item['item']) ?>" 
                                         class="item-image"
                                         loading="lazy">
                                    <div class="item-status found">
                                        <i class="fas fa-check-circle"></i> Найдено
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
                        
                        <div class="item-content">
                            <h3 class="item-title"><?= htmlspecialchars($item['item']) ?></h3>
                            <div class="item-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= htmlspecialchars($item['date_and_time']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($item['location']) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="items-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-exclamation-triangle"></i> Недавно потеряно
                    </h2>
                    <a href="catalog.php?status=lost" class="view-all-link">
                        <i class="fas fa-arrow-right"></i> Смотреть все
                    </a>
                </div>
                
                <div class="items-grid">
                    <?php foreach ($lostItems as $item): ?>
                    <a href="details.php?id=<?= $item['id'] ?>" class="item-card">
                        <div class="item-image-container">
                            <?php if (!empty($item['picture_name'])): ?>
                                <?php
                                $imagePath = '/sites/site.loc/item-images/' . $item['picture_name'];
                                $absolutePath = 'C:/xampp/htdocs' . $imagePath;
                                ?>
                                <?php if (file_exists($absolutePath)): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                                         alt="<?= htmlspecialchars($item['item']) ?>" 
                                         class="item-image"
                                         loading="lazy">
                                    <div class="item-status lost">
                                        <i class="fas fa-exclamation-circle"></i> Потеряно
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
                        
                        <div class="item-content">
                            <h3 class="item-title"><?= htmlspecialchars($item['item']) ?></h3>
                            <div class="item-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= htmlspecialchars($item['date_and_time']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($item['location']) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoriesToggle = document.getElementById('categoriesToggle');
            const categoriesDropdown = document.getElementById('categoriesDropdown');
            const sidebar = document.getElementById('sidebar');
            const toggleIcon = categoriesToggle.querySelector('.toggle-icon');
            
            let isOpen = true;
            let scrollTimeout = null;
            let lastScrollTop = 0;
            const scrollThreshold = 100;
            const autoCloseDelay = 300;

            // Функция для открытия меню
            function openCategories() {
                isOpen = true;
                categoriesDropdown.classList.remove('collapsed');
                sidebar.classList.remove('collapsed');
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
                localStorage.setItem('categoriesState', 'open');
            }

            // Функция для закрытия меню
            function closeCategories() {
                isOpen = false;
                categoriesDropdown.classList.add('collapsed');
                sidebar.classList.add('collapsed');
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
                localStorage.setItem('categoriesState', 'closed');
            }

            // Функция для переключения видимости
            function toggleCategories() {
                if (isOpen) {
                    closeCategories();
                } else {
                    openCategories();
                }
            }

            // Функция для авто-закрытия при прокрутке
            function handleScroll() {
                const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Закрываем меню при прокрутке вниз
                if (currentScrollTop > lastScrollTop && 
                    currentScrollTop > scrollThreshold && 
                    isOpen) {
                    
                    if (scrollTimeout) {
                        clearTimeout(scrollTimeout);
                    }
                    
                    scrollTimeout = setTimeout(() => {
                        if (isOpen && currentScrollTop > scrollThreshold) {
                            closeCategories();
                        }
                    }, autoCloseDelay);
                }
                
                lastScrollTop = currentScrollTop;
            }

            // Восстанавливаем состояние из localStorage
            const savedState = localStorage.getItem('categoriesState');
            if (savedState === 'closed') {
                isOpen = false;
                categoriesDropdown.classList.add('collapsed');
                sidebar.classList.add('collapsed');
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            }

            // Обработчик клика по кнопке
            categoriesToggle.addEventListener('click', toggleCategories);

            // Обработчик скролла с дебаунсингом
            let scrollDebounceTimer;
            window.addEventListener('scroll', function() {
                if (scrollDebounceTimer) {
                    clearTimeout(scrollDebounceTimer);
                }
                scrollDebounceTimer = setTimeout(() => {
                    handleScroll();
                }, 50);
            });

            // Закрытие при клике вне области (только на мобильных)
            if (window.innerWidth <= 992) {
                document.addEventListener('click', function(event) {
                    if (!categoriesToggle.contains(event.target) && 
                        !categoriesDropdown.contains(event.target)) {
                        if (isOpen) {
                            closeCategories();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>