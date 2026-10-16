<?php
include_once 'config.php';
include_once 'compress-image.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userfile'])) {
    $uploadDir = 'C:\xampp\htdocs\sites\site.loc\item-images';
    
    $originalName = basename($_FILES['userfile']['name']);
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $tempFile = $_FILES['userfile']['tmp_name'];
    $fileSize = $_FILES['userfile']['size'];
    
    if (!isJpeg($tempFile) && !isPdf($tempFile)) {
        $_SESSION['upload_error'] = 'Допустимы только JPG и PDF файлы';
        header('Location: ad-creation.php');
        exit;
    }
    
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($fileSize > $maxFileSize) {
        $_SESSION['upload_error'] = 'Файл слишком большой. Максимальный размер: 10MB';
        header('Location: ad-creation.php');
        exit;
    }
    
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    $uniqueFilename = $timestamp . '_' . $randomString . '.' . $fileExtension;
    $uploadfile = $uploadDir . DIRECTORY_SEPARATOR . $uniqueFilename;
    
    if (isJpeg($tempFile)) {
        $compressThreshold = 300 * 1024; // 300KB
        $originalFileSize = filesize($tempFile);
        
        if ($originalFileSize > $compressThreshold) {
            if (compressImageSmart($tempFile, $uploadfile)) {
                $newSize = filesize($uploadfile);
                $savedPercent = round((1 - $newSize / $originalFileSize) * 100, 1);
                $_SESSION['compression_info'] = "Изображение сжато на {$savedPercent}%";
                
                $_SESSION['just_uploaded'] = $uniqueFilename;
                $_SESSION['original_filename'] = $originalName;
            } else {
                if (move_uploaded_file($tempFile, $uploadfile)) {
                    $_SESSION['just_uploaded'] = $uniqueFilename;
                    $_SESSION['original_filename'] = $originalName;
                } else {
                    $_SESSION['upload_error'] = 'Ошибка обработки изображения';
                }
            }
        } else {
            if (move_uploaded_file($tempFile, $uploadfile)) {
                $_SESSION['just_uploaded'] = $uniqueFilename;
                $_SESSION['original_filename'] = $originalName;
            } else {
                $_SESSION['upload_error'] = 'Ошибка загрузки файла';
            }
        }
    } elseif (isPdf($tempFile)) {
        if (move_uploaded_file($tempFile, $uploadfile)) {
            $_SESSION['just_uploaded'] = $uniqueFilename;
            $_SESSION['original_filename'] = $originalName;
        } else {
            $_SESSION['upload_error'] = 'Ошибка загрузки PDF файла';
        }
    }
    
    header('Location: ad-creation.php');
    exit;
}

$displayFilename = $_SESSION['original_filename'] ?? '';
$error = $_SESSION['upload_error'] ?? '';
$compressionInfo = $_SESSION['compression_info'] ?? '';
$uploadedFile = $_SESSION['just_uploaded'] ?? '';
unset($_SESSION['upload_error'], $_SESSION['compression_info']);

