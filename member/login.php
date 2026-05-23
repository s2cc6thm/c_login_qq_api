<?php
require_once __DIR__ . '/../core/bootstrap.php';

// 已登录则跳转
if (!empty($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $member = $app->db->fetch(
            "SELECT * FROM {$app->db->table('members')} WHERE username=? AND status=1",
            [$username]
        );
        
        if ($member && password_verify($password, $member['password'])) {
            $_SESSION['member_id'] = $member['id'];
            
            // 更新登录时间
            $app->db->update('members', [
                'login_time' => date('Y-m-d H:i:s'),
                'login_ip' => getClientIp()
            ], 'id=?', [$member['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
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
    <title>用户登录 - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* 动态背景粒子 */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 15s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.3);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #888;
            font-size: 14px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 14px;
            height: 60px;
            padding: 20px 15px 10px 50px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-floating .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
            color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
            color: #764ba2;
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
        
        @media (max-width: 480px) {
            .login-card {
                padding: 35px 25px;
                margin: 15px;
            }
            
            .back-link {
                top: 15px;
                left: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- 背景粒子 -->
    <div class="particles" id="particles"></div>
    
    <a href="../" class="back-link">
        <i class="bi bi-arrow-left"></i> 返回首页
    </a>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h2>欢迎回来</h2>
                <p>登录您的账号继续使用</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-floating position-relative">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" name="username" class="form-control" id="username" placeholder="用户名" required>
                    <label for="username">用户名</label>
                </div>
                
                <div class="form-floating position-relative">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" class="form-control" id="password" placeholder="密码" required>
                    <label for="password">密码</label>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>登 录
                </button>
            </form>
            
            <div class="login-footer">
                <p class="mb-0">还没有账号？<a href="register.php">立即注册</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // 生成背景粒子
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (15 + Math.random() * 10) + 's';
            particlesContainer.appendChild(particle);
        }
    </script>
</body>
</html>
