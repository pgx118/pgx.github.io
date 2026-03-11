<?php
// 启动会话
session_start();

// 检查管理员登录状态
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

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

// 处理标签管理操作
$message = '';
$messageType = '';
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add_tag' && !empty($_POST['new_tag'])) {
        $newTag = trim($_POST['new_tag']);
        if (!in_array($newTag, $allowedTags)) {
            $allowedTags[] = $newTag;
            file_put_contents($tagsFile, json_encode($allowedTags));
            $message = "标签 '{$newTag}' 添加成功";
            $messageType = 'success';
        } else {
            $message = "标签 '{$newTag}' 已存在";
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'delete_tag' && !empty($_POST['tag_to_delete'])) {
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
                
                // 如果目录为空，删除目录
                if (file_exists($tagDir) && count(array_diff(scandir($tagDir), ['.', '..'])) === 0) {
                    rmdir($tagDir);
                }
            }
        } else {
            $message = "标签 '{$tagToDelete}' 不存在";
            $messageType = 'error';
        }
    }
    
    // 重定向以避免表单重复提交
    header("Location: wjgl.php?message=" . urlencode($message) . "&messageType=" . urlencode($messageType));
    exit;
}

// 处理置顶/取消置顶操作
if (isset($_GET['pin_action'])) {
    $filename = $_GET['filename'] ?? '';
    $tag = $_GET['tag'] ?? '';
    
    if ($_GET['pin_action'] === 'pin' && $filename && $tag) {
        // 添加置顶
        $pinnedFiles[$filename] = [
            'tag' => $tag,
            'pinned_time' => time()
        ];
        file_put_contents($pinnedFile, json_encode($pinnedFiles));
        $message = "文件 '{$filename}' 已置顶";
        $messageType = 'success';
    } elseif ($_GET['pin_action'] === 'unpin' && $filename) {
        // 取消置顶
        if (isset($pinnedFiles[$filename])) {
            unset($pinnedFiles[$filename]);
            file_put_contents($pinnedFile, json_encode($pinnedFiles));
            $message = "文件 '{$filename}' 已取消置顶";
            $messageType = 'success';
        }
    }
    
    header("Location: wjgl.php?tag=" . urlencode($tag) . "&message=" . urlencode($message) . "&messageType=" . urlencode($messageType));
    exit;
}

// 获取当前标签（用于文件管理部分）
$currentTag = isset($_GET['tag']) && in_array($_GET['tag'], $allowedTags) ? $_GET['tag'] : (count($allowedTags) > 0 ? $allowedTags[0] : '');

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

