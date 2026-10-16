<?php
session_start();

$uploadDir = 'C:\xampp\htdocs\sites\site.loc\item-images';
$filename = basename($_FILES['userfile']['name']); 
$uploadfile = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
    $_SESSION['just_uploaded'] = $filename;
} else {
    $_SESSION['upload_error'] = 'Ошибка загрузки файла';
}

header('Location: ad-creation.php');
exit;
?>