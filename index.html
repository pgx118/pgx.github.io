<?php
// 启动会话
session_start();

// 检查管理员登录状态
$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

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

// 置顶文件数据文件
$pinnedFile = 'pinned_files.json';
if (file_exists($pinnedFile)) {
    $pinnedFiles = json_decode(file_get_contents($pinnedFile), true);
    if (!is_array($pinnedFiles)) {
        $pinnedFiles = [];
    }
} else {
    $pinnedFiles = [];
    file_put_contents($pinnedFile, json_encode($pinnedFiles));
}

// 获取当前标签
$currentTag = isset($_GET['tag']) && in_array($_GET['tag'], $allowedTags) ? $_GET['tag'] : $allowedTags[0];

// 获取上传目录中的文件列表
$uploadDir = 'uploads/' . $currentTag . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$files = scandir($uploadDir);
$files = array_diff($files, array('.', '..'));

// 准备文件列表数据
$fileList = [];
foreach ($files as $file) {
    $filePath = $uploadDir . $file;
    $fileList[$file] = [
        'name' => $file,
        'size' => filesize($filePath),
        'mod_time' => filemtime($filePath),
        'is_pinned' => isset($pinnedFiles[$file]) && $pinnedFiles[$file]['tag'] === $currentTag,
        'pinned_time' => isset($pinnedFiles[$file]) ? $pinnedFiles[$file]['pinned_time'] : 0
    ];
}

// 排序文件列表：先显示置顶文件（按置顶时间倒序），然后显示普通文件（按修改时间倒序）
usort($fileList, function($a, $b) {
    if ($a['is_pinned'] && $b['is_pinned']) {
        return $b['pinned_time'] <=> $a['pinned_time']; // 置顶时间倒序
    } elseif ($a['is_pinned']) {
        return -1; // a置顶，排前面
    } elseif ($b['is_pinned']) {
        return 1; // b置顶，排前面
    } else {
        return $b['mod_time'] <=> $a['mod_time']; // 修改时间倒序
    }
});
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$isSearching = !empty($searchKeyword);
// 显示消息（如果有）
$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['messageType']) ? $_GET['messageType'] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>桂工本地网盘</title>
     <link rel="stylesheet" href="index.css">
<link rel="stylesheet" href="css/all.min.css">
<!-- 替换为本地JS库引用 -->
<script src="libs/jszip.min.js"></script>
<script src="libs/docx-preview.min.js"></script>
<script src="libs/xlsx.full.min.js"></script>
<script src="libs/pdf.min.js"></script>
<script>

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'libs/pdf.worker.min.js';
    
    window.docx = window.docx || docx;
</script>
    <link rel="icon" type="image/png" href="img/tb.jpg">
    <style>
   .stop-btn {
        background-color: var(--error-color) !important;
    }
    
    .stop-btn:hover {
        background-color: #d32f2f !important;
    }
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
    padding-bottom: 50px;
}

/* 弹窗样式 */
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
    background-color: var(--success-color) !important;
}

.toast-error {
    background-color: var(--error-color) !important;
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
        transform: translateX(0);
    }
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* 模态框样式 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9998;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease-out forwards;
}

.modal-content {
    background-color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    max-width: 500px;
    width: 90%;
    transform: translateY(-20px);
    animation: slideUp 0.3s ease-out forwards;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.modal-title {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
    transition: color 0.2s;
}

.modal-close:hover {
    color: var(--error-color);
}

.modal-body {
    margin-bottom: 20px;
    line-height: 1.6;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}

.modal-btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.modal-btn-danger {
    background-color: var(--error-color);
    color: white;
}

.modal-btn-secondary {
    background-color: #e0e0e0;
    color: #333;
}

.modal-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); }
    to { transform: translateY(0); }
}

.school-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    animation: fadeIn 0.5s ease-in-out;
}

.school-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: transform var(--transition-speed);
}

.school-item:hover {
    transform: scale(1.05) rotate(5deg);
}

.school-icon {
    width: 80px;
    height: 80px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    object-fit: contain;
    background-color: white;
    padding: 1px;
    transition: all var(--transition-speed);
}

.school-item:hover .school-icon {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    transform: rotate(-5deg);
}

.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background-color: var(--card-bg);
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(106, 90, 205, 0.1);
    animation: slideUp 0.5s ease-out;
    border: 1px solid rgba(106, 90, 205, 0.1);
    /* 添加固定高度 */
    height: 800px; /* 或者你想要的固定高度 */
    /* 确保布局正确 */
    display: flex;
    flex-direction: column;
    position: relative; /* 为分页的绝对定位提供参考 */
}

