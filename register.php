<?php
include_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    // Базовая валидация
    if ($username === '') {
        $errors[] = 'Введите имя пользователя';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат email';
    }
    if ($phone !== '' && !preg_match('/^\+7\d{10}$/', $phone)) {
        $errors[] = 'Телефон должен быть в формате +7##########';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Пароль минимум 6 символов';
    }
    if ($password !== $pass2) {
        $errors[] = 'Пароли не совпадают';
    }
    if ($email === '' && $phone === '') {
        $errors[] = 'Укажите email или телефон';
    }
    if (!isset($_POST['privacy_policy'])) {
        $errors[] = 'Необходимо принять Политику конфиденциальности';
    }

    // Проверка уникальности
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT username, address AS email, phone
            FROM users
            WHERE username = :u
               OR (address = :e AND :e <> '')
               OR (phone   = :p AND :p <> '')
            LIMIT 1
        ");

        $stmt->execute([
            ':u' => $username,
            ':e' => $email,
            ':p' => $phone,
        ]);

        $exist = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exist) {
            if ($exist['username'] === $username) {
                $errors[] = 'Имя пользователя «' . htmlspecialchars($username) . '» уже занято';
            }
            if ($email !== '' && $exist['email'] === $email) {
                $errors[] = 'Email «' . htmlspecialchars($email) . '» уже используется';
            }
            if ($phone !== '' && $exist['phone'] === $phone) {
                $errors[] = 'Телефон «' . htmlspecialchars($phone) . '» уже используется';
            }
        }
    }

    // Сохранение в БД
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, address, phone, pw)
            VALUES (:u, :e, :p, :h)
        ");

        $stmt->execute([
            ':u' => $username,
            ':e' => $email ?: null,
            ':p' => $phone ?: null,
            ':h' => $hash,
        ]);

        // Авто‑логин
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        header('Location: main.php');
        exit;
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
    <title>Регистрация | Бюро Находок</title>
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
        
        .register-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
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
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 28px;
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
            gap: 18px;
        }
        
        input {
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #2C3E50;
        }
        
        input::placeholder {
            color: #999;
        }
        
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 10px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
        }
        
        .checkbox-container label {
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        
        .privacy-link {
            color: #2C3E50;
            text-decoration: underline;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .privacy-link:hover {
            color: #4A6491;
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
            margin-top: 10px;
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
        }
        
        .login-link {
            margin-top: 25px;
            text-align: center;
            color: #666;
            font-size: 15px;
        }
        
        .login-link a {
            color: #2C3E50;
            text-decoration: none;
            font-weight: 500;
            margin-left: 8px;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: #4A6491;
            background-color: #f5f5f5;
        }
        
        .field-note {
            font-size: 12px;
            color: #666;
            margin-top: -10px;
            margin-bottom: 10px;
            padding: 0 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="main.php" class="back-button">На главную</a>
        
        <h2>Регистрация</h2>

        <?php if ($errors): ?>
            <ul class="error-list">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post">
            <input name="username" placeholder="Имя пользователя *" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            
            <input name="email" type="email" placeholder="Email *" 
                   value="<?= htmlspecialchars($email ?? '') ?>">
            
            <div class="field-note">* Email или телефон обязателен</div>
            
            <input name="phone" type="text" placeholder="Телефон (формат: +7XXXXXXXXXX)" 
                   value="<?= htmlspecialchars($phone ?? '') ?>">
            
            <input name="password" type="password" placeholder="Пароль *" required>
            
            <input name="password2" type="password" placeholder="Повторите пароль *" required>
            
            <div class="checkbox-container">
                <input type="checkbox" name="privacy_policy" id="privacy_policy" required>
                <label for="privacy_policy">
                    Я принимаю 
                    <a href="privacy_policy.php" target="_blank" class="privacy-link">
                        Политику конфиденциальности
                    </a>
                    *
                </label>
            </div>
            
            <button type="submit">Зарегистрироваться</button>
        </form>

        <div class="login-link">
            Уже есть аккаунт?
            <a href="login.php">Войти</a>
        </div>
    </div>
</body>
</html>