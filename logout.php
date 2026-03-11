<?php
// 启动会话
session_start();

// 记录登出日志（在实际应用中应该记录到文件或数据库）
function logLogout($username) {
    $logMessage = sprintf(
        "[%s] 用户 %s 已登出，IP: %s\n",
        date('Y-m-d H:i:s'),
        $username,
        $_SERVER['REMOTE_ADDR'] ?? '未知IP'
    );
    
    // 在实际应用中，应该写入日志文件或数据库
    // file_put_contents('admin_logs.log', $logMessage, FILE_APPEND);
}

// 检查是否已登录
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    $username = $_SESSION['admin_username'] ?? '管理员';
    
    // 记录登出日志
    logLogout($username);
    
    // 清除特定会话变量
    unset($_SESSION['admin']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['last_activity']);
    
    // 如果要彻底删除会话，同时删除会话 cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // 销毁会话
    session_destroy();
    
    // 设置登出成功消息
    $_SESSION['logout_message'] = '您已成功登出';
    
    // 重定向到登录页面
    header("Location: index.php");
    exit;
}

// 如果未登录直接访问logout.php，重定向到首页
header("Location: index.php");
exit;
?>