// 显示消息（如果有）
$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['messageType']) ? $_GET['messageType'] : '';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理 - 桂工本地网盘</title>
<link rel="stylesheet" href="css/all.min.css">
    <link rel="icon" type="image/png" href="img/tb.jpg">
    <style>
        :root {
            --primary-color: #6a5acd;
            --secondary-color: #9370db;
            --dark-color: #483d8b;
            --light-color: #f8f8ff;
            --text-color: #333;
            --text-light: #f8f8ff;
            --success-color: #4CAF50;
            --error-color: #f44336;
            --transition-speed: 0.3s;
            --bg-light: rgba(242, 242, 245, 0.99);
            --card-bg: rgba(255, 255, 255, 0.95);
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







.fa-upload::before {
    content: "";
    display: inline-block;
    background-image: url('tp/sc1.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}
.fa-upload {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}





.fa-tags::before {
    content: "";
    display: inline-block;
    background-image: url('tp/bq.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}
.fa-tags {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}
.fa-cloud-upload-alt::before {
    content: "";
    display: inline-block;
    background-image: url('tp/tj.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}
.fa-cloud-upload-alt {
    font-family: inherit !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
}

.fa-arrow-left::before {
    content: "";
    display: inline-block;
    background-image: url('tp/fh.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;

    width: 100%;
    height: 100%;
}
.fa-arrow-left {
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
        }

        /* 标签样式 */
        .tag-container {
            display: flex;
            justify-content: center;
            margin: 30px 0;
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
        }

        .file-list:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
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

        .actions {
            display: flex;
            gap: 10px;
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
        .wjgl-panel {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all var(--transition-speed);
            animation: fadeIn 0.5s ease-in-out;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .wjgl-panel:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .wjgl-panel h2 {
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .wjgl-form {
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

        /* 返回按钮样式 */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all var(--transition-speed);
            z-index: 100;
            box-shadow: 0 2px 8px rgba(106, 90, 205, 0.3);
            text-decoration: none;
        }

        .back-btn:hover {
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
        }

        .progress-container {
            width: 100%;
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
        }

        /* 上传按钮容器 */
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

        /* 停止上传按钮 */
        .stop-btn {
            background-color: var(--error-color) !important;
        }

        .stop-btn:hover {
            background-color: #d32f2f !important;
        }

        /* 上传新文件按钮 */
        .new-upload-btn {
            background-color: var(--secondary-color) !important;
        }

        .new-upload-btn:hover {
            background-color: var(--dark-color) !important;
        }

        /* 取消上传按钮 */
        .cancel-btn {
            background-color: #ff9800 !important;
        }

        .cancel-btn:hover {
            background-color: #f57c00 !important;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
            flex-wrap: wrap;
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
        }
    </style>
</head>
<body>
    <div id="toast-container" class="toast-container"></div>
    
    <!-- 确认删除模态框 -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> 确认删除</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">您确定要删除这个文件吗？此操作无法撤销。</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary modal-close">取消</button>
                <button id="confirmDeleteBtn" class="modal-btn modal-btn-danger">确认删除</button>
            </div>
        </div>
    </div>
    
    <!-- 确认停止上传模态框 -->
    <div id="stopUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> 确认停止上传</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>您确定要停止当前的上传吗？已经上传的部分将不会被保存。</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary modal-close">取消</button>
                <button id="confirmStopBtn" class="modal-btn modal-btn-danger">确认停止</button>
            </div>
        </div>
    </div>
    
    <!-- 刷新确认模态框 -->
    <div id="refreshConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> 确认刷新</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>当前有文件正在上传，刷新页面将中断上传过程。您确定要刷新吗？</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary modal-close">取消</button>
                <button id="confirmRefreshBtn" class="modal-btn modal-btn-danger">确认刷新</button>
            </div>
        </div>
    </div>
    
    <a href="admin.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> 返回首页
    </a>
    
    <div class="school-container">
        <a href="https://103.38.81.5/dh/" style="text-decoration: none;">
            <div class="school-item">
                <img src="img/tb.jpg" alt="桂林理工大学" class="school-icon">
            </div>
        </a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
                });
            </script>
        <?php endif; ?>

        <!-- 标签管理面板 -->
        <div class="wjgl-panel">
            <h2><i class="fas fa-tags"></i> 标签管理</h2>
            <form method="post" class="wjgl-form">
                <div class="form-group">
                    <label for="new_tag">添加新标签</label>
                    <input type="text" id="new_tag" name="new_tag" class="form-control" placeholder="输入新标签名称" required>
                </div>
                <button type="submit" name="action" value="add_tag" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 添加标签
                </button>
            </form>
            
            <form method="post" class="wjgl-form" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="tag_to_delete">删除标签</label>
                    <select id="tag_to_delete" name="tag_to_delete" class="form-control" required>
                        <option value="">选择要删除的标签</option>
                        <?php foreach ($allowedTags as $tag): ?>
                            <option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="action" value="delete_tag" class="btn btn-danger">
                    <i class="fas fa-trash"></i> 删除标签
                </button>
            </form>
        </div>

        <!-- 文件上传面板 -->
        <div class="upload-section">
            <h2><i class="fas fa-cloud-upload-alt"></i> 上传文件到 <?php echo htmlspecialchars($currentTag); ?></h2>
            <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="tag" value="<?php echo htmlspecialchars($currentTag); ?>">
                <div class="file-input-wrapper">
                    <label for="file" class="file-label">
                        <i class="fas fa-cloud-upload-alt"></i> 选择文件
                        <input type="file" name="file" id="file" class="file-input" required>
                    </label>
                    <div class="file-info">
                        <span id="file-name">未选择文件</span>
                        <span id="file-size"></span>
                    </div>
                </div>
                
                <div id="progressContainer" class="progress-container">
                    <div id="progressBar" class="progress-bar">0%</div>
                    <div id="progressText" class="progress-text">准备上传...</div>
                </div>
                
                <div class="upload-buttons">
                    <button type="submit" class="upload-btn" id="uploadBtn">
                        <i class="fas fa-upload"></i> 上传
                    </button>
                    <button type="button" class="upload-btn stop-btn" id="stopBtn" style="display: none;">
                        <i class="fas fa-pause"></i> 停止上传
                    </button>
                    <button type="button" class="upload-btn resume-btn" id="resumeBtn" style="display: none;">
                        <i class="fas fa-play"></i> 继续上传
                    </button>
                    <button type="button" class="upload-btn cancel-btn" id="cancelBtn" style="display: none;">
                        <i class="fas fa-times"></i> 取消上传
                    </button>
                </div>
            </form>
        </div>

        <!-- 文件管理面板 -->
     <div class="file-list">
        <h2><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($currentTag); ?> 文件列表</h2>
            
            <!-- 标签选择器 -->
            <div class="tag-container">
            <?php foreach ($allowedTags as $tag): ?>
                <a href="wjgl.php?tag=<?php echo urlencode($tag); ?>" class="tag <?php echo $tag == $currentTag ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($tag); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($fileList)): ?>
            <p class="no-files">暂无文件</p>
        <?php else: ?>
            <table id="filesTable">
                <thead>
                    <tr>
                        <th>文件名</th>
                        <th>大小</th>
                        <th>修改日期</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fileList as $file): 
                        $fileIcon = getFileIcon($file['name']);
                        $fileDate = date("Y-m-d H:i:s", $file['mod_time']);
                    ?>
                        <tr>
                            <td><i class="<?php echo $fileIcon; ?>"></i> <?php echo htmlspecialchars($file['name']); ?></td>
                            <td><?php echo formatFileSize($file['size']); ?></td>
                            <td><?php echo $fileDate; ?></td>
                            <td><?php echo $file['is_pinned'] ? '<span class="pinned-badge">置顶</span>' : ''; ?></td>
                            <td class="actions">
                                <a href="download.php?file=<?php echo urlencode($file['name']); ?>&tag=<?php echo urlencode($currentTag); ?>" class="download-btn" title="下载">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if ($file['is_pinned']): ?>
                                    <a href="wjgl.php?pin_action=unpin&filename=<?php echo urlencode($file['name']); ?>&tag=<?php echo urlencode($currentTag); ?>" class="unpin-btn" title="取消置顶">
                                        <i class="fas fa-thumbtack"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="wjgl.php?pin_action=pin&filename=<?php echo urlencode($file['name']); ?>&tag=<?php echo urlencode($currentTag); ?>" class="pin-btn" title="置顶">
                                        <i class="fas fa-thumbtack"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="#" class="delete-btn" title="删除" onclick="showDeleteConfirm('<?php echo htmlspecialchars($file['name']); ?>', '<?php echo urlencode($currentTag); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
 <style>
        /* 添加置顶按钮和状态样式 */
        .pin-btn, .unpin-btn {
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
        
        .pin-btn {
            background-color: #ff9800;
        }
        
        .unpin-btn {
            background-color: #9e9e9e;
        }
        
        .pin-btn:hover, .unpin-btn:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
        
        .pinned-badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #ff9800;
            color: white;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
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

            // 停止上传按钮
            document.getElementById('stopBtn').addEventListener('click', function() {
                const modal = document.getElementById('stopUploadModal');
                modal.style.display = 'flex';
                
                // 点击模态框外部关闭
                window.onclick = function(event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                };
            });

            // 继续上传按钮
            document.getElementById('resumeBtn').addEventListener('click', resumeUpload);

            // 取消上传按钮
            document.getElementById('cancelBtn').addEventListener('click', resetUploadForm);

            // 确认停止上传按钮
            document.getElementById('confirmStopBtn').addEventListener('click', function() {
                shouldStopUpload = true;
                pausedUpload = true;
                if (currentXHR) {
                    currentXHR.abort();
                }
                document.getElementById('stopUploadModal').style.display = 'none';
                toggleUploadButtons(false, false, true); // 显示继续和取消按钮
            });

            // 关闭停止上传模态框的按钮
            document.querySelectorAll('#stopUploadModal .modal-close').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('stopUploadModal').style.display = 'none';
                });
            });

            // 页面刷新警告
            window.addEventListener('beforeunload', function(e) {
                if (isUploading) {
                    e.preventDefault();
                    showRefreshWarning();
                    return '当前有文件正在上传，刷新页面将中断上传过程。';
                }
            });

            // 如果有消息，显示提示
            <?php if ($message): ?>
                showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
            <?php endif; ?>
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
                            // 如果是暂停状态，显示继续和取消按钮
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

        // 继续上传
        function resumeUpload() {
            pausedUpload = false;
            shouldStopUpload = false;
            toggleUploadButtons(false, true, false);
            uploadChunk(uploadedChunks);
        }

        // 上传成功处理
        function handleUploadSuccess(message) {
            updateProgressText('上传完成!');
            showToast(message || '文件上传成功', 'success');
            
            // 重置表单
            resetUploadForm();
            
            // 重新加载页面以刷新文件列表
            setTimeout(() => {
                window.location.reload();
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
            document.getElementById('cancelBtn').style.display = resumeVisible ? 'inline-block' : 'none';
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

        // 显示删除确认模态框
        function showDeleteConfirm(filename, tag) {
            const modal = document.getElementById('confirmModal');
            const message = document.getElementById('confirmMessage');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            message.textContent = `您确定要删除文件 "${filename}" 吗？此操作无法撤销。`;
            
            confirmBtn.onclick = function() {
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
    </script>
</body>
</html>

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
    ];
    
    return $iconMap[$extension] ?? 'fas fa-file';
}
?>