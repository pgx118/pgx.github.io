<?php
session_start();
require_once 'admin_logger.php';
require_once 'config.php';
// 检查管理员登录状态
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}
// 获取磁盘使用情况统计
function getDiskUsageStats() {
    $totalSpace = disk_total_space(__DIR__);
    $freeSpace = disk_free_space(__DIR__);
    $usedSpace = $totalSpace - $freeSpace;
    
    return [
        'total' => $totalSpace,
        'used' => $usedSpace,
        'free' => $freeSpace,
        'used_percent' => round(($usedSpace / $totalSpace) * 100, 2),
        'free_percent' => round(($freeSpace / $totalSpace) * 100, 2)
    ];
}

// 获取文件统计信息
function getFileStats($allowedTags) {
    $totalFiles = 0;
    $totalSize = 0;
    
    foreach ($allowedTags as $tag) {
        $dir = 'uploads/' . $tag . '/';
        if (file_exists($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            $totalFiles += count($files);
            
            foreach ($files as $file) {
                $totalSize += filesize($dir . $file);
            }
        }
    }
    
    return [
        'folders' => count($allowedTags),
        'files' => $totalFiles,
        'total_size' => $totalSize
    ];
}
$logger = new AdminLogger();
$currentAdmin = $_SESSION['admin_username'];

// 记录访问日志
$logger->logAction($currentAdmin, '访问管理员控制面板');

// 定义初始允许的标签（目录）
$defaultTags = ['文档', '图片', '视频', '音乐', '其他'];

// 从文件读取或初始化标签
$tagsFile = 'tags.json';
if (file_exists($tagsFile)) {
    $allowedTags = json_decode(file_get_contents($tagsFile), true);
    if (!is_array($allowedTags)) {
        $allowedTags = $defaultTags;
    }
} else {
    $allowedTags = $defaultTags;
    file_put_contents($tagsFile, json_encode($allowedTags));
}

// 背景风格配置
$stylesFile = 'styles.json';
$defaultStyles = [
    'primary-color' => '#6a5acd',
    'secondary-color' => '#9370db',
    'dark-color' => '#483d8b',
    'bg-light' => 'rgba(242, 242, 245, 0.99)'
];

if (file_exists($stylesFile)) {
    $styles = json_decode(file_get_contents($stylesFile), true);
    if (!is_array($styles)) {
        $styles = $defaultStyles;
    }
} else {
    $styles = $defaultStyles;
    file_put_contents($stylesFile, json_encode($styles));
}

// 获取当前活动标签
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'logs';

// 处理不同标签的功能
switch ($activeTab) {
    case 'folders':
        // 处理文件夹管理操作
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'add_tag' && !empty($_POST['new_tag'])) {
                $newTag = trim($_POST['new_tag']);
                if (!in_array($newTag, $allowedTags)) {
                    $allowedTags[] = $newTag;
                    file_put_contents($tagsFile, json_encode($allowedTags));
                    $message = "标签 '{$newTag}' 添加成功";
                    $messageType = 'success';
                    $logger->logAction($currentAdmin, "添加新标签: {$newTag}");
                } else {
                    $message = "标签 '{$newTag}' 已存在";
                    $messageType = 'error';
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_tag' && !empty($_POST['tag_to_delete'])) {
                $tagToDelete = $_POST['tag_to_delete'];
                if (($key = array_search($tagToDelete, $allowedTags)) !== false) {
                    // 检查是否有文件在该标签目录中
                    $tagDir = 'uploads/' . $tagToDelete . '/';
                    if (file_exists($tagDir) && count(array_diff(scandir($tagDir), ['.', '..'])) > 0) {
                        $message = "无法删除标签 '{$tagToDelete}'，因为该标签下还有文件";
                        $messageType = 'error';
                    } else {
                        unset($allowedTags[$key]);
                        $allowedTags = array_values($allowedTags); // 重新索引数组
                        file_put_contents($tagsFile, json_encode($allowedTags));
                        $message = "标签 '{$tagToDelete}' 删除成功";
                        $messageType = 'success';
                        $logger->logAction($currentAdmin, "删除标签: {$tagToDelete}");
                        
                        // 如果目录为空，删除目录
                        if (file_exists($tagDir) && count(array_diff(scandir($tagDir), ['.', '..'])) === 0) {
                            rmdir($tagDir);
                        }
                    }
                } else {
                    $message = "标签 '{$tagToDelete}' 不存在";
                    $messageType = 'error';
                }
            } elseif (isset($_POST['delete_file']) && isset($_POST['file_name']) && isset($_POST['folder'])) {
                $fileName = $_POST['file_name'];
                $folder = $_POST['folder'];
                $filePath = 'uploads/' . $folder . '/' . $fileName;
                
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $message = "文件 '{$fileName}' 删除成功";
                        $messageType = 'success';
                        // 记录删除操作到日志
                        $logger->logAction($currentAdmin, "删除文件: {$folder}/{$fileName}");
                    } else {
                        $message = "无法删除文件 '{$fileName}'";
                        $messageType = 'error';
                    }
                } else {
                    $message = "文件 '{$fileName}' 不存在";
                    $messageType = 'error';
                }
                
                // 重定向以避免表单重复提交
                $redirectParams = [
                    'tab' => 'folders',
                    'folder' => $folder,
                    'message' => $message,
                    'messageType' => $messageType
                ];
                
                header('Location: admin.php?' . http_build_query($redirectParams));
                exit;
            }
            
            // 重定向以避免表单重复提交
            $redirectParams = [
                'tab' => 'folders',
                'folder' => isset($_POST['folder']) ? $_POST['folder'] : (isset($_GET['folder']) ? $_GET['folder'] : $allowedTags[0])
            ];
            
            if (isset($message)) {
                $redirectParams['message'] = $message;
                $redirectParams['messageType'] = $messageType;
            }
            
            header('Location: admin.php?' . http_build_query($redirectParams));
            exit;
        }
        break;
        
    case 'appearance':
        // 处理背景风格设置
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'update_style') {
                // 更新背景风格
                $newStyles = [
                    'primary-color' => $_POST['primary_color'],
                    'secondary-color' => $_POST['secondary_color'],
                    'dark-color' => $_POST['dark_color'],
                    'bg-light' => $_POST['bg_light']
                ];
                file_put_contents($stylesFile, json_encode($newStyles));
                $styles = $newStyles;
                $message = "背景风格更新成功";
                $messageType = 'success';
                $logger->logAction($currentAdmin, "更新背景风格设置");
            } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_style') {
                // 重置背景风格
                file_put_contents($stylesFile, json_encode($defaultStyles));
                $styles = $defaultStyles;
                $message = "背景风格已重置";
                $messageType = 'success';
                $logger->logAction($currentAdmin, "重置背景风格设置");
            }
            
            // 重定向以避免表单重复提交
            header("Location: admin.php?tab=appearance&message=" . urlencode($message) . "&messageType=" . urlencode($messageType));
            exit;
        }
        break;
        
    case 'logs':
    default:
        // 日志管理功能
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $perPage = 20;
        $logsData = $logger->getLogs($page, $perPage, $search);
        $logs = $logsData['logs'];
        $totalPages = $logsData['pages'];
        
        // 处理删除日志
        if (isset($_POST['delete_log']) && isset($_POST['log_id'])) {
            $logId = (int)$_POST['log_id'];
            $stmt = $logger->getConn()->prepare("DELETE FROM admin_logs WHERE id = ?");
            $stmt->bind_param("i", $logId);
            $stmt->execute();
            $stmt->close();
            $logger->logAction($currentAdmin, "删除日志记录 ID: $logId");
            header("Location: admin.php?tab=logs&page=$page&search=" . urlencode($search));
            exit;
        }
        
        // 处理清空日志
        if (isset($_POST['clear_logs'])) {
            $stmt = $logger->getConn()->prepare("TRUNCATE TABLE admin_logs");
            $stmt->execute();
            $stmt->close();
            $logger->logAction($currentAdmin, "清空所有日志记录");
            header("Location: admin.php?tab=logs");
            exit;
        }
        break;
}

