<?php
// convert.php - 安全可靠的文档转换器
header('Content-Type: text/plain; charset=utf-8');

// 1. 接收并验证参数
$file = $_GET['file'] ?? '';
$tag = $_GET['tag'] ?? '';
$type = 'pdf'; // 固定输出PDF格式

if (empty($file) || empty($tag)) {
    http_response_code(400);
    exit("ERROR: 缺少文件参数");
}

// 2. 安全过滤路径参数
$baseDir = realpath(__DIR__ . '/uploads') . '/';
$filePath = realpath($baseDir . $tag . '/' . $file);

// 3. 严格路径验证
if ($filePath === false || 
    !file_exists($filePath) || 
    strpos($filePath, $baseDir) !== 0) {
    http_response_code(404);
    exit("ERROR: 文件路径无效或不存在");
}

// 4. 允许转换的文件类型
$allowedTypes = [
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
];

$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!array_key_exists($fileExt, $allowedTypes)) {
    http_response_code(415);
    exit("ERROR: 不支持此文件类型转换");
}

// 5. 准备临时文件
$outputDir = sys_get_temp_dir();
$outputFile = $outputDir . '/preview_' . md5($filePath) . '.pdf';

// 6. 执行转换 (使用LibreOffice)
$command = sprintf(
    'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
    escapeshellarg($outputDir),
    escapeshellarg($filePath)
);

exec($command, $output, $returnCode);

// 7. 验证转换结果
if ($returnCode !== 0 || !file_exists($outputFile)) {
    http_response_code(500);
    error_log("转换失败: " . implode("\n", $output));
    exit("ERROR: 文件转换失败");
}

// 8. 输出PDF文件
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($file, '.'.$fileExt) . '.pdf"');
readfile($outputFile);

// 9. 清理临时文件
register_shutdown_function(function() use ($outputFile) {
    if (file_exists($outputFile)) {
        unlink($outputFile);
    }
});
?>