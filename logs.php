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
        header("Location: logs.php?message=".urlencode("日志已清空")."&messageType=success");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
        header("Location: logs.php?message=".urlencode("清空日志失败: ".$error)."&messageType=error");
        exit;
    }
}

// 显示消息
$message = $_GET['message'] ?? '';
$messageType = $_GET['messageType'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志监控</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --warning-color: #ff9800;
            --info-color: #2196F3;
            --transition-speed: 0.3s;
            --bg-light: rgba(242, 242, 245, 0.99);
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(106, 90, 205, 0.1);
            padding: 25px;
            border: 1px solid rgba(106, 90, 205, 0.1);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .logs-table th, .logs-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logs-table th {
            background-color: rgba(106, 90, 205, 0.05);
            color: var(--primary-color);
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        .logs-table tr:hover {
            background-color: rgba(106, 90, 205, 0.03);
        }

        .log-time {
            white-space: nowrap;
        }

        .log-ip {
            font-family: monospace;
        }

        .log-username {
            font-weight: 500;
        }

        .log-action {
            word-break: break-word;
        }

        .action-upload {
            color: var(--success-color);
        }

        .action-download {
            color: var(--info-color);
        }

        .action-delete {
            color: var(--error-color);
        }

        .action-other {
            color: var(--warning-color);
        }

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
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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

        /* 分页样式 */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
            border: 1px solid #ddd;
            transition: all var(--transition-speed);
        }

        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .disabled {
            color: #aaa;
            pointer-events: none;
            cursor: default;
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

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .logs-table {
                display: block;
                overflow-x: auto;
            }
            
            .logs-table th, .logs-table td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
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
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回文件管理
            </a>
            <form method="post" onsubmit="return confirm('确定要清空所有日志吗？此操作不可恢复！');">
                <button type="submit" name="clear_logs" class="btn btn-danger">
                    <i class="fas fa-trash"></i> 清空日志
                </button>
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
                <a href="logs.php?page=1" title="第一页"><i class="fas fa-angle-double-left"></i></a>
                <a href="logs.php?page=<?php echo $currentPage - 1; ?>" title="上一页"><i class="fas fa-angle-left"></i></a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                <span class="disabled"><i class="fas fa-angle-left"></i></span>
            <?php endif; ?>
            
            <?php
            // 显示页码范围
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            if ($startPage > 1) {
                echo '<a href="logs.php?page=1">1</a>';
                if ($startPage > 2) {
                    echo '<span>...</span>';
                }
            }
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $currentPage) {
                    echo '<span class="current">'.$i.'</span>';
                } else {
                    echo '<a href="logs.php?page='.$i.'">'.$i.'</a>';
                }
            }
            
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<span>...</span>';
                }
                echo '<a href="logs.php?page='.$totalPages.'">'.$totalPages.'</a>';
            }
            ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="logs.php?page=<?php echo $currentPage + 1; ?>" title="下一页"><i class="fas fa-angle-right"></i></a>
                <a href="logs.php?page=<?php echo $totalPages; ?>" title="最后一页"><i class="fas fa-angle-double-right"></i></a>
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