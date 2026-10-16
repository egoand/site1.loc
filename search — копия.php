<?php
$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';
    

if (!empty($_GET['search']))
    include_once 'config.php';

    $conn = new mysqli($host, $username, $password, $dbname);

    $foundStatus = isset($_GET['found']) ? (int)$_GET['found'] : 1;

    $searchst = mysqli_real_escape_string($conn, $_GET['search']);
    $searchst = filter_var($searchst, FILTER_SANITIZE_STRING);

    $query = "SELECT * FROM items WHERE 1 = 1";

    // это на потом
    // $searchst = replace_words($searchst);
    $words = explode(" ", $searchst);
    foreach ($words as $m) {
        $query .= " AND (item LIKE '%".$m."%' OR commentary LIKE '%".$m."%')";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Результаты поиска - <?= $searchst ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <header>
        <a href="main.php">
            <div class="header-main"><h1>Бюро Находок<h1/></div>
        </a>
        <div class="top-bar-all">
            <?php if (is_logged_in()): ?>
                <a href="ad-creation.php" class="header-link">
                    <div class="top-bar">Разместить объявление</div>
                </a>
            <?php endif; ?>

            <div class="search-box">
                <form action="search.php" method = "GET">
                    <input class="search-textbox" type="text" name="search" placeholder="Поиск по сайту">
                    <button class="search-btn"><span style="color: white;">→</span></button>
                </form>
            </div>

            <div class="auth-buttons-container">
                <?php if (is_logged_in()): ?>
                    <a href="profile.php" class="autorisation-link">
                        <div class="authorisation-button">
                            Мой профиль (<?= htmlspecialchars(current_username()) ?>)
                        </div>
                    </a>
                    <a href="logout.php" class="autorisation-link">
                        <div class="authorisation-button">Выход</div>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="autorisation-link">
                        <div class="authorisation-button">Вход</div>
                    </a>
                    <a href="register.php" class="autorisation-link">
                        <div class="authorisation-button">Регистрация</div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="content">
        <div class="content-box">
            <?php
            $sql = "SELECT id, item_category FROM categories ORDER BY item_category";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<ul class="catalog-list">';

            echo '<li><a href="catalog.php" class="catalog-link">
                    <div class="category-button" style="font-size: 18px;">Все категории</div></a></li>';
            
            foreach($categories as $row) {
                echo '<li><a href="catalog.php?category=' . $row['id'] . '" class="catalog-link">
                        <div class="category-button">' . htmlspecialchars($row["item_category"]) . '</div></a></li>';
            }
            echo "</ul>";
            ?>
        </div>
    
        <div class="main-content" style="font-family: 'Open Sans', sans-serif;">

            <!-- <div class="status-switcher">
                <p>Показать:</p>
                <a href="?category=<?= $selectedCategory ?>&found=1" 
                   class="status-btn <?= $foundStatus == 1 ? 'active' : '' ?>">
                    Найденные
                </a>
                <a href="?category=<?= $selectedCategory ?>&found=0" 
                   class="status-btn <?= $foundStatus == 0 ? 'active' : '' ?>">
                    Потерянные
                </a>
            </div> -->
            
            <?php if (empty($items)): ?>
                <p>Товары не найдены.</p>
            <?php else: ?>
                <div class="items-list">
                    <?php foreach($items as $item): ?>
                        <a href="details.php?id=<?= $item['id'] ?>" class="item-link">
                            <div class="item">
                                <?php if (!empty($item['picture_name'])): ?>
                                    <?php
                                    $imagePath = '/sites/site.loc/item-images/' . $item['picture_name'];
                                    $absolutePath = 'C:/xampp/htdocs' . $imagePath;
                                    ?>
                                    <div class="item-image">
                                        <?php if (file_exists($absolutePath)): ?>
                                            <img src="<?= htmlspecialchars($imagePath) ?>" 
                                                 alt="<?= htmlspecialchars($item['item']) ?>" 
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="no-image">Нет изображения</div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-image">Нет изображения</div>
                                <?php endif; ?>
                                
                                <div class="item-info">
                                    <h2><?= htmlspecialchars($item['item']) ?></h2>
                                    <p class="date"><?= htmlspecialchars($item['date_and_time']) ?></p>
                                    <p class="location"><?= htmlspecialchars($item['location']) ?></p>
                                    <p class="status"><?= $item['found'] ? 'Найдено' : 'Потеряно' ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
