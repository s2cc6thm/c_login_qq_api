<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $adminUser = $app->getSetting('admin_user', 'admin');
    $adminPass = $app->getSetting('admin_pass', '');
    
    if ($username === $adminUser && password_verify($password, $adminPass)) {
        $_SESSION['admin_id'] = 1;
        header('Location: index.php');
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
    <title>后台登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 30px;
            padding: 50px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.6s ease;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.4);
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating .form-control {
            height: 60px;
            border-radius: 16px;
            border: 2px solid rgba(0, 0, 0, 0.06);
            background: rgba(255, 255, 255, 0.8);
            padding: 20px 20px 10px 52px;
            font-size: 1rem;
        }
        
        .form-floating .form-control:focus {
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .form-floating label {
            padding-left: 52px;
            color: #6b7280;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
            z-index: 10;
        }
        
        .btn-login {
            width: 100%;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }
        
        .alert-glass {
            background: rgba(239, 68, 68, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1 class="login-title">管理后台</h1>
                <p class="login-subtitle">请登录以继续访问</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert-glass">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-floating position-relative">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="用户名" required autofocus>
                    <label for="username">用户名</label>
                </div>
                
                <div class="form-floating position-relative">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="密码" required>
                    <label for="password">密码</label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>登录
                </button>
            </form>
        </div>
    </div>
</body>
</html>