// 显示消息（如果有）
$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['messageType']) ? $_GET['messageType'] : '';

// 获取当前选中的文件夹（用于文件管理）
$selectedFolder = isset($_GET['folder']) ? $_GET['folder'] : $allowedTags[0];
$folderPath = 'uploads/' . $selectedFolder . '/';
$files = [];
if (file_exists($folderPath)) {
    $files = array_diff(scandir($folderPath), ['.', '..']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员控制面板 - 桂工网盘</title>
         <link rel="stylesheet" href="index2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   
<style>
    :root {
    --primary-color: <?php echo $styles['primary-color']; ?>;
    --secondary-color: <?php echo $styles['secondary-color']; ?>;
    --dark-color: <?php echo $styles['dark-color']; ?>;
    --light-color: #f8f8ff;
    --text-color: #333;
    --text-light: #f8f8ff;
    --success-color: #4CAF50;
    --error-color: #f44336;
    --transition-speed: 0.3s;
    --bg-light: <?php echo $styles['bg-light']; ?>;
    --card-bg: rgba(255, 255, 255, 0.95);
    --sidebar-width: 250px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: var(--bg-light);
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
}

/* Admin Layout */
.admin-sidebar {
    width: var(--sidebar-width);
    background-color: var(--dark-color);
    color: white;
    height: 100vh;
    position: fixed;
    overflow-y: auto;
}

.admin-logo {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-logo h2 {
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-menu {
    list-style: none;
}

.admin-menu li a {
    display: block;
    padding: 15px 20px;
    color: white;
    text-decoration: none;
    transition: all var(--transition-speed);
    border-left: 4px solid transparent;
}

.admin-menu li a:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.admin-menu li a.active {
    background-color: var(--primary-color);
    border-left-color: white;
}

.admin-menu li a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.admin-main {
    flex: 1;
    margin-left: var(--sidebar-width);
    padding: 20px;
    max-width: calc(100% - var(--sidebar-width));
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.admin-title {
    font-size: 1.5rem;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Common Elements */
.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    margin-bottom: 15px;
    transition: all var(--transition-speed);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(106, 90, 205, 0.3);
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all var(--transition-speed);
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-danger {
    background-color: var(--error-color);
    color: white;
}

.btn-warning {
    background-color: #ff9800;
    color: white;
}

.btn-secondary {
    background-color: #e0e0e0;
    color: #333;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.toast {
    padding: 15px 25px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s ease-out forwards;
    opacity: 0;
    transform: translateX(100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 250px;
}

.toast-success {
    background-color: var(--success-color);
}

.toast-error {
    background-color: var(--error-color);
}

.toast i {
    margin-right: 10px;
}

.toast-close {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 16px;
    margin-left: 15px;
}

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* File Management */
.file-management-container {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.admin-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.folder-selector {
    flex: 1;
    max-width: 300px;
}

.form-select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    background-color: white;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
    cursor: pointer;
}

.toolbar-actions {
    display: flex;
    gap: 10px;
}

.btn-refresh {
    background-color: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.file-grid-container {
    width: 100%;
    overflow-x: auto;
}

.file-grid-header {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr;
    padding: 12px 15px;
    background-color: rgba(var(--primary-color-rgb), 0.05);
    border-radius: 8px;
    margin-bottom: 10px;
    font-weight: 500;
    color: var(--primary-color);
}

.file-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

.file-item {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr;
    align-items: center;
    padding: 12px 15px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.file-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.file-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-icon {
    color: var(--primary-color);
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.file-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

.file-size, .file-date {
    color: #666;
    font-size: 14px;
}

.file-actions {
    display: flex;
    gap: 8px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-download {
    background-color: rgba(var(--primary-color-rgb), 0.1);
    color: var(--primary-color);
}

.btn-download:hover {
    background-color: var(--primary-color);
    color: white;
}

.btn-delete {
    background-color: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.btn-delete:hover {
    background-color: #ef4444;
    color: white;
}

.empty-folder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #999;
}

/* Tag Management */
.tag-management-section {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.tag-management-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.tag-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.tag-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.btn-add, .btn-delete {
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    list-style: none;
}

.tag-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background-color: rgba(var(--primary-color-rgb), 0.1);
    border-radius: 20px;
    color: var(--primary-color);
    font-size: 14px;
}

/* Appearance Settings */
.appearance-container {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
}

.color-settings-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.color-picker-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.color-picker {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.color-input {
    display: flex;
    align-items: center;
    gap: 15px;
}

.color-input input[type="color"] {
    width: 50px;
    height: 50px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}

.color-hex {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    background-color: #f8f8f8;
    font-family: 'Courier New', monospace;
    font-size: 0.95rem;
    min-width: 100px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.btn-save {
    background-color: var(--primary-color);
    color: white;
}

.btn-reset {
    background-color: #f0f0f0;
    color: #555;
}

.preview-container {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    height: 500px;
    display: flex;
    flex-direction: column;
    position: relative;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.preview-header {
    height: 70px;
    padding: 0 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    width: 100%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.preview-sidebar {
    width: 250px;
    height: 100%;
    color: white;
    padding: 20px 0;
    position: absolute;
    left: 0;
    top: 70px;
    z-index: 1;
}

.sidebar-item {
    padding: 12px 25px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.95rem;
}

.preview-content {
    flex: 1;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 250px;
}

.preview-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    width: 100%;
    max-width: 500px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.preview-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    margin-right: 15px;
    font-weight: 500;
    transition: all 0.2s ease;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .color-settings-card {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    
    .admin-main {
        margin-left: 0;
        max-width: 100%;
    }
    
    .file-grid-container {
        overflow-x: auto;
    }
}

/* File Icons */
.fa-file-image { color: #4CAF50; }
.fa-file-audio { color: #2196F3; }
.fa-file-video { color: #FF5722; }
.fa-file-pdf { color: #F44336; }
.fa-file-word { color: #2196F3; }
.fa-file-excel { color: #4CAF50; }
.fa-file-powerpoint { color: #FF5722; }
.fa-file-archive { color: #795548; }
.fa-file-code { color: #607D8B; }
.fa-file-alt { color: #9E9E9E; }




.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(var(--primary-color-rgb), 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 18px;
}

.stat-info {
    flex: 1;
}

.stat-title {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-weight: 600;
    font-size: 18px;
    color: var(--text-color);
}

.progress-container {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-bar {
    height: 100%;
    background-color: var(--primary-color);
    border-radius: 10px;
    transition: width 0.5s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 10px;
    font-weight: bold;
}

.stat-details {
    font-size: 12px;
    color: #777;
}

@media (max-width: 768px) {
    .admin-stats {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 480px) {
    .admin-stats {
        grid-template-columns: 1fr;
    }
}

</style>
</head>
<body>
    <!-- 左侧导航栏 -->
   <div class="admin-sidebar">
    <div class="admin-logo">
        <h2><i class="fas fa-user-shield"></i> 管理员面板</h2>
    </div>
    <ul class="admin-menu">
 
    <li>
        <a href="admin.php?tab=dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> 控制面板
        </a>
    </li>
        <li>
            <a href="admin.php?tab=logs" class="<?php echo $activeTab === 'logs' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i> 操作日志
            </a>
        </li>
        <li>
            <a href="wjgl.php" class="<?php echo $activeTab === 'folders' ? 'active' : ''; ?>">
                <i class="fas fa-folder"></i> 文件管理
            </a>
        </li>
        <li>
            <a href="admin.php?tab=appearance" class="<?php echo $activeTab === 'appearance' ? 'active' : ''; ?>">
                <i class="fas fa-palette"></i> 外观设置
            </a>
        </li>
        
    
        <!--<li>-->
        <!--    <a href="admin2.php?tab=advanced" class="<?php echo $activeTab === 'advanced' ? 'active' : ''; ?>">-->
        <!--        <i class="fas fa-cogs"></i> 高级设置-->
        <!--    </a>-->
        <!--</li>-->
        
      
        <li>
            <a href="index.php" class="">
                <i class="fas fa-home"></i> 返回首页
            </a>
        </li>
        <li>
            <a href="logout.php" class="">
                <i class="fas fa-sign-out-alt"></i> 退出登录
            </a>
        </li>
    </ul>
</div>

    <!-- 主内容区 -->
    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">
                <?php 
                    switch ($activeTab) {
                        case 'logs': echo '<i class="fas fa-clipboard-list"></i> '; break;
                        // case 'folders': echo '<i class="fas fa-folder"></i> 文件管理'; break;
                        case 'appearance': echo '<i class="fas fa-palette"></i> 外观设置'; break;
                        default: echo '<i class="fas fa-user-shield"></i> 管理员控制面板';
                    }
                ?>
            </h1>
        </div>

        <?php if ($message): ?>
            <div id="toast-container" class="toast-container">
                <div class="toast toast-<?php echo $messageType; ?>">
                    <div>
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <button class="toast-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <script>
                // 3秒后自动消失
                setTimeout(() => {
                    document.querySelector('.toast').style.animation = 'fadeOut 0.3s ease-out forwards';
                    setTimeout(() => document.querySelector('.toast').remove(), 300);
                }, 3000);
            </script>
        <?php endif; ?>

        <!-- 操作日志标签内容 -->
        <div id="logs-tab" class="tab-content <?php echo $activeTab === 'logs' ? 'active' : ''; ?>">
       <?php
session_start();
require_once 'admin_logger.php';

// 检查管理员权限
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

$logger = new AdminLogger();

// 分页设置
$perPage = 20; // 每页显示的日志数量
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // 当前页码
$offset = ($currentPage - 1) * $perPage; // 计算偏移量

// 获取日志总数
$totalLogs = $logger->getLogCount();
$totalPages = ceil($totalLogs / $perPage); // 计算总页数

// 获取当前页的日志
$logs = $logger->getLogs($perPage, $offset);

// 处理清空日志请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    try {
        $logFilePath = 'logs/admin_actions.log';
        
        // 检查文件是否存在并可写
        if (!file_exists($logFilePath)) {
            throw new Exception("日志文件不存在");
        }
        
        if (!is_writable($logFilePath)) {
            throw new Exception("日志文件不可写");
        }
        
        // 清空文件内容
        if (file_put_contents($logFilePath, '') === false) {
            throw new Exception("无法清空日志文件");
        }
        
        // 记录清空操作
        $logger->logAction($_SESSION['admin_username'] ?? '管理员', "清空了操作日志");
        
        // 重定向避免重复提交
        header("Location: admin.php?message=".urlencode("日志已清空")."&messageType=success");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
        header("Location: admin.php?message=".urlencode("清空日志失败: ".$error)."&messageType=error");
        exit;
    }
}

// 显示消息
$message = $_GET['message'] ?? '';
$messageType = $_GET['messageType'] ?? '';
?>

     <div id="toast-container" class="toast-container">
        <?php if ($message): ?>
            <div class="toast toast-<?php echo $messageType ?>">
                <div>
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i> 操作日志监控</h1>
        
        <div class="action-buttons">
            <!--<a href="index.php" class="btn btn-secondary">-->
            <!--    <i class="fas fa-arrow-left"></i> 返回文件管理-->
            <!--</a>-->
            <form method="post" onsubmit="return confirm('确定要清空所有日志吗？此操作不可恢复！');">
                <!--<button type="submit" name="clear_logs" class="btn btn-danger">-->
                <!--    <i class="fas fa-trash"></i> 清空日志-->
                <!--</button>-->
            </form>
            <button onclick="refreshLogs()" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> 刷新日志
            </button>
            <span style="margin-left: auto; align-self: center; font-size: 14px; color: #666;">
                共 <?php echo $totalLogs; ?> 条记录
            </span>
        </div>
        
        <table class="logs-table">
            <thead>
                <tr>
                    <th>时间</th>
                    <th>IP地址</th>
                    <th>用户名</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="logs-body">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">暂无日志记录</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="log-time"><?php echo htmlspecialchars($log['time']); ?></td>
                            <td class="log-ip"><?php echo htmlspecialchars($log['ip']); ?></td>
                            <td class="log-username"><?php echo htmlspecialchars($log['username']); ?></td>
                            <td class="log-action <?php echo getActionClass($log['action']); ?>">
                                <i class="<?php echo getActionIcon($log['action']); ?>"></i>
                                <?php echo htmlspecialchars($log['action']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="admin.php?page=1" title="第一页"><i class="fas fa-angle-double-left"></i></a>
                <a href="admin.php?page=<?php echo $currentPage - 1; ?>" title="上一页"><i class="fas fa-angle-left"></i></a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                <span class="disabled"><i class="fas fa-angle-left"></i></span>
            <?php endif; ?>
            
            <?php
            // 显示页码范围
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            if ($startPage > 1) {
                echo '<a href="admin.php?page=1">1</a>';
                if ($startPage > 2) {
                    echo '<span>...</span>';
                }
            }
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $currentPage) {
                    echo '<span class="current">'.$i.'</span>';
                } else {
                    echo '<a href="admin.php?page='.$i.'">'.$i.'</a>';
                }
            }
            
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<span>...</span>';
                }
                echo '<a href="admin.php?page='.$totalPages.'">'.$totalPages.'</a>';
            }
            ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="admin.php?page=<?php echo $currentPage + 1; ?>" title="下一页"><i class="fas fa-angle-right"></i></a>
                <a href="admin.php?page=<?php echo $totalPages; ?>" title="最后一页"><i class="fas fa-angle-double-right"></i></a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-angle-right"></i></span>
                <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // 刷新日志
        function refreshLogs() {
            fetch('get_logs.php?page=<?php echo $currentPage; ?>')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('logs-body').innerHTML = html;
                    showToast('日志已刷新', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('刷新日志失败', 'error');
                });
        }
        
        // 显示Toast通知
        function showToast(message, type) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `
                <div>
                    <i class="fas ${icon}"></i>
                    ${message}
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            // 3秒后自动消失
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // 自动刷新日志 (每30秒)
        setInterval(refreshLogs, 30000);
    </script>
</body>
</html>

<?php
// 根据操作类型获取CSS类
function getActionClass($action) {
    if (strpos($action, '上传') !== false) {
        return 'action-upload';
    } elseif (strpos($action, '下载') !== false) {
        return 'action-download';
    } elseif (strpos($action, '删除') !== false) {
        return 'action-delete';
    } else {
        return 'action-other';
    }
}

// 根据操作类型获取图标
function getActionIcon($action) {
    if (strpos($action, '上传') !== false) {
        return 'fas fa-cloud-upload-alt';
    } elseif (strpos($action, '下载') !== false) {
        return 'fas fa-cloud-download-alt';
    } elseif (strpos($action, '删除') !== false) {
        return 'fas fa-trash';
    } else {
        return 'fas fa-info-circle';
    }
}
?>
        </div>
<!-- 在admin.php文件中，找到admin-header部分，在admin-title下方添加以下代码 -->



   <div id="dashboardb" class="tab-content <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">

<div class="admin-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-hdd"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">磁盘使用情况</div>
            <div class="stat-value">
                <?php
                $diskTotal = disk_total_space(__DIR__);
                $diskFree = disk_free_space(__DIR__);
                $diskUsed = $diskTotal - $diskFree;
                $diskPercent = round(($diskUsed / $diskTotal) * 100, 2);
                ?>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $diskPercent; ?>%"></div>
                    <span class="progress-text"><?php echo $diskPercent; ?>%</span>
                </div>
                <div class="stat-details">
                    <?php echo formatFileSize($diskUsed); ?> / <?php echo formatFileSize($diskTotal); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-folder"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">文件夹数量</div>
            <div class="stat-value">
                <?php echo count($allowedTags); ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-file"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">文件总数</div>
            <div class="stat-value">
                <?php
                $totalFiles = 0;
                foreach ($allowedTags as $tag) {
                    $dir = 'uploads/' . $tag . '/';
                    if (file_exists($dir)) {
                        $files = array_diff(scandir($dir), ['.', '..']);
                        $totalFiles += count($files);
                    }
                }
                echo $totalFiles;
                ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">当前用户</div>
            <div class="stat-value">
                <?php echo htmlspecialchars($currentAdmin); ?>
            </div>
        </div>
    </div>
</div>
</div>


   <div id="folders-tab" class="tab-content <?php echo $activeTab === 'folders' ? 'active' : ''; ?>">
    <div class="file-management-container">
        <!-- 顶部操作栏 -->
        <div class="admin-toolbar">
            <div class="folder-selector">
                <form method="get" class="folder-form">
                    <input type="hidden" name="tab" value="folders">
                    <div class="form-group">
                        <select name="folder" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allowedTags as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>" <?php echo $selectedFolder == $tag ? 'selected' : ''; ?>>
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($tag); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="toolbar-actions">
                <button class="btn btn-refresh" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> 刷新
                </button>
            </div>
        </div>

        <!-- 文件列表卡片视图 -->
        <div class="file-grid-container">
            <?php 
            // 分页设置
            $filesPerPage = 7; // 每页显示的文件数量
            $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $totalFiles = count($files);
            $totalPages = ceil($totalFiles / $filesPerPage);
            $startIndex = ($currentPage - 1) * $filesPerPage;
            $paginatedFiles = array_slice($files, $startIndex, $filesPerPage);
            
            if (empty($paginatedFiles)): ?>
                <div class="empty-folder">
                    <i class="fas fa-folder-open"></i>
                    <p>此文件夹为空</p>
                </div>
            <?php else: ?>
                <div class="file-grid-header">
                    <div class="file-grid-header-item name">文件名</div>
                    <div class="file-grid-header-item size">大小</div>
                    <div class="file-grid-header-item date">修改日期</div>
                    <div class="file-grid-header-item actions">操作</div>
                </div>
                
                <div class="file-grid">
                    <?php foreach ($paginatedFiles as $file): 
                        $filePath = $folderPath . $file;
                        $fileSize = filesize($filePath);
                        $fileDate = date("Y-m-d H:i:s", filemtime($filePath));
                        $fileIcon = getFileIcon($file);
                        $fileUrl = 'download.php?file='.urlencode($file).'&tag='.urlencode($selectedFolder);
                    ?>
                        <div class="file-item">
                            <div class="file-info">
                                <div class="file-icon">
                                    <i class="<?php echo $fileIcon; ?>"></i>
                                </div>
                                <div class="file-name" title="<?php echo htmlspecialchars($file); ?>">
                                    <?php echo htmlspecialchars($file); ?>
                                </div>
                            </div>
                            <div class="file-size"><?php echo formatFileSize($fileSize); ?></div>
                            <div class="file-date"><?php echo $fileDate; ?></div>
                            <div class="file-actions">
                                <a href="<?php echo $fileUrl; ?>" class="btn-action btn-download" title="下载" onclick="logDownload('<?php echo htmlspecialchars($file); ?>', '<?php echo htmlspecialchars($selectedFolder); ?>')">
                                    <i class="fas fa-download"></i>
                                </a>
                                <form method="post" class="delete-form" onsubmit="return confirm('确定要永久删除此文件吗？');">
                                    <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($file); ?>">
                                    <input type="hidden" name="folder" value="<?php echo htmlspecialchars($selectedFolder); ?>">
                                    <button type="submit" name="delete_file" class="btn-action btn-delete" title="删除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 分页导航 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 20px;">
                    <?php if ($currentPage > 1): ?>
                        <a href="admin.php?tab=folders&folder=<?php echo urlencode($selectedFolder); ?>&page=1" class="btn btn-sm btn-secondary">
                            <i class="fas fa-angle-double-left"></i> 第一页
                        </a>
                        <a href="admin.php?tab=folders&folder=<?php echo urlencode($selectedFolder); ?>&page=<?php echo $currentPage - 1; ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-angle-left"></i> 上一页
                        </a>
                    <?php endif; ?>
                    
                    <span style="margin: 0 15px; line-height: 30px;">
                        第 <?php echo $currentPage; ?> 页 / 共 <?php echo $totalPages; ?> 页
                    </span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="admin.php?tab=folders&folder=<?php echo urlencode($selectedFolder); ?>&page=<?php echo $currentPage + 1; ?>" class="btn btn-sm btn-secondary">
                            下一页 <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="admin.php?tab=folders&folder=<?php echo urlencode($selectedFolder); ?>&page=<?php echo $totalPages; ?>" class="btn btn-sm btn-secondary">
                            最后一页 <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 标签管理部分 -->
    <div class="tag-management-section">
        <h3><i class="fas fa-tags"></i> 标签管理</h3>
        
        <div class="tag-management-cards">
            <!-- 添加标签卡片 -->
            <div class="tag-card">
                <div class="tag-card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h4>添加新标签</h4>
                </div>
                <form method="post" class="tag-form">
                    <div class="form-group">
                        <input type="text" name="new_tag" class="form-input" placeholder="输入新标签名称" required>
                    </div>
                    <input type="hidden" name="action" value="add_tag">
                    <button type="submit" class="btn btn-add">
                        <i class="fas fa-plus"></i> 添加
                    </button>
                </form>
            </div>
            
            <!-- 删除标签卡片 -->
            <div class="tag-card">
                <div class="tag-card-header">
                    <i class="fas fa-minus-circle"></i>
                    <h4>删除标签</h4>
                </div>
                <form method="post" class="tag-form">
                    <div class="form-group">
                        <select name="tag_to_delete" class="form-select" required>
                            <option value="">选择要删除的标签</option>
                            <?php foreach ($allowedTags as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="delete_tag">
                    <button type="submit" class="btn btn-delete">
                        <i class="fas fa-trash"></i> 删除
                    </button>
                </form>
            </div>
            
            <!-- 当前标签卡片 -->
            <div class="tag-card tag-list-card">
                <div class="tag-card-header">
                    <i class="fas fa-list"></i>
                    <h4>当前标签</h4>
                </div>
                <div class="current-tags">
                    <?php if (empty($allowedTags)): ?>
                        <p class="no-tags">暂无标签</p>
                    <?php else: ?>
                        <ul class="tags-list">
                            <?php foreach ($allowedTags as $tag): ?>
                                <li>
                                    <span class="tag-badge">
                                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($tag); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="appearance-tab" class="tab-content <?php echo $activeTab === 'appearance' ? 'active' : ''; ?>">
    <div class="appearance-container">
        <div class="appearance-header">
       
        </div>
        
        <div class="color-settings-card">
            <div class="color-settings-form">
                <h3><i class="fas fa-sliders-h"></i> 颜色配置</h3>
                
                <form method="post" class="admin-form">
                    <div class="color-picker-group">
                        <div class="color-picker">
                            <label for="primary_color">主色调</label>
                            <div class="color-input">
                                <input type="color" id="primary_color" name="primary_color" 
                                       value="<?php echo $styles['primary-color']; ?>">
                                <span class="color-hex"><?php echo $styles['primary-color']; ?></span>
                            </div>
                        </div>
                        
                        <div class="color-picker">
                            <label for="secondary_color">次色调</label>
                            <div class="color-input">
                                <input type="color" id="secondary_color" name="secondary_color" 
                                       value="<?php echo $styles['secondary-color']; ?>">
                                <span class="color-hex"><?php echo $styles['secondary-color']; ?></span>
                            </div>
                        </div>
                        
                        <div class="color-picker">
                            <label for="dark_color">深色调</label>
                            <div class="color-input">
                                <input type="color" id="dark_color" name="dark_color" 
                                       value="<?php echo $styles['dark-color']; ?>">
                                <span class="color-hex"><?php echo $styles['dark-color']; ?></span>
                            </div>
                        </div>
                        
                        <div class="color-picker">
                            <label for="bg_light">背景颜色</label>
                            <div class="color-input">
                                <input type="color" id="bg_light" name="bg_light" 
                                       value="<?php echo $styles['bg-light']; ?>">
                                <span class="color-hex"><?php echo $styles['bg-light']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="action" value="update_style">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save"></i> 保存设置
                        </button>
                        <button type="submit" name="action" value="reset_style" class="btn btn-reset">
                            <i class="fas fa-undo"></i> 恢复默认
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="color-preview">
                <h3><i class="fas fa-eye"></i> 实时预览</h3>
                <div class="preview-container">
                    <div class="preview-header" id="preview-header">
                        <div class="preview-logo">桂工网盘</div>
                        <div class="preview-nav">
                            <div class="nav-item active">首页</div>
                            <div class="nav-item">文件</div>
                            <div class="nav-item">设置</div>
                        </div>
                    </div>
                    
                    <div class="preview-sidebar" id="preview-sidebar">
                        <div class="sidebar-item active">
                            <i class="fas fa-home"></i> 仪表盘
                        </div>
                        <div class="sidebar-item">
                            <i class="fas fa-folder"></i> 我的文件
                        </div>
                        <div class="sidebar-item">
                            <i class="fas fa-upload"></i> 上传文件
                        </div>
                        <div class="sidebar-item">
                            <i class="fas fa-cog"></i> 设置
                        </div>
                    </div>
                    
                    <div class="preview-content" id="preview-content">
                        <div class="preview-card">
                            <h5>欢迎使用桂工网盘</h5>
                            <p>这是一个文件管理系统的预览界面，展示您当前的主题设置效果。</p>
                            <button class="preview-btn" id="preview-primary-btn">主要按钮</button>
                            <button class="preview-btn secondary" id="preview-secondary-btn">次要按钮</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>




<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取所有颜色输入元素
    const primaryColorInput = document.getElementById('primary_color');
    const secondaryColorInput = document.getElementById('secondary_color');
    const darkColorInput = document.getElementById('dark_color');
    const bgLightInput = document.getElementById('bg_light');
    
    // 获取预览元素
    const previewHeader = document.getElementById('preview-header');
    const previewSidebar = document.getElementById('preview-sidebar');
    const previewContent = document.getElementById('preview-content');
    const previewPrimaryBtn = document.getElementById('preview-primary-btn');
    const previewSecondaryBtn = document.getElementById('preview-secondary-btn');
    
    // 实时更新预览
    function updatePreview() {
        // 更新预览区域样式
        previewHeader.style.backgroundColor = darkColorInput.value;
        previewSidebar.style.backgroundColor = primaryColorInput.value;
        previewContent.style.backgroundColor = bgLightInput.value;
        previewPrimaryBtn.style.backgroundColor = primaryColorInput.value;
        previewSecondaryBtn.style.backgroundColor = secondaryColorInput.value;
        
        // 更新十六进制显示
        document.querySelector('#primary_color + .color-hex').textContent = primaryColorInput.value.toUpperCase();
        document.querySelector('#secondary_color + .color-hex').textContent = secondaryColorInput.value.toUpperCase();
        document.querySelector('#dark_color + .color-hex').textContent = darkColorInput.value.toUpperCase();
        document.querySelector('#bg_light + .color-hex').textContent = bgLightInput.value.toUpperCase();
    }
    
    // 为所有颜色输入添加事件监听器
    [primaryColorInput, secondaryColorInput, darkColorInput, bgLightInput].forEach(input => {
        input.addEventListener('input', updatePreview);
    });
    
    // 为十六进制输入框添加事件监听器
    document.querySelectorAll('.color-hex').forEach(hexInput => {
        hexInput.addEventListener('input', function() {
            const colorInput = this.previousElementSibling;
            const colorValue = this.value;
            
            // 验证是否为有效的十六进制颜色
            if (/^#[0-9A-F]{6}$/i.test(colorValue)) {
                colorInput.value = colorValue;
                updatePreview();
            }
        });
    });
    
    // 恢复默认按钮功能
    document.querySelector('.btn-reset').addEventListener('click', function(e) {
        if (confirm('确定要恢复默认颜色设置吗？')) {
            // 设置默认颜色值
            const defaultColors = {
                'primary_color': '#6a5acd',
                'secondary_color': '#9370db',
                'dark_color': '#483d8b',
                'bg_light': 'rgba(242, 242, 245, 0.99)'
            };
            
            // 更新输入框值
            primaryColorInput.value = defaultColors.primary_color;
            secondaryColorInput.value = defaultColors.secondary_color;
            darkColorInput.value = defaultColors.dark_color;
            bgLightInput.value = defaultColors.bg_light;
            
            // 更新预览
            updatePreview();
            
            // 提交表单以保存默认设置
            this.form.submit();
        } else {
            e.preventDefault();
        }
    });
});
</script>



    <?php
    // 格式化文件大小
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

    // 获取文件图标
    function getFileIcon($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $iconMap = [
            // 图片
            'jpg' => 'fas fa-file-image',
            'jpeg' => 'fas fa-file-image',
            'png' => 'fas fa-file-image',
            'gif' => 'fas fa-file-image',
            'bmp' => 'fas fa-file-image',
            'svg' => 'fas fa-file-image',
            'webp' => 'fas fa-file-image',
            
            // 音频
            'mp3' => 'fas fa-file-audio',
            'wav' => 'fas fa-file-audio',
            'ogg' => 'fas fa-file-audio',
            'flac' => 'fas fa-file-audio',
                 'aac' => 'fas fa-file-audio',
        
        // 视频
        'mp4' => 'fas fa-file-video',
        'avi' => 'fas fa-file-video',
        'mov' => 'fas fa-file-video',
        'wmv' => 'fas fa-file-video',
        'flv' => 'fas fa-file-video',
        'mkv' => 'fas fa-file-video',
        
        // 压缩文件
        'zip' => 'fas fa-file-archive',
        'rar' => 'fas fa-file-archive',
        '7z' => 'fas fa-file-archive',
        'tar' => 'fas fa-file-archive',
        'gz' => 'fas fa-file-archive',
        
        // 代码文件
        'html' => 'fas fa-file-code',
        'htm' => 'fas fa-file-code',
        'css' => 'fas fa-file-code',
        'js' => 'fas fa-file-code',
        'php' => 'fas fa-file-code',
        'json' => 'fas fa-file-code',
        'xml' => 'fas fa-file-code',
        
        // 其他
        'exe' => 'fas fa-file',
        'dll' => 'fas fa-file',
        'ini' => 'fas fa-file',
        'bat' => 'fas fa-file',
        'sh' => 'fas fa-file',
    ];
    
    return $iconMap[$extension] ?? 'fas fa-file';
}
?>