<?php
session_start();

// 简单认证 - 在实际应用中应该使用更安全的认证方式
$validUsername = 'admin';
$validPassword = 'admin123'; // 在实际应用中应该使用密码哈希

// 检查是否已登录
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: admin.php');
    exit;
}

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $validUsername && $password === $validPassword) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #6a5acd;
            --secondary-color: #9370db;
            --dark-color: #483d8b;
            --light-color: #e6e6fa;
            --text-color: #f8f8ff;
            --text-dark: #333;
            --success-color: #4CAF50;
            --error-color: #f44336;
            --transition-speed: 0.3s;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #483d8b, #6a5acd);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.5s ease-out;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--light-color);
        }
        
        .login-header i {
            font-size: 50px;
            color: var(--light-color);
            margin-bottom: 15px;
            display: block;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            color: var(--light-color);
            font-weight: 500;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: var(--text-color);
            transition: all var(--transition-speed);
            font-size: 16px;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(106, 90, 205, 0.3);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed);
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background-color: var(--dark-color);
            transform: translateY(-2px);
        }
        
        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--light-color);
            text-decoration: none;
            transition: all var(--transition-speed);
        }
        
        .back-link:hover {
            color: white;
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-lock"></i>
            <h1>管理员登录</h1>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="post">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> 用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> 密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> 登录
            </button>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> 返回首页
            </a>
        </form>
    </div>
</body>
</html>