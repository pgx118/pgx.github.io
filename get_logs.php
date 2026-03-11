<?php
session_start();
require_once 'admin_logger.php';

// 检查管理员权限
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die('无权访问');
}

$logger = new AdminLogger();
$logs = $logger->getLogs(200); // 获取最近的200条日志

if (empty($logs)) {
    echo '<tr><td colspan="4" style="text-align: center; padding: 20px;">暂无日志记录</td></tr>';
} else {
    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td class="log-time">' . htmlspecialchars($log['time']) . '</td>';
        echo '<td class="log-ip">' . htmlspecialchars($log['ip']) . '</td>';
        echo '<td class="log-username">' . htmlspecialchars($log['username']) . '</td>';
        echo '<td class="log-action ' . getActionClass($log['action']) . '">';
        echo '<i class="' . getActionIcon($log['action']) . '"></i> ';
        echo htmlspecialchars($log['action']);
        echo '</td>';
        echo '</tr>';
    }
}

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