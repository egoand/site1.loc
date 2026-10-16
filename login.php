<?php
include_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');  // email или телефон
    $password = $_POST['password'] ?? '';

    if ($login === '') {
        $errors[] = 'Введите email или телефон';
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id, username, pw
            FROM users
            WHERE (address = :l AND :l LIKE '%@%')
               OR (phone   = :l AND :l NOT LIKE '%@%')
            LIMIT 1
        ");

        $stmt->execute([':l' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['pw'])) {
            $errors[] = 'Неправильные учетные данные';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: main.php');
            exit;
        }
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
    <title>Вход | Бюро Находок</title>
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
        
        .login-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
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
            margin-bottom: 20px;
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
        
        .links {
            margin-top: 25px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .links a {
            color: #2C3E50;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s ease;
            position: relative;
            padding: 8px 0;
        }
        
        .links a:hover {
            color: #4A6491;
        }
        
        .links a.forgot::before {
            content: "?";
            display: inline-block;
            width: 20px;
            height: 20px;
            background-color: #f5f5f5;
            border-radius: 50%;
            text-align: center;
            margin-right: 8px;
            font-size: 14px;
            line-height: 20px;
        }
        
        .links a.register {
            font-weight: 500;
            font-size: 16px;
        }
        
        .links a.register::after {
            content: "";
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 1px;
            background-color: #2C3E50;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .links a.register:hover::after {
            transform: scaleX(1);
        }
        
        .no-account {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="main.php" class="back-button">На главную</a>
        
        <h2>Вход</h2>

        <?php if ($errors): ?>
            <ul class="error-list">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post">
            <input name="login" type="text" placeholder="Email или телефон" 
                   value="<?= htmlspecialchars($login ?? '') ?>" required>
            
            <input name="password" type="password" placeholder="Пароль" required>
            
            <button type="submit">Войти</button>
        </form>

        <div class="links">
            <a href="forgot.php" class="forgot">Забыли пароль?</a>
            
            <p class="no-account">Нет аккаунта?</p>
            <a href="register.php" class="register">Зарегистрироваться</a>
        </div>
    </div>
</body>
</html>