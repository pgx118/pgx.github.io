<?php
session_start();
require_once 'admin_logger.php';

// 检查参数
if (!isset($_GET['file']) || !isset($_GET['tag'])) {
    die(json_encode(['success' => false, 'message' => '参数错误']));
}

$file = basename($_GET['file']);
$tag = $_GET['tag'];

// 验证标签是否在允许的列表中
$allowedTags = json_decode(file_get_contents('tags.json'), true) ?? ['文档', '图片', '视频', '音乐', '其他'];
if (!in_array($tag, $allowedTags)) {
    die(json_encode(['success' => false, 'message' => '非法标签']));
}

$filePath = 'uploads/' . $tag . '/' . $file;

// 验证文件存在且可读
if (!file_exists($filePath) || !is_readable($filePath)) {
    die(json_encode(['success' => false, 'message' => '文件不存在或不可读']));
}

// 验证文件名合法性
if (preg_match('/\.\.|\/|\\\/', $file)) {
    die(json_encode(['success' => false, 'message' => '文件名不合法']));
}

// 记录下载日志
$logger = new AdminLogger();
$username = isset($_SESSION['admin']) && $_SESSION['admin'] === true ? '管理员' : 
           (isset($_SESSION['username']) ? $_SESSION['username'] : '访客');
$logger->logAction($username, "下载文件: {$tag}/{$file} (大小: " . formatFileSize(filesize($filePath)) . ")");

// 关闭输出缓冲
if (ob_get_level()) {
    ob_end_clean();
}

// 设置下载头信息
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// 禁用脚本执行时间限制
set_time_limit(0);

// 清除PHP的输出缓冲区
while (ob_get_level()) {
    ob_end_clean();
}

// 使用分块读取方式输出文件
$chunkSize = 10 * 1024 * 1024; // 10MB chunks
$handle = fopen($filePath, 'rb');
if ($handle === false) {
    die(json_encode(['success' => false, 'message' => '无法打开文件']));
}

while (!feof($handle)) {
    $buffer = fread($handle, $chunkSize);
    echo $buffer;
    flush(); // 刷新输出缓冲
    
    // 检查连接是否仍然有效
    if (connection_status() != 0) {
        fclose($handle);
        die('下载中断');
    }
}

fclose($handle);
exit;

// 格式化文件大小函数
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>