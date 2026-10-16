<?php
$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получаем поисковый запрос
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    include_once 'config.php';
    
    // Получаем все категории для боковой панели
    $sqlCategories = "SELECT id, item_category FROM categories ORDER BY item_category";
    $stmtCategories = $pdo->prepare($sqlCategories);
    $stmtCategories->execute();
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

    // Получаем найденные товары
    $items = [];
    if (!empty($searchTerm)) {
        // Сначала проверим структуру таблицы items
        $checkTable = $pdo->query("DESCRIBE items");
        $tableStructure = $checkTable->fetchAll(PDO::FETCH_COLUMN);
        
        // Определяем, по каким полям искать
        $searchFields = [];
        if (in_array('item', $tableStructure)) {
            $searchFields[] = 'i.item';
        }
        if (in_array('description', $tableStructure)) {
            $searchFields[] = 'i.description';
        }
        if (in_array('location', $tableStructure)) {
            $searchFields[] = 'i.location';
        }
        
        // Если нет полей для поиска, используем только item
        if (empty($searchFields)) {
            $searchFields = ['i.item'];
        }
        
        // Создаем SQL запрос
        $whereConditions = [];
        $params = [];
        $paramIndex = 1;
        
        foreach ($searchFields as $field) {
            $whereConditions[] = "$field LIKE ?";
            $params[] = '%' . $searchTerm . '%';
        }
        
        $sql = "SELECT i.* FROM items i WHERE " . implode(' OR ', $whereConditions) . " ORDER BY i.date_and_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Поиск - "<?= htmlspecialchars($searchTerm) ?>" | Бюро Находок</title>
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
                        <input class="search-input" type="text" name="search" placeholder="Поиск по сайту" value="<?= htmlspecialchars($searchTerm) ?>">
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
                    <i class="fas fa-search"></i> Результаты поиска
                </h2>
                <div class="info-content">
                    <?php if (empty($searchTerm)): ?>
                        <p>Введите поисковый запрос в поле выше, чтобы найти потерянные или найденные предметы.</p>
                        <p>Поиск осуществляется по названию предмета и его описанию.</p>
                    <?php else: ?>
                        <p>По запросу <strong>"<?= htmlspecialchars($searchTerm) ?>"</strong> найдено <strong><?= count($items) ?></strong> предметов.</p>
                        <p>Нажмите на любой предмет для просмотра подробной информации.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($searchTerm)): ?>
                <div class="items-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-lightbulb"></i> Советы по поиску
                        </h2>
                    </div>
                    
                    <div class="info-content">
                        <p>Для эффективного поиска:</p>
                        <ul>
                            <li>Вводите конкретные названия предметов (например, "черный кошелек", "ключи от квартиры")</li>
                            <li>Используйте ключевые слова из описания</li>
                            <li>Попробуйте разные варианты написания</li>
                            <li>Можно искать по месту находки/потери</li>
                        </ul>
                        <p>Или посмотрите <a href="catalog.php" class="view-all-link">весь каталог предметов</a>.</p>
                    </div>
                </div>
            <?php elseif (empty($items)): ?>
                <div class="items-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-exclamation-circle"></i> Ничего не найдено
                        </h2>
                    </div>
                    
                    <div class="info-content">
                        <p>По запросу <strong>"<?= htmlspecialchars($searchTerm) ?>"</strong> ничего не найдено.</p>
                        <p>Попробуйте:</p>
                        <ul>
                            <li>Использовать другие ключевые слова</li>
                            <li>Проверить правильность написания</li>
                            <li>Искать по категориям в <a href="catalog.php" class="view-all-link">каталоге</a></li>
                            <li>Посмотреть <a href="catalog.php?status=found" class="view-all-link">недавно найденные</a> или <a href="catalog.php?status=lost" class="view-all-link">потерянные</a> предметы</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="items-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-search"></i> Найденные предметы (<?= count($items) ?>)
                        </h2>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="catalog.php" class="view-all-link">
                                <i class="fas fa-arrow-right"></i> Смотреть все в каталоге
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="items-grid">
                        <?php foreach($items as $item): ?>
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
                                            <div class="item-status <?= $item['found'] ? 'found' : 'lost' ?>">
                                                <i class="fas <?= $item['found'] ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
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
                
                <?php 
                // Разделяем найденные и потерянные предметы для двух секций
                $foundItems = array_filter($items, function($item) {
                    return $item['found'] == 1;
                });
                
                $lostItems = array_filter($items, function($item) {
                    return $item['found'] == 0;
                });
                ?>
                
                <?php if (!empty($foundItems)): ?>
                <div class="items-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-check-circle"></i> Найдено (<?= count($foundItems) ?>)
                        </h2>
                        <a href="catalog.php?status=found" class="view-all-link">
                            <i class="fas fa-arrow-right"></i> Все найденные
                        </a>
                    </div>
                    
                    <div class="items-grid">
                        <?php foreach($foundItems as $item): ?>
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
                <?php endif; ?>
                
                <?php if (!empty($lostItems)): ?>
                <div class="items-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-exclamation-triangle"></i> Потеряно (<?= count($lostItems) ?>)
                        </h2>
                        <a href="catalog.php?status=lost" class="view-all-link">
                            <i class="fas fa-arrow-right"></i> Все потерянные
                        </a>
                    </div>
                    
                    <div class="items-grid">
                        <?php foreach($lostItems as $item): ?>
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
                <?php endif; ?>
            <?php endif; ?>
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