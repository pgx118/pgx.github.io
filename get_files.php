<?php
session_start();

// 获取请求参数
$tag = isset($_GET['tag']) ? $_GET['tag'] : '文档';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// 检查标签是否合法
$tagsFile = 'tags.json';
if (file_exists($tagsFile)) {
    $allowedTags = json_decode(file_get_contents($tagsFile), true);
    if (!in_array($tag, $allowedTags)) {
        $tag = $allowedTags[0];
    }
} else {
    $tag = '文档';
}

// 获取文件列表
$uploadDir = 'uploads/' . $tag . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$files = scandir($uploadDir);
$files = array_diff($files, array('.', '..'));

// 分页设置
$filesPerPage = 7;
$totalFiles = count($files);
$totalPages = ceil($totalFiles / $filesPerPage);
$offset = ($page - 1) * $filesPerPage;

// 获取置顶文件
$pinnedFiles = json_decode(file_get_contents('pinned_files.json'), true) ?: [];

// 创建文件信息数组
$filesWithInfo = [];
foreach ($files as $file) {
    $filePath = $uploadDir . $file;
    $filesWithInfo[$file] = [
        'size' => filesize($filePath),
        'mod_time' => filemtime($filePath),
        'is_pinned' => isset($pinnedFiles[$file]) && $pinnedFiles[$file]['tag'] === $tag,
        'pinned_time' => $pinnedFiles[$file]['pinned_time'] ?? 0
    ];
}

// 排序 - 置顶文件优先，然后按修改时间
uasort($filesWithInfo, function($a, $b) {
    if ($a['is_pinned'] && $b['is_pinned']) {
        return $b['pinned_time'] <=> $a['pinned_time'];
    } elseif ($a['is_pinned']) {
        return -1;
    } elseif ($b['is_pinned']) {
        return 1;
    } else {
        return $b['mod_time'] <=> $a['mod_time'];
    }
});

// 获取当前页文件
$paginatedFiles = array_slice(array_keys($filesWithInfo), $offset, $filesPerPage);

// 输出文件列表HTML
if (empty($paginatedFiles)) {
    echo '<p class="no-files">暂无文件</p>';
} else {
    echo '<table id="filesTable">
        <thead>
            <tr>
                <th>文件名</th>
                <th>大小</th>
                <th>修改日期</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($paginatedFiles as $file) {
        $fileInfo = $filesWithInfo[$file];
        $fileDate = date("Y-m-d H:i:s", $fileInfo['mod_time']);
        $fileIcon = getFileIcon($file);
        
        echo '<tr>
            <td>
                <i class="'.$fileIcon.'"></i> 
                <a href="javascript:void(0);" onclick="previewFile(\''.htmlspecialchars($file).'\', \''.$tag.'\')">
                    '.htmlspecialchars($file).'
                </a>
            </td>
            <td>'.formatFileSize($fileInfo['size']).'</td>
            <td>'.$fileDate.'</td>
            <td>'.($fileInfo['is_pinned'] ? '<span class="pinned-badge">置顶</span>' : '').'</td>
            <td class="actions">
                <a href="download.php?file='.urlencode($file).'&tag='.urlencode($tag).'" class="download-btn" title="下载">
                    <i class="fas fa-download"></i>
                </a>';
        
        if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
            echo '<a href="#" class="delete-btn" title="删除" onclick="showDeleteConfirm(\''.htmlspecialchars($file).'\', \''.$tag.'\')">
                    <i class="fas fa-trash"></i>
                </a>';
        }
        
        echo '</td>
        </tr>';
    }
    
    echo '</tbody></table>';
    
    // 分页导航
    if ($totalPages > 1) {
        echo '<div class="pagination">';
        
        // 上一页
        if ($page > 1) {
            echo '<a href="javascript:void(0);" onclick="loadFileList(\''.$tag.'\', '.($page - 1).')" class="page-link">
                    《 上一页
                </a>';
        }
        
        // 页码
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $startPage + 4);
        $startPage = max(1, $endPage - 4);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            echo '<a href="javascript:void(0);" onclick="loadFileList(\''.$tag.'\', '.$i.')" class="page-link '.($i == $page ? 'active' : '').'">
                    '.$i.'
                </a>';
        }
        
        // 下一页
        if ($page < $totalPages) {
            echo '<a href="javascript:void(0);" onclick="loadFileList(\''.$tag.'\', '.($page + 1).')" class="page-link">
                    下一页 》</i>
                </a>';
        }
        
        echo '</div>';
    }
}

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