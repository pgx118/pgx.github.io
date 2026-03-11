<?php
session_start();
header('Content-Type: application/json');
require_once 'admin_logger.php';
if (isset($_SESSION['admin_username'])) {
    $logger = new AdminLogger();
    $logger->logAction($_SESSION['admin_username'], "上传文件: {$tag}/{$fileName}");
}
// 验证参数
if (!isset($_POST['tag'])) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$tag = $_POST['tag'];

// 验证标签是否在允许的列表中
$allowedTags = json_decode(file_get_contents('tags.json'), true) ?? ['文档', '图片', '视频', '音乐', '其他'];
if (!in_array($tag, $allowedTags)) {
    echo json_encode(['success' => false, 'message' => '非法标签']);
    exit;
}

$uploadDir = 'uploads/' . $tag . '/';

// 创建目录如果不存在
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 验证文件上传
if (!isset($_FILES['file']) && !isset($_POST['chunk'])) {
    echo json_encode(['success' => false, 'message' => '没有文件上传']);
    exit;
}

// 获取文件名和路径
$fileName = isset($_POST['filename']) ? basename($_POST['filename']) : basename($_FILES['file']['name']);
$targetPath = $uploadDir . $fileName;

// 验证文件名合法性
if (preg_match('/\.\.|\/|\\\/', $fileName)) {
    echo json_encode(['success' => false, 'message' => '文件名不合法']);
    exit;
}

// 分片上传处理
if (isset($_POST['chunk'])) {
    $chunk = $_POST['chunk'];
    $chunks = $_POST['chunks'];
    $chunkNumber = $_POST['chunkNumber'];
    
    // 临时文件路径
    $tempDir = $uploadDir . 'temp/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $tempFilePath = $tempDir . $fileName . '.part' . $chunkNumber;
    
    // 移动分片文件
    if (move_uploaded_file($_FILES['file']['tmp_name'], $tempFilePath)) {
        // 检查是否所有分片都已上传
        $allPartsUploaded = true;
        for ($i = 0; $i < $chunks; $i++) {
            if (!file_exists($tempDir . $fileName . '.part' . $i)) {
                $allPartsUploaded = false;
                break;
            }
        }
        
        if ($allPartsUploaded) {
            // 合并所有分片
            if ($out = fopen($targetPath, 'wb')) {
                for ($i = 0; $i < $chunks; $i++) {
                    $partPath = $tempDir . $fileName . '.part' . $i;
                    if ($in = fopen($partPath, 'rb')) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                        fclose($in);
                        unlink($partPath); // 删除分片文件
                    } else {
                        fclose($out);
                        echo json_encode(['success' => false, 'message' => '无法读取分片文件']);
                        exit;
                    }
                }
                fclose($out);
                
                // 删除临时目录
                @rmdir($tempDir);
                
                echo json_encode([
                    'success' => true, 
                    'message' => '文件上传成功',
                    'progress' => 100
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '无法创建目标文件']);
            }
        } else {
            // 计算上传进度
            $progress = round(($chunkNumber + 1) / $chunks * 100);
            echo json_encode([
                'success' => true, 
                'message' => '分片上传成功',
                'progress' => $progress
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '分片上传失败']);
    }
} else {
    // 普通上传处理
    if (file_exists($targetPath)) {
        echo json_encode(['success' => false, 'message' => '文件已存在']);
        exit;
    }
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo json_encode(['success' => true, 'message' => '文件上传成功', 'progress' => 100]);
    } else {
        echo json_encode(['success' => false, 'message' => '文件上传失败']);
    }
}if ($uploadSuccess) {
    // 获取最新文件列表
    $tag = $_POST['tag'];
    $uploadDir = 'uploads/' . $tag . '/';
    $files = scandir($uploadDir);
    $files = array_diff($files, array('.', '..'));
    
    echo json_encode([
        'success' => true,
        'message' => '文件上传成功',
        'fileCount' => count($files), // 返回文件总数
        'html' => generateFileListHTML($tag, $files) // 返回生成的HTML
    ]);
    exit;
}

// 添加生成HTML的函数
function generateFileListHTML($tag, $files) {
    ob_start();
    // 这里包含你原来的文件列表生成逻辑
    include 'get_files.php'; // 或者直接复制get_files.php的内容
    return ob_get_clean();
}