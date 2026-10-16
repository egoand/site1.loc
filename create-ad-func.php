<?php
// create-ad-func.php
session_start();

if (isset($_POST['ad-create-btn'])) {
    $filename = $_POST['picture_name'] ?? null;
    
    $host = '127.0.0.1';
    $dbname = 'database';
    $username = 'root';
    $password = '';
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    $sql = "SELECT MIN(t1.id) + 1 AS next_id FROM items t1 LEFT JOIN items t2 ON t1.id + 1 = t2.id WHERE t2.id IS NULL LIMIT 1";
    $result = $conn->query($sql);
    $next_id = $result && ($row = $result->fetch_assoc()) ? $row['next_id'] : 1;
    
    $item_st = mysqli_real_escape_string($conn, $_POST['name-text'] ?? '');
    $category = $_POST['category'] ?? 0;
    $author = $_SESSION['user_id'];
    $picture_name_st = $filename ? mysqli_real_escape_string($conn, $filename) : null;
    $status = $_POST['status'] ?? '';
    $found = ($status === 'Нашел') ? 1 : 0;
    $commentary_st = mysqli_real_escape_string($conn, $_POST['description-text'] ?? '');
    $contacts_st = mysqli_real_escape_string($conn, $_POST['contacts'] ?? '');
    $location_st = mysqli_real_escape_string($conn, $_POST['location'] ?? '');
    $back_time = date('Y-m-d H:i:s');
    $current_time = date('Y-m-d H:i:s', strtotime($back_time . ' +5 hours'));
    
    $sql = "INSERT INTO items (id, item, category, author, picture_name, found, commentary, contacts, location, date_and_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssissss", 
        $next_id,
        $item_st,
        $category,
        $author,
        $picture_name_st,
        $found,
        $commentary_st,
        $contacts_st,
        $location_st,
        $current_time
    );
    
    if ($stmt->execute()) {
        // Очищаем все сессионные данные о загрузке
        unset($_SESSION['just_uploaded']);
        unset($_SESSION['original_filename']);
        header('Location: main.php');
        exit;
    } else {
        die("Ошибка при создании объявления: " . $stmt->error);
    }
}