$host = '127.0.0.1';
$dbname = 'database';
$username = 'root';
$password = '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT id, item_category FROM categories";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once 'config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Создание объявления | Бюро Находок</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="ad-creation.css">
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
        
        <main class="ad-creation-form">
            <h1 class="form-title">
                <i class="fas fa-plus-circle"></i> Создание нового объявления
            </h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($displayFilename) && empty($error)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    Файл "<?= htmlspecialchars($displayFilename) ?>" успешно загружен
                    <?php if (!empty($compressionInfo)): ?>
                        <span class="compression-info">(<?= htmlspecialchars($compressionInfo) ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="upload-form">
                <div class="upload-section" id="drop-area">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    
                    <h3 class="upload-title">Загрузите фото или документ</h3>
                    
                    <p class="upload-subtitle">Перетащите файл сюда или нажмите кнопку ниже</p>
                    
                    <input type="file" name="userfile" accept=".jpg,.jpeg,.pdf" class="file-input" id="file-input">
                    <label for="file-input" class="upload-btn">
                        <i class="fas fa-folder-open"></i> Выбрать файл
                    </label>
                    
                    <div class="upload-requirements">
                        <p><strong>Требования к файлу:</strong></p>
                        <ul>
                            <li>Допустимые форматы: JPG, JPEG, PDF</li>
                            <li>Максимальный размер: 10 MB</li>
                            <li>Максимум 1 файл</li>
                        </ul>
                    </div>
                    
                    <img id="image-preview" class="image-preview" src="" alt="Предпросмотр">
                    
                    <?php if (!empty($displayFilename) && !empty($uploadedFile)): ?>
                    <div class="file-preview">
                        <div class="file-info">
                            <div class="file-icon">
                                <?php if (pathinfo($displayFilename, PATHINFO_EXTENSION) === 'pdf'): ?>
                                    <i class="fas fa-file-pdf"></i>
                                <?php else: ?>
                                    <i class="fas fa-file-image"></i>
                                <?php endif; ?>
                            </div>
                            <div class="file-details">
                                <h4><?= htmlspecialchars($displayFilename) ?></h4>
                                <p>Файл готов к прикреплению к объявлению</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
            
            <form action="create-ad-func.php" method="POST" id="ad-form">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="item-name" class="form-label">
                            <i class="fas fa-heading"></i> Название
                        </label>
                        <input type="text" id="item-name" name="name-text" class="form-input" 
                               required maxlength="100" placeholder="Что вы нашли или потеряли?">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="item-description" class="form-label">
                            <i class="fas fa-align-left"></i> Описание
                        </label>
                        <textarea id="item-description" name="description-text" class="form-textarea" 
                                  required maxlength="1000" placeholder="Подробно опишите предмет..."></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Статус
                        </label>
                        <div class="status-group">
                            <div class="status-option">
                                <input type="radio" id="status-found" name="status" value="Нашел" class="status-radio" required checked>
                                <label for="status-found" class="status-label">
                                    <i class="fas fa-search"></i> Нашел
                                </label>
                            </div>
                            <div class="status-option">
                                <input type="radio" id="status-lost" name="status" value="Потерял" class="status-radio">
                                <label for="status-lost" class="status-label">
                                    <i class="fas fa-exclamation-triangle"></i> Потерял
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="item-category" class="form-label">
                            <i class="fas fa-tag"></i> Категория
                        </label>
                        <select id="item-category" name="category" class="form-select" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>">
                                    <?= htmlspecialchars($category['item_category']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="item-contacts" class="form-label">
                            <i class="fas fa-phone-alt"></i> Контакты
                        </label>
                        <textarea id="item-contacts" name="contacts" class="form-textarea small" 
                                  required maxlength="50" placeholder="Как с вами связаться?"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="item-location" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Место
                        </label>
                        <textarea id="item-location" name="location" class="form-textarea small" 
                                  required maxlength="100" placeholder="Где это произошло?"></textarea>
                    </div>
                </div>
                
                <input type="hidden" name="picture_name" value="<?= htmlspecialchars($uploadedFile) ?>">
                
                <div class="submit-section">
                    <button type="submit" name="ad-create-btn" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Опубликовать объявление
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('file-input');
        const imagePreview = document.getElementById('image-preview');
        const uploadForm = document.getElementById('upload-form');
        
        // Предотвращаем стандартное поведение браузера
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Подсветка области при перетаскивании
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('drag-over');
        }
        
        function unhighlight() {
            dropArea.classList.remove('drag-over');
        }
        
        // Обработка сброса файла
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                const file = files[0];
                const fileName = file.name.toLowerCase();
                const validExtensions = ['.jpg', '.jpeg', '.pdf'];
                const isValid = validExtensions.some(ext => fileName.endsWith(ext));
                
                if (!isValid) {
                    alert('Допустимы только JPG и PDF файлы');
                    return;
                }
                
                if (file.type.startsWith('image/')) {
                    // Показываем превью для изображений
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                }
                
                fileInput.files = files;
                uploadForm.submit();
            }
        }
        
        // Обработка выбора файла через кнопку
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                }
                
                uploadForm.submit();
            }
        });
        
        // Валидация формы объявления перед отправкой
        document.getElementById('ad-form').addEventListener('submit', function(e) {
            const pictureName = document.querySelector('input[name="picture_name"]').value;
            
            if (!pictureName) {
                e.preventDefault();
                alert('Пожалуйста, загрузите фото или документ перед созданием объявления');
                dropArea.scrollIntoView({ behavior: 'smooth' });
                dropArea.style.borderColor = '#e74c3c';
                dropArea.style.backgroundColor = '#fee';
                
                setTimeout(() => {
                    dropArea.style.borderColor = '';
                    dropArea.style.backgroundColor = '';
                }, 2000);
            }
        });
    </script>
</body>
</html>