<?php
// 设置JSON头
header('Content-Type: application/json');

// 允许跨域访问（如果需要）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 获取磁盘信息
function getDiskInfo() {
    try {
        // 获取磁盘总空间和可用空间
        $diskTotal = disk_total_space(__DIR__);
        $diskFree = disk_free_space(__DIR__);
        
        if ($diskTotal === false || $diskFree === false) {
            throw new Exception("无法获取磁盘空间信息");
        }
        
        $diskUsed = $diskTotal - $diskFree;
        $usagePercent = round(($diskUsed / $diskTotal) * 100, 2);
        
        return [
            'success' => true,
            'disk' => [
                'total' => $diskTotal,
                'used' => $diskUsed,
                'free' => $diskFree,
                'usage_percent' => $usagePercent,
                'total_formatted' => formatBytes($diskTotal),
                'used_formatted' => formatBytes($diskUsed),
                'free_formatted' => formatBytes($diskFree)
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// 获取文件数量
function getFileCount() {
    try {
        $totalFiles = 0;
        $totalSize = 0;
        $folders = ['文档', '图片', '视频', '音乐', '其他'];
        
        foreach ($folders as $folder) {
            $dirPath = 'uploads/' . $folder . '/';
            
            if (file_exists($dirPath) && is_dir($dirPath)) {
                $files = array_diff(scandir($dirPath), ['.', '..']);
                $totalFiles += count($files);
                
                // 计算文件夹总大小
                foreach ($files as $file) {
                    $filePath = $dirPath . $file;
                    if (is_file($filePath)) {
                        $totalSize += filesize($filePath);
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'files' => [
                'count' => $totalFiles,
                'total_size' => $totalSize,
                'total_size_formatted' => formatBytes($totalSize),
                'folders' => count($folders)
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// 格式化字节大小
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    if ($bytes == 0) return '0 B';
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 获取所有信息
function getAllInfo() {
    $diskInfo = getDiskInfo();
    $fileInfo = getFileCount();
    
    if ($diskInfo['success'] && $fileInfo['success']) {
        return [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'disk' => $diskInfo['disk'],
            'files' => $fileInfo['files'],
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ]
        ];
    } else {
        return [
            'success' => false,
            'errors' => [
                'disk' => $diskInfo['error'] ?? null,
                'files' => $fileInfo['error'] ?? null
            ]
        ];
    }
}

// 根据请求参数返回不同的数据
$action = $_GET['action'] ?? 'all';

switch ($action) {
    case 'disk':
        echo json_encode(getDiskInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    case 'files':
        echo json_encode(getFileCount(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    case 'all':
    default:
        echo json_encode(getAllInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
}
?>