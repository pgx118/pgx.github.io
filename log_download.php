<?php
session_start();
require_once 'admin_logger.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die('未授权访问');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = $_POST['filename'] ?? '';
    $folder = $_POST['folder'] ?? '';
    
    if (!empty($filename) && !empty($folder)) {
        $logger = new AdminLogger();
        $currentAdmin = $_SESSION['admin_username'];
        $logger->logAction($currentAdmin, "下载文件: {$folder}/{$filename}");
        echo 'OK';
    } else {
        echo '参数错误';
    }
} else {
    echo '无效请求';
}
?>