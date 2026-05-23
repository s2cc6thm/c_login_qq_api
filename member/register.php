<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!empty($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    // 验证
    if (empty($username) || empty($email) || empty($password)) {
        $error = '请填写完整信息';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度应为3-20位';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } elseif (strlen($password) < 6) {
        $error = '密码长度不能少于6位';
    } elseif ($password !== $confirm) {
        $error = '两次输入的密码不一致';
    } else {
        // 检查用户名和邮箱是否存在
        $exists = $app->db->fetch(
            "SELECT id FROM {$app->db->table('members')} WHERE username=? OR email=?",
            [$username, $email]
        );
        
        if ($exists) {
            $error = '用户名或邮箱已被注册';
        } else {
            // 创建用户
            $app->db->insert('members', [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 1
            ]);
            
            $success = '注册成功，请登录';
        }
    }
}

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 40px 20px;
        }
        
        /* 背景装饰 */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float-shape 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: white;
            border-radius: 50%;
            top: -150px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            background: white;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            bottom: -100px;
            left: -50px;
            animation-delay: 5s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            top: 50%;
            left: 10%;
            animation-delay: 10s;
        }
        
        @keyframes float-shape {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        .register-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.5);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .register-header .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(17, 153, 142, 0.4);
        }
        
        .register-header h2 {
            color: #333;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .register-header p {
            color: #888;
            font-size: 14px;
        }
        
        .form-floating {
            margin-bottom: 18px;
        }
        
        .form-floating .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 14px;
            height: 58px;
            padding: 20px 15px 10px 50px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-floating .form-control:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.1);
        }
        
        .form-floating label {
            padding-left: 50px;
            color: #999;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            z-index: 10;
            transition: color 0.3s;
        }
        
        .form-floating .form-control:focus ~ .input-icon {
            color: #11998e;
        }
        
        .password-strength {
            height: 4px;
            background: #e8e8e8;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s;
        }
        
        .strength-weak { width: 33%; background: #dc3545; }
        .strength-medium { width: 66%; background: #ffc107; }
        .strength-strong { width: 100%; background: #28a745; }
        
        .btn-register {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(17, 153, 142, 0.4);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .register-footer a {
            color: #11998e;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .register-footer a:hover {
            color: #38ef7d;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #2a6;
        }
        
        .back-link {
            position: absolute;
            top: 30px;
            left: 30px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0.9;
            transition: opacity 0.3s;
            z-index: 10;
        }
        
        .back-link:hover {
            opacity: 1;
            color: white;
        }
        
        @media (max-width: 576px) {
            body {
                padding: 20px 15px;
            }
            
            .register-card {
                padding: 30px 20px;
            }
            
            .back-link {
                top: 15px;
                left: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- 背景装饰 -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <a href="../" class="back-link">
        <i class="bi bi-arrow-left"></i> 返回首页
    </a>
    
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="logo">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h2>创建账号</h2>
                <p>填写以下信息开始您的旅程</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <div>
                    <?php echo $success; ?>
                    <br><a href="login.php">点击登录</a>
                </div>
            </div>
            <?php else: ?>
            <form method="POST" action="" id="registerForm">
                <div class="form-floating position-relative">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" name="username" class="form-control" id="username" placeholder="用户名" required minlength="3" maxlength="20">
                    <label for="username">用户名 (3-20位)</label>
                </div>
                
                <div class="form-floating position-relative">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control" id="email" placeholder="邮箱" required>
                    <label for="email">邮箱地址</label>
                </div>
                
                <div class="form-floating position-relative">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" class="form-control" id="password" placeholder="密码" required minlength="6">
                    <label for="password">密码 (至少6位)</label>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                
                <div class="form-floating position-relative mt-3">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="password" name="confirm" class="form-control" id="confirm" placeholder="确认密码" required>
                    <label for="confirm">确认密码</label>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="bi bi-person-plus me-2"></i>立即注册
                </button>
            </form>
            
            <div class="register-footer">
                <p class="mb-0">已有账号？<a href="login.php">立即登录</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // 密码强度检测
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('strengthBar');
            
            if (password.length === 0) {
                bar.className = 'password-strength-bar';
            } else if (password.length < 6) {
                bar.className = 'password-strength-bar strength-weak';
            } else if (password.length < 10 || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                bar.className = 'password-strength-bar strength-medium';
            } else {
                bar.className = 'password-strength-bar strength-strong';
            }
        });
        
        // 表单验证
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('两次输入的密码不一致');
            }
        });
    </script>
</body>
</html>