/* 标签样式 */
.tag-container {
    display: flex;
    justify-content: center;

    flex-wrap: wrap;
    gap: 10px;
}

.tag {
    padding: 10px 20px;
    border-radius: 25px;
    background-color: rgba(106, 90, 205, 0.1);
    color: var(--primary-color);
    text-decoration: none;
    transition: all var(--transition-speed);
    border: 1px solid rgba(106, 90, 205, 0.2);
    font-weight: 500;
}

.tag:hover {
    background-color: rgba(106, 90, 205, 0.2);
    transform: translateY(-2px);
}

.tag.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.upload-section {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all var(--transition-speed);
    border: 1px solid rgba(0, 0, 0, 0.05);
    /* 固定高度 */
    height: auto; /* 或者设置固定高度如 200px */
    min-height: 200px;
}

.upload-section:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transform: translateY(-3px);
}

.upload-section h2 {
    margin-bottom: 20px;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.upload-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.file-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 15px;
}

.file-label {
    display: inline-block;
    padding: 12px 25px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--transition-speed);
    font-weight: 500;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    border: none;
}

.file-label:hover {
    background-color: var(--dark-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.file-label i {
    margin-right: 8px;
}

.file-input {
    position: absolute;
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    z-index: -1;
}

#file-name {
    flex-grow: 1;
    padding: 10px;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    border: 1px dashed rgba(106, 90, 205, 0.3);
    color: var(--text-color);
    transition: all var(--transition-speed);
}

.upload-btn {
    padding: 12px 25px;
    background-color: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: all var(--transition-speed);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    align-self: flex-end;
}

.upload-btn:hover {
    background-color: var(--dark-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
.file-list {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all var(--transition-speed);
    border: 1px solid rgba(0, 0, 0, 0.05);
    flex: 1;
    overflow-y: auto; 
    margin-bottom: 70px; 
    scrollbar-width: thin; 
    scrollbar-color: rgba(0, 0, 0, 0.2) transparent; /* Firefox - 滑块颜色和轨道颜色 */
}
.file-list::-webkit-scrollbar-track {
    background: transparent; /* 滚动条轨道背景 */
    border-radius: 4px;
}

.file-list::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2); /* 滚动条滑块颜色 */
    border-radius: 4px;
}

.file-list::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3); /* 鼠标悬停时的滑块颜色 */
}
/*.file-list:hover {*/
/*    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);*/
/*    transform: translateY(-3px);*/
/*}*/
.file-list::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
}
.file-list td{
   
    transform: translateY(-3px);
}
.file-list td:hover {
    
    transform: translateY(-4px);
}
.file-list h2 {
    margin-bottom: 20px;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.no-files {
    text-align: center;
    color: rgba(0, 0, 0, 0.5);
    font-style: italic;
    padding: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    animation: fadeIn 0.5s ease-in-out;
}

th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

th {
    background-color: rgba(106, 90, 205, 0.05);
    color: var(--primary-color);
    font-weight: 500;
}

tr {
    transition: all var(--transition-speed);
}

tr:hover {
    background-color: rgba(106, 90, 205, 0.03);
}



.download-btn, .delete-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: all var(--transition-speed);
}

.download-btn {
    background-color: var(--success-color);
}

.delete-btn {
    background-color: var(--error-color);
}

.download-btn:hover, .delete-btn:hover {
    transform: scale(1.1) rotate(10deg);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* 管理员面板样式 */
.admin-panel {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all var(--transition-speed);
    animation: fadeIn 0.5s ease-in-out;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.admin-panel:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transform: translateY(-3px);
}

.admin-panel h2 {
    margin-bottom: 20px;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--primary-color);
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    background-color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(106, 90, 205, 0.2);
    border-radius: 8px;
    color: var(--text-color);
    transition: all var(--transition-speed);
}

