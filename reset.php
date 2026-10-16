<?php
include_once 'config.php';

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';

// Проверяем токен из GET-параметра
if ($token === '') {
    $errors[] = 'Недействительная ссылка.';
} else {
    // Ищем токен и проверяем срок
    $stmt = $pdo->prepare("SELECT user_id 
                            FROM password_reset_tokens
                            WHERE token = ? AND expires > NOW()");

    $stmt->execute([$token]);
    $user_id = $stmt->fetchColumn();

    if (!$user_id) {
        $errors[] = 'Ссылка недействительна или срок её действия истёк.';
    }
}

// Обработка формы нового пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    // Валидация
    if (strlen($pass) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Пароли не совпадают.';
    }

    if (empty($errors)) {
        // Хешируем и сохраняем новый пароль
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET pw = ? WHERE id = ?")
            ->execute([$hash, $user_id]);

        // Удаляем все токены этого пользователя
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?")
            ->execute([$user_id]);

        $success = true;
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
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <title>Сброс пароля</title>
</head>

<body>

    <header>
        <a href="main.php">
            <h1>Бюро Находок</h1>
        </a>
    </header>

    <main>

        <h2>Сброс пароля</h2>

        <?php if ($errors): ?>
            <ul style="color: red;">
                <?php foreach ($errors as $e)
                    echo '<li>' . htmlspecialchars($e) . '</li>';
                ?>
            </ul>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="password" name="password" placeholder="Новый пароль">
            <input type="password" name="password2" placeholder="Повторите пароль">

            <button type="submit">Сменить пароль</button>
        </form>

        <?php
        // Если успешный сброс — показываем итог
        if ($success): ?>

            <h3>Ваш пароль успешно изменён</h3>
            <p>
                Теперь вы можете
                <a href="login.php">
                    войти
                </a>
                с новым паролем.
            </p>

            <?php
            exit;
        endif;
        ?>

    </main>

</body>

</html>