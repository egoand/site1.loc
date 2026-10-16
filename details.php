<?php
session_start();

$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: catalog.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report'])) {
    $itemId = (int)$_GET['id'];
    $complaintFile = 'C:/xampp/htdocs/sites/site.loc/complaints.txt';
    
    file_put_contents($complaintFile, $itemId . PHP_EOL, FILE_APPEND);
    
    $_SESSION['report_success'] = "Жалоба на объявление #$itemId отправлена";
    header("Location: details.php?id=$itemId");
    exit;
}

$itemId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT i.*, c.item_category as category_name 
        FROM items i
        LEFT JOIN categories c ON i.category = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception("Объект не найден");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: catalog.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($item['item']) ?> - Детали</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="details.css">
    <style>
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            animation: zoomIn 0.3s;
        }
        
        @keyframes zoomIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: #bbb;
        }
        
        .image-wrapper {
            position: relative;
            display: inline-block;
            overflow: hidden;
            cursor: zoom-in;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .image-wrapper img {
            max-width: 500px;
            max-height: 500px;
            display: block;
            transition: transform 0.3s ease;
        }
        
        .image-wrapper:hover img {
            transform: scale(1.02);
        }
        
        .magnifier {
            position: absolute;
            width: 200px;
            height: 200px;
            border: 3px solid #fff;
            border-radius: 50%;
            background-repeat: no-repeat;
            background-color: white;
            pointer-events: none;
            display: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            z-index: 10;
        }
        
        .magnifier.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="panel"></div>
    <div class="item-details">
        <h1><?= htmlspecialchars($item['item']) ?></h1>

        <?php if (!empty($item['picture_name'])): ?>
            <?php
            $imagePath = '/sites/site.loc/item-images/' . $item['picture_name'];
            $absolutePath = 'C:/xampp/htdocs' . $imagePath;
            ?>
            <div class="item-image">
                <?php if (file_exists($absolutePath)): ?>
                    <div class="image-wrapper" id="image-wrapper">
                        <img src="<?= htmlspecialchars($imagePath) ?>" 
                             alt="<?= htmlspecialchars($item['item']) ?>" 
                             id="main-image"
                             class="zoomable-image">
                        <div class="magnifier" id="magnifier"></div>
                    </div>
                <?php else: ?>
                    <p>Изображение не найдено</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="detail-row">
            <p class="detail-label">Категория:</p>
            <?= htmlspecialchars($item['category_name'] ?? 'Не указана') ?>
        </div>
        
        <div class="detail-row">
            <p class="detail-label">Дата публикации:</p>
            <?= htmlspecialchars(substr($item['date_and_time'], 0, 10)); ?>
        </div>
        
        <div class="detail-row">
            <p class="detail-label">Примерное место находки/пропажи:</p>
            <?= htmlspecialchars($item['location']) ?>
        </div>
        
        <div class="detail-row">
            <p class="detail-label">Комментарий:</p>
            <?= nl2br(htmlspecialchars($item['commentary'])) ?>
        </div>
        
        <div class="detail-row">
            <p class="detail-label">Контактные данные:</p>
            <?= htmlspecialchars($item['contacts']) ?>
        </div>
        <a href="catalog.php" class="back-link">← Вернуться к каталогу</a>
    </div>

    <!-- Модальное окно для увеличенного изображения -->
    <div class="image-modal" id="image-modal">
        <span class="close-modal" id="close-modal">&times;</span>
        <img src="<?= htmlspecialchars($imagePath) ?>" 
             alt="<?= htmlspecialchars($item['item']) ?>" 
             class="modal-image" 
             id="modal-image">
    </div>

    <div class="report-container">
        <form method="post" onsubmit="return confirm('Вы уверены, что хотите пожаловаться на это объявление?');">
            <input type="hidden" name="report" value="1">
            <button type="submit" class="report-btn">Пожаловаться на объявление</button>
        </form>
    </div>
    <div class="panel"></div>

    <?php if (isset($_SESSION['report_success'])): ?>
        <div class="report-success" id="report-success">
            <?= htmlspecialchars($_SESSION['report_success']) ?>
        </div>
        <?php unset($_SESSION['report_success']); ?>
        <script>
            document.getElementById('report-success').style.display = 'block';
            setTimeout(function() {
                document.getElementById('report-success').style.display = 'none';
            }, 3000);
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageWrapper = document.getElementById('image-wrapper');
            const mainImage = document.getElementById('main-image');
            const magnifier = document.getElementById('magnifier');
            const modal = document.getElementById('image-modal');
            const modalImage = document.getElementById('modal-image');
            const closeModal = document.getElementById('close-modal');
            
            if (!mainImage || !imageWrapper) return;
            
            let magnifierActive = false;
            const magnifierSize = 200;
            const zoomLevel = 2;
            
            // Функция для активации лупы
            function activateMagnifier(e) {
                if (!magnifierActive) return;
                
                const rect = imageWrapper.getBoundingClientRect();
                let x = e.clientX - rect.left;
                let y = e.clientY - rect.top;
                
                // Ограничиваем позицию лупы в пределах изображения
                x = Math.max(magnifierSize/2, Math.min(x, rect.width - magnifierSize/2));
                y = Math.max(magnifierSize/2, Math.min(y, rect.height - magnifierSize/2));
                
                // Позиционируем лупу
                magnifier.style.left = (x - magnifierSize/2) + 'px';
                magnifier.style.top = (y - magnifierSize/2) + 'px';
                
                // Вычисляем background-position для увеличения
                const bgX = (x / rect.width) * 100;
                const bgY = (y / rect.height) * 100;
                
                // Устанавливаем увеличенное изображение в лупу
                magnifier.style.backgroundImage = `url('${mainImage.src}')`;
                magnifier.style.backgroundSize = `${rect.width * zoomLevel}px ${rect.height * zoomLevel}px`;
                magnifier.style.backgroundPosition = `${bgX}% ${bgY}%`;
            }
            
            // Активация лупы при наведении
            imageWrapper.addEventListener('mouseenter', function() {
                magnifierActive = true;
                magnifier.classList.add('active');
            });
            
            // Деактивация лупы при уходе мыши
            imageWrapper.addEventListener('mouseleave', function() {
                magnifierActive = false;
                magnifier.classList.remove('active');
            });
            
            // Движение лупы
            imageWrapper.addEventListener('mousemove', activateMagnifier);
            
            // Клик для открытия модального окна
            imageWrapper.addEventListener('click', function() {
                modal.style.display = 'flex';
                modalImage.src = mainImage.src;
            });
            
            // Закрытие модального окна
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Закрытие по клику вне изображения
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Закрытие по ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>