.form-control:focus {
    outline: none;
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 2px rgba(106, 90, 205, 0.3);
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all var(--transition-speed);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-danger {
    background-color: var(--error-color);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

/* 登录按钮样式 */
.login-btn {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: all var(--transition-speed);
    z-index: 100;
    box-shadow: 0 2px 8px rgba(106, 90, 205, 0.3);
}

.login-btn:hover {
    background-color: var(--dark-color);
    transform: translateY(-2px);
}

/* 动画效果 */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

/* 响应式设计 */
@media (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 10px;
    }
    
    .tag-container {
        flex-direction: column;
        align-items: center;
    }
    
    .file-input-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .upload-btn {
        align-self: stretch;
    }
    
    table {
        display: block;
        overflow-x: auto;
    }
    
    .modal-content {
        width: 95%;
        padding: 15px;
    }
}.progress-container {
    width: 100;
    background-color: #f1f1f1;
    border-radius: 8px;
    margin: 15px 0;
    display: none;
}

.progress-bar {
    height: 20px;
    border-radius: 8px;
    background-color: var(--primary-color);
    width: 0%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.progress-text {
    margin-top: 5px;
    text-align: center;
    font-size: 14px;
    color: var(--primary-color);
}/* 上传按钮容器 */
.upload-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* 继续上传按钮 */
.resume-btn {
    background-color: var(--success-color) !important;
}

.resume-btn:hover {
    background-color: #388e3c !important;
}

/* 上传新文件按钮 */
.new-upload-btn {
    background-color: var(--secondary-color) !important;
}

.new-upload-btn:hover {
    background-color: var(--dark-color) !important;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    flex-wrap: wrap;
    position: absolute;
    bottom: 20px;
    left: 20px;
    right: 20px;
    background: var(--card-bg);
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    z-index: 10;
}

.page-link {
    padding: 8px 15px;
    border-radius: 5px;
    background-color: rgba(106, 90, 205, 0.1);
    color: var(--primary-color);
    text-decoration: none;
    transition: all var(--transition-speed);
    border: 1px solid rgba(106, 90, 205, 0.2);
    font-weight: 500;
}

.page-link:hover {
    background-color: rgba(106, 90, 205, 0.2);
    transform: translateY(-2px);
}

.page-link.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
    cursor: default;
}

.page-link i {
    margin: 0 5px;
}.announcement-banner {
    padding: 15px 25px;
    margin: 0 auto 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
}

.announcement-banner.info {
    background-color: rgba(33, 150, 243, 0.1);
    border-left: 5px solid var(--info-color);
    color: var(--info-color);
}

.announcement-banner.success {
    background-color: rgba(76, 175, 80, 0.1);
    border-left: 5px solid var(--success-color);
    color: var(--success-color);
}

.announcement-banner.warning {
    background-color: rgba(255, 152, 0, 0.1);
    border-left: 5px solid var(--warning-color);
    color: var(--warning-color);
}

.announcement-banner.error {
    background-color: rgba(244, 67, 54, 0.1);
    border-left: 5px solid var(--error-color);
    color: var(--error-color);
}

.announcement-content {
    max-width: 1200px;
    margin: 0 auto;
}

.announcement-content h3 {
    margin-bottom: 10px;
}
/* 为所有 Font Awesome 下载图标设置本地图片 */
.fa-download::before {
    content: "";
    display: inline-block;
    width: 16px;
    height: 16px;
    background-image: url('tp/download.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
}

/* 隐藏原来的 Font Awesome 图标 */
.fa-download {
    font-family: inherit !important;
}
.fa-folder-open::before {
    content: "";
    display: inline-block;
    background-image: url('tp/file.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    /* 完全自适应父容器大小 */
    width: 100%;
    height: 100%;
}

.fa-folder-open {
    font-family: inherit !important;
    /* 设置一个基准大小，实际使用时可以覆盖 */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}

.fa-file-word::before {
    content: "";
    display: inline-block;
    background-image: url('tp/word.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}

.fa-file-word {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}


.fa-sign-in-alt::before {
    content: "";
    display: inline-block;
    background-image: url('tp/gly.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}
.fa-sign-in-alt {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}



.fa-file::before {
    content: "";
    display: inline-block;
    background-image: url('tp/qt.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}

.fa-file {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}


.fa-file-powerpoint::before {
    content: "";
    display: inline-block;
    background-image: url('tp/ppt.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}

.fa-file-powerpoint {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}

.fa-file-excel::before {
    content: "";
    display: inline-block;
    background-image: url('tp/excel.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    width: 100%;
    height: 100%;
}

.fa-file-excel {
    font-family: inherit !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}


.fa-file-pdf::before {
    content: "";
    display: inline-block;
    background-image: url('tp/pdf.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    width: 100%;
    height: 100%;
}

.fa-file-pdf {
    font-family: inherit !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}


.fa-file-archive::before {
    content: "";
    display: inline-block;
    background-image: url('tp/ysb.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    width: 100%;
    height: 100%;
}

.fa-file-archive {
    font-family: inherit !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}

.fa-file::before {
    content: "";
    display: inline-block;
    background-image: url('tp/qt.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    width: 100%;
    height: 100%;
}

.fa-file {
    font-family: inherit !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}
.fa-chevron-right::before {
    content: "";
    display: inline-block;
    background-image: url('tp/107.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    width: 100%;
    height: 100%;
}

.fa-chevron-right{
    font-family: inherit !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}

    #previewModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            overflow: auto;
        }
        
        #previewContent {
            background-color: white;
            margin: 2% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 900px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        #previewClose {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        
        #previewTitle {
            margin-bottom: 15px;
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        #previewContainer {
            width: 100%;
            min-height: 500px;
            border: 1px solid #eee;
            padding: 15px;
        }
        
        .preview-loading {
            text-align: center;
            padding: 50px;
            font-size: 1.2rem;
            color: #666;
        }/* 文件列表中的链接样式 */
.file-list table td a {
    color: #000000; /* 黑色字体 */
    text-decoration: none; /* 默认无下划线 */
    transition: all 0.2s ease; /* 平滑过渡效果 */
}

/* 鼠标悬停时显示下划线 */
.file-list table td a:hover {
    color: #000000; /* 保持黑色 */
    text-decoration: underline; /* 悬停时显示下划线 */
    text-decoration-thickness: 1px; /* 下划线粗细 */
    text-underline-offset: 3px; /* 下划线偏移量，可选 */
}

/* 其他状态保持无下划线 */
.file-list table td a:visited,
.file-list table td a:focus,
.file-list table td a:active {
    color: #000000;
    text-decoration: none;
}

.search-container {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.search-form {
    width: 50%;
    max-width: 500px;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
   
}

.search-input {
    width: 100%;
    padding: 12px 50px 12px 20px;
    border: 2px solid var(--primary-color);
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    transition: all var(--transition-speed);
    background: white;
}

.search-input:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 10px rgba(106, 90, 205, 0.2);
}

.search-btn {
    position: absolute;
    right: <?php echo $isSearching ? '60px' : '10px'; ?>;
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 8px;
    transition: color var(--transition-speed);
}

.search-btn:hover {
    color: var(--dark-color);
}

.cancel-search-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 8px;
    transition: color var(--transition-speed);
}

.cancel-search-btn:hover {
    color: var(--error-color);
}

/* 搜索结果样式 */
.search-results-info {
    text-align: center;
    margin: 10px 0;
    color: var(--primary-color);
    font-weight: 500;
}


    </style>
</head>
<body>
    <div id="toast-container" class="toast-container"></div>
    <!-- 预览模态框 -->
<div id="previewModal">
    <span id="previewClose">&times;</span>
    <div id="previewContent">
        <h3 id="previewTitle"></h3>
        <div id="previewContainer" class="preview-loading">
            正在加载文件预览...
        </div>
    </div>
</div>
 
    <?php if ($isAdmin): ?>
      
    <?php else: ?>
      
    <?php endif; ?>
    
     <div class="school-container">
        <a href="https://103.38.81.5/gllg/" style="text-decoration: none;">
            <div class="school-item">
                <img src="img/tb.jpg" alt="柳橙汁LCVC" class="school-icon">
            </div>
        </a>
        <a href="https://103.38.81.5/glgl/" style="text-decoration: none;">
            <div class="school-item">
                <img src="https://p1.ssl.qhimg.com/dr/220__/t015f5f2f2488c2b17e.jpg" alt="桂工网盘" class="school-icon">
            </div>
        </a>
    </div>
    
    <div class="container">
        <!-- 标签导航 -->
        <div class="tag-container">
            <?php foreach ($allowedTags as $tag): ?>
                <a href="index.php?tag=<?php echo urlencode($tag); ?>" class="tag <?php echo $tag == $currentTag ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($tag); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
                });
            </script>
        <?php endif; ?>
<!-- 搜索框 -->
<div class="search-container">
    <form id="searchForm" method="GET" class="search-form">
        <input type="hidden" name="tag" value="<?php echo htmlspecialchars($currentTag); ?>">
        <div class="search-input-wrapper">
            <input type="text" name="search" id="searchInput" 
                   placeholder="搜索所有文件夹中的文件..." 
                   value="<?php echo htmlspecialchars($searchKeyword); ?>"
                   class="search-input">
            <button type="submit" class="search-btn" title="搜索">
                <i>搜索</i>
            </button>
            <?php if ($isSearching): ?>
      <button type="button" id="cancelSearch" class="cancel-search-btn" 
        title="取消搜索" style="display: <?php echo $isSearching ? 'block' : 'none'; ?>;"
        onclick="window.location.href='https://103.38.81.5/gllg/index.php'">
    取消
</button>
                
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

      
<script>
// 全局变量
let isUploading = false;
let currentXHR = null;
let shouldStopUpload = false;
let pausedUpload = false;
let uploadedChunks = 0;
let totalChunks = 0;
let file = null;
let tag = null;
const chunkSize = 5 * 1024 * 1024; // 5MB 分片大小

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 文件选择事件 - 显示文件名和大小
    document.getElementById('file').addEventListener('change', function(e) {
        updateFileInfo(e.target);
    });

    // 上传表单提交事件
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        handleUploadSubmit(e);
    });

    // 暂停上传按钮
    document.getElementById('stopBtn').addEventListener('click', pauseUpload);

    // 继续上传按钮
    document.getElementById('resumeBtn').addEventListener('click', resumeUpload);

    // 上传新文件按钮
    document.getElementById('newUploadBtn').addEventListener('click', resetUploadForm);

    // 页面刷新警告
    window.addEventListener('beforeunload', function(e) {
        if (isUploading) {
            e.preventDefault();
            showRefreshWarning();
            return '当前有文件正在上传，刷新页面将中断上传过程。';
        }
    });
});

// 更新文件信息显示
function updateFileInfo(fileInput) {
    const fileNameDisplay = document.getElementById('file-name');
    const fileSizeDisplay = document.getElementById('file-size');
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        fileNameDisplay.textContent = file.name;
        fileSizeDisplay.textContent = formatBytes(file.size);
    } else {
        fileNameDisplay.textContent = '未选择文件';
        fileSizeDisplay.textContent = '';
    }
}

// 处理上传表单提交
function handleUploadSubmit(e) {
    const form = e.target;
    const fileInput = document.getElementById('file');
    
    // 如果是继续上传，直接从断点继续
    if (pausedUpload) {
        pausedUpload = false;
        toggleUploadButtons(false, true, false);
        uploadChunk(uploadedChunks);
        return;
    }
    
    // 验证是否选择了文件
    if (fileInput.files.length === 0) {
        showToast('请选择要上传的文件', 'error');
        return;
    }
    
    // 初始化上传参数
    file = fileInput.files[0];
    totalChunks = Math.ceil(file.size / chunkSize);
    tag = form.querySelector('input[name="tag"]').value;
    uploadedChunks = 0;
    
    // 设置上传状态
    isUploading = true;
    shouldStopUpload = false;
    pausedUpload = false;
    toggleUploadButtons(false, true, false);
    
    // 显示进度条
    showProgressBar('0%', '准备上传...');
    
    // 开始上传第一个分片
    uploadChunk(0);
}

// 上传分片函数
function uploadChunk(chunkNumber) {
    if (shouldStopUpload) {
        return handleUploadCompletion();
    }
    
    const start = chunkNumber * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    const chunk = file.slice(start, end);
    
    // 准备表单数据
    const formData = new FormData();
    formData.append('file', chunk);
    formData.append('filename', file.name);
    formData.append('tag', tag);
    formData.append('chunk', chunkNumber);
    formData.append('chunks', totalChunks);
    formData.append('chunkNumber', chunkNumber);
    formData.append('resume', pausedUpload ? '1' : '0');
    
    // 创建XHR对象
    const xhr = new XMLHttpRequest();
    currentXHR = xhr;
    
    xhr.open('POST', 'upload.php', true);
    
    // 上传进度处理
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const loaded = start + e.loaded;
            const progress = Math.round(loaded / file.size * 100);
            updateProgressBar(progress, loaded, file.size);
        }
    };
    
    // 上传完成处理
    xhr.onload = function() {
        if (xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            if (!data.success) {
                showToast(data.message || '上传失败', 'error');
                handleUploadCompletion();
            } else {
                uploadedChunks = chunkNumber + 1;
                
                if (pausedUpload) {
                    // 如果是暂停状态，显示继续和重新上传按钮
                    toggleUploadButtons(false, false, true);
                    updateProgressText(`上传已暂停 (${formatBytes(uploadedChunks * chunkSize)} / ${formatBytes(file.size)})`);
                    return;
                }
                
                if (uploadedChunks < totalChunks) {
                    // 继续上传下一个分片
                    uploadChunk(uploadedChunks);
                } else {
                    // 上传完成处理
                    handleUploadSuccess(data.message);
                }
            }
        } else {
            showToast('上传失败，状态码: ' + xhr.status, 'error');
            handleUploadCompletion();
        }
    };
    
    // 上传错误处理
    xhr.onerror = function() {
        showToast('上传过程中发生错误', 'error');
        handleUploadCompletion();
    };
    
    // 发送请求
    xhr.send(formData);
}

// 暂停上传
function pauseUpload() {
    pausedUpload = true;
    shouldStopUpload = false;
    if (currentXHR) {
        currentXHR.abort();
    }
    toggleUploadButtons(false, false, true);
}

// 继续上传
function resumeUpload() {
    pausedUpload = false;
    toggleUploadButtons(false, true, false);
    uploadChunk(uploadedChunks);
}

// 上传成功处理
// 上传成功处理
function handleUploadSuccess(message) {
    updateProgressText('上传完成!');
    showToast(message || '文件上传成功', 'success');
    
    // 重置表单
    resetUploadForm();
    
    // 重新加载文件列表 - 使用当前标签
    setTimeout(() => {
        document.getElementById('progressContainer').style.display = 'none';
        loadFileList(document.querySelector('input[name="tag"]').value);
    }, 1500);
    
    handleUploadCompletion();
}

// 重置上传表单
function resetUploadForm() {
    document.getElementById('uploadForm').reset();
    document.getElementById('file-name').textContent = '未选择文件';
    document.getElementById('file-size').textContent = '';
    document.getElementById('progressContainer').style.display = 'none';
    toggleUploadButtons(true, false, false);
    handleUploadCompletion();
}

// 处理上传完成或停止后的状态
function handleUploadCompletion() {
    isUploading = false;
    pausedUpload = false;
    shouldStopUpload = false;
    currentXHR = null;
}

// 切换上传按钮显示状态
function toggleUploadButtons(uploadVisible, stopVisible, resumeVisible) {
    document.getElementById('uploadBtn').style.display = uploadVisible ? 'inline-block' : 'none';
    document.getElementById('stopBtn').style.display = stopVisible ? 'inline-block' : 'none';
    document.getElementById('resumeBtn').style.display = resumeVisible ? 'inline-block' : 'none';
    document.getElementById('newUploadBtn').style.display = resumeVisible ? 'inline-block' : 'none';
}

// 显示进度条
function showProgressBar(progress, text) {
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    progressContainer.style.display = 'block';
    progressBar.style.width = progress;
    progressBar.textContent = progress;
    progressText.textContent = text;
}

// 更新进度条
function updateProgressBar(progress, loaded, total) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    progressBar.style.width = progress + '%';
    progressBar.textContent = progress + '%';
    progressText.textContent = `上传中: ${progress}% (${formatBytes(loaded)} / ${formatBytes(total)})`;
}

// 更新进度文本
function updateProgressText(text) {
    document.getElementById('progressText').textContent = text;
}

// 显示刷新警告
function showRefreshWarning() {
    const modal = document.getElementById('refreshConfirmModal');
    modal.style.display = 'flex';
    
    // 关闭模态框的按钮
    const closeButtons = document.querySelectorAll('#refreshConfirmModal .modal-close');
    closeButtons.forEach(button => {
        button.onclick = function() {
            modal.style.display = 'none';
        };
    });
    
    // 确认刷新按钮
    document.getElementById('confirmRefreshBtn').onclick = function() {
        shouldStopUpload = true;
        if (currentXHR) {
            currentXHR.abort();
        }
        window.location.reload();
    };
    
    // 点击模态框外部关闭
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}

// 格式化文件大小
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
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

// 加载文件列表


</script>
<div class="file-list">
    <h2><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($currentTag); ?> 文件列表</h2>
    <div id="fileTableContainer">
        <!-- 文件列表将通过AJAX动态加载到这里 -->
    </div>
</div>

<script>
// 初始加载文件列表
document.addEventListener('DOMContentLoaded', function() {
    loadFileList('<?php echo $currentTag; ?>', <?php echo isset($_GET['page']) ? $_GET['page'] : 1; ?>);
});


function loadFileList(tag, page = 1) {
    const searchKeyword = document.getElementById('searchInput').value.trim();
    
    if (searchKeyword) {
        performSearch(searchKeyword);
        return;
    }
    
    // 原有的加载逻辑...
    const decodedTag = decodeURIComponent(tag);
    fetch(`get_files.php?tag=${encodeURIComponent(tag)}&page=${page}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('fileTableContainer').innerHTML = html;
            history.pushState({}, '', `index.php?tag=${encodeURIComponent(tag)}&page=${page}`);
            updateActiveTab(decodedTag);
            updateTitle(decodedTag);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('加载文件列表失败', 'error');
        });
}

// 新增：更新活动标签样式
function updateActiveTab(tag) {
    document.querySelectorAll('.tag').forEach(tab => {
        if (tab.textContent.trim() === tag) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
}

// 新增：更新标题
function updateTitle(tag) {
    const titleElement = document.querySelector('.file-list h2');
    if (titleElement) {
        // 直接使用已解码的标签名
        titleElement.innerHTML = `<i class="fas fa-folder-open"></i> ${tag} 文件列表`;
    }
}

// 修改标签点击事件
document.querySelectorAll('.tag').forEach(tagElement => {
    tagElement.addEventListener('click', function(e) {
        e.preventDefault();
        const tag = this.textContent.trim();
        loadFileList(tag);
    });
});

// 初始加载时设置正确标签
document.addEventListener('DOMContentLoaded', function() {
    const initialTag = '<?php echo $currentTag; ?>';
    const initialPage = <?php echo isset($_GET['page']) ? $_GET['page'] : 1; ?>;
    
    // 设置初始活动标签
    updateActiveTab(initialTag);
    
    // 加载文件列表
    loadFileList(initialTag, initialPage);
});
// 标签点击事件处理
document.querySelectorAll('.tag').forEach(tagElement => {
    tagElement.addEventListener('click', function(e) {
        e.preventDefault();
        const tag = this.textContent.trim();
        loadFileList(tag);
    });
});
</script>

    <script>
        // 显示选择的文件名
        document.getElementById('file').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : '未选择文件';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // 添加动画效果
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.tag, .file-list, .upload-section, .admin-panel');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = 1;
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // 如果有消息，显示提示
            <?php if ($message): ?>
                showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
            <?php endif; ?>
        });
        
        // 显示弹窗函数
        function showToast(message, type) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            // 根据类型设置图标
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
        
        // 显示删除确认模态框
     // 显示删除确认模态框
// 显示删除确认模态框
function showDeleteConfirm(filename, tag) {
    const modal = document.getElementById('confirmModal');
    const message = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    message.textContent = `您确定要删除文件 "${filename}" 吗？此操作无法撤销。`;
    
    confirmBtn.onclick = function() {
        // 直接跳转到删除链接，删除后会重定向回当前页面
        window.location.href = `delete.php?file=${encodeURIComponent(filename)}&tag=${tag}`;
    };
    
    // 显示模态框
    modal.style.display = 'flex';
    
    // 关闭模态框的按钮
    const closeButtons = document.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.onclick = function() {
            modal.style.display = 'none';
        };
    });
    
    // 点击模态框外部关闭
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}
// 添加文件预览功能
function previewFile(filename, tag) {
    const modal = document.getElementById('previewModal');
    const previewTitle = document.getElementById('previewTitle');
    const previewContainer = document.getElementById('previewContainer');
    const closeBtn = document.getElementById('previewClose');
    
    // 显示模态框
    modal.style.display = 'block';
    previewTitle.textContent = filename;
    previewContainer.innerHTML = '<div class="preview-loading">正在加载文件预览...</div>';
    
    // 关闭按钮事件
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    };
    
    // 点击模态框外部关闭
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
    
    // 获取文件URL
    const fileUrl = `uploads/${tag}/${filename}`;
    
    // 根据文件类型进行预览
    const extension = filename.split('.').pop().toLowerCase();
    
    // 对于Word文档
    if (['docx'].includes(extension)) {
        fetch(fileUrl)
            .then(response => response.blob())
            .then(blob => {
                previewContainer.innerHTML = '';
                docx.renderAsync(blob, previewContainer)
                    .catch(err => {
                        previewContainer.innerHTML = `<p class="preview-error">无法预览Word文档: ${err.message}</p>`;
                    });
            })
            .catch(err => {
                previewContainer.innerHTML = `<p class="preview-error">无法加载文件: ${err.message}</p>`;
            });
    }
    // 对于Excel文件
    else if (['xlsx', 'xls', 'csv'].includes(extension)) {
        fetch(fileUrl)
            .then(response => response.arrayBuffer())
            .then(arrayBuffer => {
                const data = new Uint8Array(arrayBuffer);
                const workbook = XLSX.read(data, { type: 'array' });
                
                // 获取第一个工作表
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                
                // 转换为HTML表格
                const html = XLSX.utils.sheet_to_html(worksheet);
                previewContainer.innerHTML = html;
            })
            .catch(err => {
                previewContainer.innerHTML = `<p class="preview-error">无法预览Excel文件: ${err.message}</p>`;
            });
    }
    // 对于PDF文件
    else if (['pdf'].includes(extension)) {
        pdfjsLib.getDocument(fileUrl).promise
            .then(pdf => {
                // 获取第一页
                return pdf.getPage(1);
            })
            .then(page => {
                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                previewContainer.innerHTML = '';
                previewContainer.appendChild(canvas);
                
                return page.render({
                    canvasContext: context,
                    viewport: viewport
                }).promise;
            })
            .catch(err => {
                previewContainer.innerHTML = `<p class="preview-error">无法预览PDF文件: ${err.message}</p>`;
            });
    }
    // 对于文本文件
    else if (['txt', 'log', 'md', 'json', 'xml', 'html', 'css', 'js', 'php'].includes(extension)) {
        fetch(fileUrl)
            .then(response => response.text())
            .then(text => {
                const pre = document.createElement('pre');
                pre.style.whiteSpace = 'pre-wrap';
                pre.style.wordWrap = 'break-word';
                pre.textContent = text;
                previewContainer.innerHTML = '';
                previewContainer.appendChild(pre);
            })
            .catch(err => {
                previewContainer.innerHTML = `<p class="preview-error">无法预览文本文件: ${err.message}</p>`;
            });
    }
    // 对于图片文件
    else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
        const img = document.createElement('img');
        img.src = fileUrl;
        img.style.maxWidth = '100%';
        img.style.maxHeight = '80vh';
        img.onload = () => {
            previewContainer.innerHTML = '';
            previewContainer.appendChild(img);
        };
        img.onerror = () => {
            previewContainer.innerHTML = `<p class="preview-error">无法加载图片</p>`;
        };
    }
    // 不支持的文件类型
    else {
        previewContainer.innerHTML = `
            <p class="preview-error">不支持在线预览此文件类型</p>
            <p>请下载后查看: <a href="${fileUrl}" download>点击下载</a></p>
        `;
    }
}
// 搜索功能
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const searchInput = document.getElementById('searchInput');
    const keyword = searchInput.value.trim();
    
    if (keyword) {
        performSearch(keyword);
    }
});

// 取消搜索
document.getElementById('cancelSearch')?.addEventListener('click', function() {
    cancelSearch();
});

// 执行搜索
function performSearch(keyword) {
    fetch(`search.php?search=${encodeURIComponent(keyword)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('fileTableContainer').innerHTML = html;
            // 更新URL但不刷新页面
            const url = new URL(window.location);
            url.searchParams.set('search', keyword);
            history.pushState({}, '', url);
            
            // 显示取消按钮
            updateSearchUI(true);
        })
        .catch(error => {
            console.error('搜索错误:', error);
            showToast('搜索失败', 'error');
        });
}

// 取消搜索
function cancelSearch() {
    // 重新加载当前标签的文件列表
    const currentTag = '<?php echo $currentTag; ?>';
    loadFileList(currentTag, 1);
    
    // 清除搜索输入框
    document.getElementById('searchInput').value = '';
    
    // 更新URL
    const url = new URL(window.location);
    url.searchParams.delete('search');
    history.pushState({}, '', url);
    
    // 隐藏取消按钮
    updateSearchUI(false);
}

// 更新搜索UI
function updateSearchUI(isSearching) {
    const cancelBtn = document.getElementById('cancelSearch');
    const searchBtn = document.querySelector('.search-btn');
    
    if (isSearching) {
        if (!cancelBtn) {
            // 创建取消按钮
            const cancelButton = document.createElement('button');
            cancelButton.id = 'cancelSearch';
            cancelButton.className = 'cancel-search-btn';
            cancelButton.title = '取消搜索';
            cancelButton.innerHTML = '<i>取消搜索</i>';
            cancelButton.onclick = cancelSearch;
            
            document.querySelector('.search-input-wrapper').appendChild(cancelButton);
        }
        searchBtn.style.right = '60px';
    } else {
        if (cancelBtn) {
            cancelBtn.remove();
        }
        searchBtn.style.right = '10px';
    }
}

// 处理浏览器前进后退
window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchKeyword = urlParams.get('search');
    const tag = urlParams.get('tag') || '<?php echo $currentTag; ?>';
    
    if (searchKeyword) {
        performSearch(searchKeyword);
    } else {
        loadFileList(tag, 1);
        document.getElementById('searchInput').value = '';
        updateSearchUI(false);
    }
});
    </script>

<style>
        /* 添加置顶状态样式 */
        .pinned-badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #ff9800;
            color: white;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
           .fa-trash::before {
    content: "";
    display: inline-block;
    background-image: url('tp/sc.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}

.fa-trash {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}

   .fa-thumbtack::before {
    content: "";
    display: inline-block;
    background-image: url('tp/zd.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}

.fa-thumbtack {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}
    </style>
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
        // 文档
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'txt' => 'fas fa-file-alt',
        'rtf' => 'fas fa-file-alt',
        'odt' => 'fas fa-file-alt',
        
        // 表格
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'csv' => 'fas fa-file-csv',
        'ods' => 'fas fa-file-excel',
        
        // 演示文稿
        'ppt' => 'fas fa-file-powerpoint',
        'pptx' => 'fas fa-file-powerpoint',
        'odp' => 'fas fa-file-powerpoint',
        
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
        'xmind' => 'fas fa-file',
    ];
    
    return $iconMap[$extension] ?? 'fas fa-file';
}
?>
