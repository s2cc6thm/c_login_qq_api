<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$memberId = $_SESSION['member_id'];
$member = $app->db->fetch(
    "SELECT * FROM {$app->db->table('members')} WHERE id=?",
    [$memberId]
);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            $error = '新密码长度不能少于6位';
        } elseif (!password_verify($oldPassword, $member['password'])) {
            $error = '原密码错误';
        } else {
            $app->db->update('members', [
                'email' => $email,
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ], 'id=?', [$memberId]);
            $message = '密码修改成功';
        }
    } else {
        $app->db->update('members', ['email' => $email], 'id=?', [$memberId]);
        $message = '信息更新成功';
    }
    
    // 刷新数据
    $member = $app->db->fetch(
        "SELECT * FROM {$app->db->table('members')} WHERE id=?",
        [$memberId]
    );
}

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账号设置 - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --sidebar-width: 260px;
            --header-height: 70px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        /* 顶部导航 */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 30px;
            flex-wrap: nowrap;
        }
        
        .brand {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
        }
        
        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
            min-width: 0;
        }
        
        .header-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .header-icon:hover {
            background: var(--primary);
            color: white;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-dropdown:hover {
            background: #f8f9fa;
        }
        
        .user-dropdown > div:last-child {
            min-width: 0;
            overflow: hidden;
        }
        
        .user-dropdown > div:last-child > div {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        /* 侧边栏 */
        .sidebar {
            position: fixed;
            left: 0;
            top: var(--header-height);
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: white;
            border-right: 1px solid #eee;
            padding: 25px 15px;
            overflow-y: auto;
            z-index: 100;
        }
        
        .nav-section { margin-bottom: 25px; }
        
        .nav-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            font-weight: 600;
            padding: 0 15px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #f5f7fa;
            color: var(--primary);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .nav-link i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        /* 主内容区 */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 30px;
            min-height: calc(100vh - var(--header-height));
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .page-title p {
            color: #888;
            font-size: 14px;
            margin: 0;
        }
        
        /* 设置卡片 */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }
        
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        
        .card-header-custom {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
        }
        
        .card-title h4 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .card-title p {
            color: #888;
            font-size: 13px;
            margin: 4px 0 0;
        }
        
        /* 表单样式 */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-control:disabled {
            background: #f8f9fa;
            color: #888;
        }
        
        .form-text {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
        }
        
        /* 提交按钮 */
        .btn-submit {
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* 提示 */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        /* 分隔线 */
        .divider {
            height: 1px;
            background: #f0f0f0;
            margin: 25px 0;
        }
        
        /* 移动端菜单按钮 */
        .menu-toggle {
            display: none;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            align-items: center;
            justify-content: center;
            color: #666;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            margin-right: 10px;
        }
        
        .menu-toggle:hover {
            background: var(--primary);
            color: white;
        }
        
        /* 移动端遮罩层 */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* 响应式 */
        @media (max-width: 992px) {
            .menu-toggle {
                display: flex;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .top-header {
                padding: 0 15px;
            }
            
            .brand {
                font-size: 18px;
            }
            
            .main-content {
                padding: 20px 15px;
            }
            
            .header-right {
                gap: 10px;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .settings-card {
                padding: 20px;
            }
            
            .card-header-custom {
                padding-bottom: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- 移动端遮罩层 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- 顶部导航 -->
    <header class="top-header">
        <div style="display: flex; align-items: center;">
            <button class="menu-toggle" id="menuToggle" title="打开菜单">
                <i class="bi bi-list" style="font-size: 20px;"></i>
            </button>
            <a href="../" class="brand"><?php echo $siteName; ?></a>
        </div>
        <div class="header-right">
            <a href="../" class="header-icon" title="返回首页"><i class="bi bi-house"></i></a>
            <div class="user-dropdown">
                <div class="user-avatar"><i class="bi bi-person"></i></div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">账号设置</div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="nav-section">
            <div class="nav-title">主菜单</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i><span>控制台</span></a>
                <a class="nav-link" href="apps.php"><i class="bi bi-grid"></i><span>我的应用</span></a>
                <a class="nav-link" href="orders.php"><i class="bi bi-receipt"></i><span>订单记录</span></a>
            </nav>
        </div>
        <div class="nav-section">
            <div class="nav-title">财务中心</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="recharge.php"><i class="bi bi-wallet"></i><span>充值中心</span></a>
                <a class="nav-link" href="vip.php"><i class="bi bi-gem"></i><span>购买会员</span></a>
            </nav>
        </div>
        <div class="nav-section">
            <div class="nav-title">个人中心</div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="profile.php"><i class="bi bi-person-gear"></i><span>账号设置</span></a>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>退出登录</span></a>
            </nav>
        </div>
    </aside>
    
    <!-- 主内容 -->
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h2>账号设置</h2>
                <p>管理您的个人信息和账户安全</p>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="settings-grid">
            <!-- 基本信息 -->
            <div class="settings-card">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="bi bi-person"></i></div>
                    <div class="card-title">
                        <h4>基本信息</h4>
                        <p>管理您的个人资料</p>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['username']); ?>" disabled>
                        <div class="form-text">用户名不可修改</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">邮箱地址</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="card-header-custom" style="padding-bottom: 0; border: none;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"><i class="bi bi-shield-lock"></i></div>
                        <div class="card-title">
                            <h4>修改密码</h4>
                            <p>不修改请留空</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">原密码</label>
                        <input type="password" name="old_password" class="form-control" placeholder="输入原密码">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">新密码</label>
                        <input type="password" name="new_password" class="form-control" placeholder="输入新密码（至少6位）" minlength="6">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-lg me-2"></i>保存修改
                    </button>
                </form>
            </div>
            
            <!-- 账户信息 -->
            <div class="settings-card">
                <div class="card-header-custom">
                    <div class="card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);"><i class="bi bi-info-circle"></i></div>
                    <div class="card-title">
                        <h4>账户信息</h4>
                        <p>查看您的账户详情</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">注册时间</label>
                    <input type="text" class="form-control" value="<?php echo $member['create_time']; ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">最后登录</label>
                    <input type="text" class="form-control" value="<?php echo $member['login_time'] ?: '从未登录'; ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">登录IP</label>
                    <input type="text" class="form-control" value="<?php echo $member['login_ip'] ?: '-'; ?>" disabled>
                </div>
                
                <div class="divider"></div>
                
                <div class="card-header-custom" style="padding-bottom: 0; border: none;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #e85d04;"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="card-title">
                        <h4>危险操作</h4>
                        <p>请谨慎操作</p>
                    </div>
                </div>
                
                <a href="logout.php" class="btn btn-outline-danger w-100" style="border-radius: 12px; padding: 12px;">
                    <i class="bi bi-box-arrow-right me-2"></i>退出登录
                </a>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 移动端菜单切换
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function openSidebar() {
            sidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
        
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        
        sidebarOverlay.addEventListener('click', closeSidebar);
        
        // 点击侧边栏链接后自动关闭（移动端）
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });
        
        // 窗口大小改变时重置侧边栏状态
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>
