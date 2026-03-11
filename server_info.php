<?php
// 获取磁盘使用情况
function getDiskUsage() {
    // 获取磁盘总空间和可用空间
    $diskTotal = disk_total_space(__DIR__);
    $diskFree = disk_free_space(__DIR__);
    $diskUsed = $diskTotal - $diskFree;
    
    // 计算磁盘使用百分比
    if ($diskTotal > 0) {
        $usagePercent = round(($diskUsed / $diskTotal) * 100, 1);
        return [
            'usagePercent' => $usagePercent,
            'total' => $diskTotal,
            'used' => $diskUsed,
            'free' => $diskFree
        ];
    }
    
    return null;
}

// 获取总文件数
function getTotalFiles() {
    $total = 0;
    $tags = ['文档', '图片', '视频', '音乐', '其他'];
    
    foreach ($tags as $tag) {
        $dir = 'uploads/' . $tag . '/';
        if (file_exists($dir)) {
            $files = scandir($dir);
            $files = array_diff($files, array('.', '..'));
            $total += count($files);
        }
    }
    
    return $total;
}

// 返回JSON数据
header('Content-Type: application/json');

$diskInfo = getDiskUsage();
$totalFiles = getTotalFiles();

if ($diskInfo) {
    echo json_encode([
        'success' => true,
        'diskUsage' => $diskInfo['usagePercent'],
        'totalFiles' => $totalFiles,
        'diskTotal' => $diskInfo['total'],
        'diskUsed' => $diskInfo['used'],
        'diskFree' => $diskInfo['free']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => '无法获取磁盘信息'
    ]);
}
?>