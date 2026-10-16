<?php
session_start();
$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$item = [];
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        die("Объявление не найдено");
    }
    
    $stmt = $pdo->query("SELECT id, item_category FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete'])) {
            // Удаляем изображение если есть
            if (!empty($item['picture_name'])) {
                $oldImage = 'C:/xampp/htdocs/sites/site.loc/item-images/' . $item['picture_name'];
                if (file_exists($oldImage)) {
                    unlink($oldImage);
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$itemId]);
            
            header('Location: catalog.php');
            exit;
        }
        
        $back_time = date('Y-m-d H:i:s');
        $current_time = date('Y-m-d H:i:s', strtotime($back_time . ' +5 hours'));
        $itemData = [
            'item' => $_POST['item'] ?? '',
            'category' => (int)$_POST['category'] ?? 0,
            'found' => (int)$_POST['status'],
            'commentary' => $_POST['commentary'] ?? '',
            'contacts' => $_POST['contacts'] ?? '',
            'location' => $_POST['location'] ?? '',
            'date_and_time' => $current_time, // Обновляем дату и время
            'id' => $itemId
        ];
        
        if (!empty($_FILES['picture']['name'])) {
            $uploadDir = 'C:/xampp/htdocs/sites/site.loc/item-images/';
            $extension = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid() . '.' . $extension;
            $uploadFile = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $uploadFile)) {
                // Удаляем старое изображение если есть
                if (!empty($item['picture_name'])) {
                    $oldImage = $uploadDir . $item['picture_name'];
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                $itemData['picture_name'] = $newFilename;
            } else {
                throw new Exception("Ошибка загрузки изображения");
            }
        }
        
        $sql = "UPDATE items SET 
                item = :item, 
                category = :category, 
                found = :found, 
                commentary = :commentary, 
                contacts = :contacts, 
                location = :location,
                date_and_time = :date_and_time";
        
        if (isset($itemData['picture_name'])) {
            $sql .= ", picture_name = :picture_name";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($itemData);
        
        header("Location: details.php?id=$itemId");
        exit;
        
    } catch (Exception $e) {
        die("Ошибка: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование объявления</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="rewrite.css">
</head>
<body>
    <div class="panel"></div>
    <div class="container">
        <h1>Редактирование объявления #<?= $itemId ?></h1>
        <p class="last-updated">Последнее обновление: <?= $item['date_and_time'] ?></p>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="item">Название:</label>
                <input type="text" id="item" name="item" value="<?= htmlspecialchars($item['item']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Категория:</label>
                <select id="category" name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $category['id'] == $item['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['item_category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Статус:</label>
                <div class="status-group">
                    <div class="status-option">
                        <input type="radio" id="found" name="status" value="1" <?= $item['found'] ? 'checked' : '' ?>>
                        <label for="found">Найдено</label>
                    </div>
                    <div class="status-option">
                        <input type="radio" id="lost" name="status" value="0" <?= !$item['found'] ? 'checked' : '' ?>>
                        <label for="lost">Потеряно</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="commentary">Комментарий:</label>
                <textarea id="commentary" name="commentary"><?= htmlspecialchars($item['commentary']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="contacts">Контакты:</label>
                <input type="text" id="contacts" name="contacts" value="<?= htmlspecialchars($item['contacts']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="location">Местоположение:</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($item['location']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Текущее изображение:</label>
                <?php if (!empty($item['picture_name'])): ?>
                    <img src="/sites/site.loc/item-images/<?= htmlspecialchars($item['picture_name']) ?>" 
                         class="current-image" 
                         alt="Текущее изображение">
                <?php else: ?>
                    <p>Нет изображения</p>
                <?php endif; ?>
                
                <label for="picture">Новое изображение:</label>
                <input type="file" id="picture" name="picture" accept="image/*">
            </div>
            
            <div class="buttons">
                <button type="submit">Сохранить изменения</button>
                <a href="details.php?id=<?= $itemId ?>" style="text-decoration: none; position: relative; top: 8px; color: #2C3E50;">Назад</a>
                <button type="submit" name="delete" class="delete-btn" 
                        onclick="return confirm('Удалить это объявление?')">Удалить</button>
            </div>
        </form> 
    </div>
        <div class="panel"></div>
</body>
</html>