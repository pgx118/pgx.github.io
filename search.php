<?php
session_start();

// 获取搜索关键字
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($searchKeyword)) {
    echo '<p class="no-files">请输入搜索关键字</p>';
    exit;
}

// 获取所有标签
$tagsFile = 'tags.json';
if (file_exists($tagsFile)) {
    $allowedTags = json_decode(file_get_contents($tagsFile), true);
} else {
    $allowedTags = ['文档', '图片', '视频', '音乐', '其他'];
}

// 搜索所有文件夹
$searchResults = [];
foreach ($allowedTags as $tag) {
    $uploadDir = 'uploads/' . $tag . '/';
    if (!file_exists($uploadDir)) {
        continue;
    }
    
    $files = scandir($uploadDir);
    $files = array_diff($files, array('.', '..'));
    
    foreach ($files as $file) {
        // 模糊匹配文件名
        if (stripos($file, $searchKeyword) !== false) {
            $filePath = $uploadDir . $file;
            $searchResults[] = [
                'name' => $file,
                'path' => $filePath,
                'tag' => $tag,
                'size' => filesize($filePath),
                'mod_time' => filemtime($filePath),
                'url' => 'uploads/' . $tag . '/' . urlencode($file)
            ];
        }
    }
}

// 按修改时间排序
usort($searchResults, function($a, $b) {
    return $b['mod_time'] <=> $a['mod_time'];
});

// 输出搜索结果
if (empty($searchResults)) {
    echo '<p class="no-files">未找到匹配的文件</p>';
} else {
    echo '<div class="search-results-info">找到 ' . count($searchResults) . ' 个匹配文件</div>';
    echo '<table id="filesTable">
        <thead>
            <tr>
                <th>文件名</th>
                <th>所在文件夹</th>
                <th>大小</th>
                <th>修改日期</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($searchResults as $fileInfo) {
        $fileDate = date("Y-m-d H:i:s", $fileInfo['mod_time']);
        $fileIcon = getFileIcon($fileInfo['name']);
        
        echo '<tr>
            <td>
                <i class="'.$fileIcon.'"></i> 
                <a href="javascript:void(0);" onclick="previewFile(\''.htmlspecialchars($fileInfo['name']).'\', \''.$fileInfo['tag'].'\')">
                    '.htmlspecialchars($fileInfo['name']).'
                </a>
            </td>
            <td>'.htmlspecialchars($fileInfo['tag']).'</td>
            <td>'.formatFileSize($fileInfo['size']).'</td>
            <td>'.$fileDate.'</td>
            <td class="actions">
                <a href="'.$fileInfo['url'].'" download class="download-btn" title="下载">
                    <i class="fas fa-download"></i>
                </a>';
        
        if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
            echo '<a href="#" class="delete-btn" title="删除" onclick="showDeleteConfirm(\''.htmlspecialchars($fileInfo['name']).'\', \''.$fileInfo['tag'].'\')">
                    <i class="fas fa-trash"></i>
                </a>';
        }
        
        echo '</td>
        </tr>';
    }
    
    echo '</tbody></table>';
}

// 辅助函数
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' Bytes';
    }
}

function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $iconMap = [
        'pdf' => 'fas fa-file-pdf', 'doc' => 'fas fa-file-word', 'docx' => 'fas fa-file-word',
        'txt' => 'fas fa-file-alt', 'xls' => 'fas fa-file-excel', 'xlsx' => 'fas fa-file-excel',
        'csv' => 'fas fa-file-csv', 'ppt' => 'fas fa-file-powerpoint', 'pptx' => 'fas fa-file-powerpoint',
        'jpg' => 'fas fa-file-image', 'jpeg' => 'fas fa-file-image', 'png' => 'fas fa-file-image',
        'gif' => 'fas fa-file-image', 'mp3' => 'fas fa-file-audio', 'mp4' => 'fas fa-file-video',
        'zip' => 'fas fa-file-archive', 'rar' => 'fas fa-file-archive'
    ];
    return $iconMap[$extension] ?? 'fas fa-file';
}
?>