<?php

function compressImageSmart($source, $destination) {
    return smartCompressJPEG($source, $destination);
}

function smartCompressJPEG($source, $destination) {
    if (!file_exists($source)) {
        return false;
    }
    
    $originalSize = filesize($source);
    
    if ($originalSize < 200 * 1024) {
        return copy($source, $destination);
    }
    
    $image = @imagecreatefromjpeg($source);
    if (!$image) {
        return copy($source, $destination);
    }
    
    $quality = 80; // По умолчанию
    if ($originalSize > 1 * 1024 * 1024) { // >1MB
        $quality = 70;
    }
    if ($originalSize > 2 * 1024 * 1024) { // >2MB
        $quality = 65;
    }
    if ($originalSize > 5 * 1024 * 1024) { // >5MB
        $quality = 60;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    if ($width <= 0 || $height <= 0) {
        imagedestroy($image);
        return copy($source, $destination);
    }
    
    $maxDimension = 1920;
    $needsResize = false;
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width > $maxDimension || $height > $maxDimension) {
        $needsResize = true;
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = floor($height * ($maxDimension / $width));
        } else {
            $newHeight = $maxDimension;
            $newWidth = floor($width * ($maxDimension / $height));
        }
        
        if ($newWidth <= 0) $newWidth = 1;
        if ($newHeight <= 0) $newHeight = 1;
    }
    
    if ($needsResize) {
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$newImage) {
            imagedestroy($image);
            return copy($source, $destination);
        }
        
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, 
            $newWidth, $newHeight, $width, $height);
        
        imagejpeg($newImage, $destination, $quality);
        imagedestroy($newImage);
    } else {
        imagejpeg($image, $destination, $quality);
    }
    
    imagedestroy($image);
    
    if (!file_exists($destination)) {
        return false;
    }
    
    $newSize = filesize($destination);
    
    if ($newSize >= $originalSize) {
        unlink($destination);
        return copy($source, $destination);
    }
    
    return true;
}

function isJpeg($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg']);
    }
    
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return in_array($mime, ['image/jpeg', 'image/jpg']);
}

function isPdf($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $ext == 'pdf';
    }
    
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return $mime == 'application/pdf';
}

function compressImage($source, $destination, $maxWidth = 1920, $quality = 75) {
    return compressImageSmart($source, $destination);
}

function needsCompression($filePath, $threshold = 500 * 1024) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    return filesize($filePath) > $threshold;
}
?>