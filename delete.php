<?php
session_start();
// 在删除文件前添加
require_once 'admin_logger.php';
$logger = new AdminLogger();
$logger->logAction($_SESSION['admin_username'] ?? $_SESSION['username'] ?? '匿名用户', "删除文件: {$tag}/{$file}");

// 检查管理员权限
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die('无权访问');
}

// 检查参数
if (!isset($_GET['file']) || !isset($_GET['tag'])) {
    die('参数错误');
}

$file = $_GET['file'];
$tag = $_GET['tag'];

// 验证标签是否合法
$allowedTags = json_decode(file_get_contents('tags.json'), true) ?: ['文档', '图片', '视频', '音乐', '其他'];
if (!in_array($tag, $allowedTags)) {
    die('无效标签');
}

// 构建文件路径
$uploadDir = 'uploads/' . $tag . '/';
$filePath = $uploadDir . $file;

// 检查文件是否存在
if (!file_exists($filePath)) {
    die('文件不存在');
}

// 删除文件
if (unlink($filePath)) {
    // 删除成功后重定向回原页面
    header("Location: wjgl.php?tag=" . urlencode($tag) . "&message=" . urlencode("文件删除成功") . "&messageType=success");
} else {
    header("Location: wjgl.php?tag=" . urlencode($tag) . "&message=" . urlencode("文件删除失败") . "&messageType=error");
}
exit;
?>