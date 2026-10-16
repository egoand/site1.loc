<?php
include_once 'config.php';

$info = 'Введите свой email — мы вышлем ссылку для сброса пароля.';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Проверка формата email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат email';
    }

    if (empty($errors)) {
        // Ищем пользователя по адресу
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE address = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Генерируем токен и сохраняем
            $token = bin2hex(random_bytes(32));               // 64 символа
            $expiry = date('Y-m-d H:i:s', time() + 36000);      // 1 час
            $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires)
                VALUES (?, ?, ?)")
                ->execute([$user['id'], $token, $expiry]);

            // Формируем ссылку и отправляем письмо
            $resetLink = "https://localhost/sites/site.loc/reset.php?token=$token";
            $subject = 'Сброс пароля на сайте «Бюро Находок»';
            $message = "Здравствуйте, {$user['username']}!\n\n"
                . "Чтобы сбросить пароль, перейдите по ссылке:\n"
                . "$resetLink\n\n"
                . "Ссылка действительна в течение 1 часа.\n\n"
                . "Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.";
            $headers = "From: trusovn06@gmail.com\r\n"
                . "Content-Type: text/plain; charset=utf-8";

            mail($email, $subject, $message, $headers);
        }

        $info = 'Если этот email зарегистрирован, вы получите письмо со ссылкой.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap">
    <title>Восстановление пароля | Бюро Находок</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Oswald', sans-serif;
            background: linear-gradient(135deg, #2C3E50 0%, #4A6491 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .forgot-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px 30px;
            position: relative;
        }
        
        .back-button {
            position: absolute;
            top: 15px;
            left: 15px;
            background: none;
            border: none;
            color: #2C3E50;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .back-button:hover {
            background-color: #f5f5f5;
            transform: translateX(-3px);
        }
        
        .back-button::before {
            content: "←";
            font-size: 16px;
        }
        
        h2 {
            color: #2C3E50;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 28px;
        }
        
        .info-message {
            background-color: #e8f4fd;
            border: 1px solid #b6dfff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            color: #2C3E50;
            font-size: 15px;
            line-height: 1.5;
            text-align: center;
        }
        
        .error-list {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            list-style: none;
        }
        
        .error-list li {
            color: #c62828;
            font-size: 14px;
            padding: 5px 0;
        }
        
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        input[type="email"] {
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #2C3E50;
        }
        
        input[type="email"]::placeholder {
            color: #999;
        }
        
        button[type="submit"] {
            background: linear-gradient(to right, #2C3E50, #4A6491);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 16px;
            font-family: inherit;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
        }
        
        .links {
            margin-top: 25px;
            text-align: center;
        }
        
        .links a {
            color: #2C3E50;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s ease;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .links a:hover {
            color: #4A6491;
            background-color: #f5f5f5;
        }
        
        .links a::before {
            content: "←";
            margin-right: 8px;
            font-weight: bold;
        }
        
        .success-message {
            background-color: #e8f6e8;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            color: #2e7d32;
            font-size: 15px;
            line-height: 1.5;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <a href="login.php" class="back-button">Назад</a>
        
        <h2>Восстановление пароля</h2>

        <?php if ($errors): ?>
            <ul class="error-list">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errors): ?>
            <div class="info-message">
                <?= htmlspecialchars($info) ?>
            </div>
            
            <form method="post" autocomplete="off">
                <input type="email" name="email" placeholder="Email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                
                <button type="submit">Отправить ссылку</button>
            </form>
        <?php else: ?>
            <div class="success-message">
                <?= htmlspecialchars($info) ?>
            </div>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">Вернуться к входу</a>
        </div>
    </div>
</body>
</html>