<?php
require_once __DIR__ . '/../core/bootstrap.php';

// 检查登录
if (empty($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$memberId = $_SESSION['member_id'];
$member = $app->db->fetch(
    "SELECT m.*, v.name as vip_name 
     FROM {$app->db->table('members')} m 
     LEFT JOIN {$app->db->table('vip_levels')} v ON m.vip_level=v.level 
     WHERE m.id=?",
    [$memberId]
);

if (!$member) {
    unset($_SESSION['member_id']);
    header('Location: login.php');
    exit;
}

// 检查VIP是否过期
$vipExpired = false;
if ($member['vip_level'] > 0 && $member['vip_expire']) {
    $vipExpired = strtotime($member['vip_expire']) < time();
    if ($vipExpired) {
        $member['vip_name'] .= '（已过期）';
    }
}

// 统计数据
$appCount = $app->db->count('apps', 'member_id=?', [$memberId]);
$loginCount = $app->db->fetch(
    "SELECT SUM(login_count) as total FROM {$app->db->table('accounts')} 
     WHERE app_id IN (SELECT id FROM {$app->db->table('apps')} WHERE member_id=?)",
    [$memberId]
);
$loginCount = $loginCount['total'] ?? 0;

// 获取应用限制
$vipLevel = $app->db->fetch(
    "SELECT app_limit FROM {$app->db->table('vip_levels')} WHERE level=?",
    [$member['vip_level']]
);
$appLimit = $vipLevel['app_limit'] ?? 1;
$canCreateApp = $appLimit == 0 || $appCount < $appLimit;

// 获取最近应用
$recentApps = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('apps')} WHERE member_id=? ORDER BY id DESC LIMIT 5",
    [$memberId]
);

$siteName = $app->getSetting('site_name', 'QQ快捷登录');

// 获取VIP颜色
function getVipColor($level) {
    $colors = [
        0 => ['#6c757d', '#adb5bd'],
        1 => ['#ffc107', '#ff9800'],
        2 => ['#0dcaf0', '#0d6efd'],
        3 => ['#d63384', '#6f42c1'],
    ];
    return $colors[$level] ?? $colors[0];
}

$vipColors = getVipColor($member['vip_level']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 - <?php echo $siteName; ?></title>
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
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
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
            min-width: 0;
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
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            font-weight: 600;
            padding: 0 15px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        .nav-item {
            margin-bottom: 4px;
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
        
        /* 统计卡片 */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        
        .stat-info p {
            color: #888;
            font-size: 14px;
            margin: 0;
        }
        
        /* 卡片 */
        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* 欢迎横幅 */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 16px;
            padding: 35px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-content h2 {
            font-size: 26px;
            margin-bottom: 10px;
        }
        
        .welcome-content p {
            opacity: 0.9;
            margin: 0;
        }
        
        .vip-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        
        /* 应用列表 */
        .app-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .app-item {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .app-item:hover {
            background: #f0f2f5;
        }
        
        .app-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .app-info {
            flex: 1;
        }
        
        .app-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }
        
        .app-id {
            font-size: 12px;
            color: #999;
        }
        
        .app-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .app-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        /* 快速操作 */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 25px 15px;
            background: #f8f9fa;
            border-radius: 14px;
            text-decoration: none;
            color: #666;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 28px;
        }
        
        .action-btn span {
            font-size: 14px;
            font-weight: 500;
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
            
            .stat-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-banner {
                padding: 25px;
            }
            
            .welcome-content h2 {
                font-size: 20px;
            }
            
            .main-content {
                padding: 20px 15px;
            }
            
            .header-right {
                gap: 10px;
            }
            
            .user-dropdown > div:last-child {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-btn {
                padding: 20px 10px;
            }
            
            .app-item {
                flex-wrap: wrap;
            }
            
            .app-status {
                margin-top: 10px;
                width: 100%;
                text-align: center;
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
            <a href="../" class="header-icon" title="返回首页">
                <i class="bi bi-house"></i>
            </a>
            <div class="user-dropdown dropdown">
                <div class="user-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($member['username']); ?></div>
                    <div style="font-size: 12px; color: #999;"><?php echo $member['vip_name'] ?? '普通用户'; ?></div>
                </div>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="profile.php"><i class="bi bi-gear me-2"></i>账号设置</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>退出登录</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="nav-section">
            <div class="nav-title">主菜单</div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>控制台</span>
                </a>
                <a class="nav-link" href="apps.php">
                    <i class="bi bi-grid"></i>
                    <span>我的应用</span>
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="bi bi-receipt"></i>
                    <span>订单记录</span>
                </a>
            </nav>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">财务中心</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="recharge.php">
                    <i class="bi bi-wallet"></i>
                    <span>充值中心</span>
                </a>
                <a class="nav-link" href="vip.php">
                    <i class="bi bi-gem"></i>
                    <span>购买会员</span>
                </a>
            </nav>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">个人中心</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="profile.php">
                    <i class="bi bi-person-gear"></i>
                    <span>账号设置</span>
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>退出登录</span>
                </a>
            </nav>
        </div>
    </aside>
    
    <!-- 主内容 -->
    <main class="main-content">
        <!-- 欢迎横幅 -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h2>👋 欢迎回来，<?php echo htmlspecialchars($member['username']); ?>！</h2>
                <p>这是您的控制中心，可以管理应用、查看统计数据和购买会员服务。</p>
                <div class="vip-badge">
                    <i class="bi bi-gem"></i>
                    <span><?php echo $member['vip_name'] ?? '普通用户'; ?><?php echo $member['vip_level'] > 0 && $member['vip_expire'] ? ' · 到期 ' . date('Y-m-d', strtotime($member['vip_expire'])) : ''; ?></span>
                </div>
            </div>
        </div>
        
        <!-- 统计卡片 -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-grid"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $appCount; ?><?php echo $appLimit > 0 ? '<span style="font-size: 16px; color: #999;">/' . $appLimit . '</span>' : ''; ?></h3>
                    <p>应用数量</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($loginCount); ?></h3>
                    <p>累计登录</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="stat-info">
                    <h3>¥<?php echo number_format($member['money'], 2); ?></h3>
                    <p>账户余额</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- 最近应用 -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">最近应用</h5>
                        <a href="apps.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>新建应用
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentApps)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-grid-3x3-gap" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-3">暂无应用，点击右上角创建</p>
                            </div>
                        <?php else: ?>
                            <div class="app-list">
                                <?php foreach ($recentApps as $app): ?>
                                <div class="app-item">
                                    <div class="app-icon">
                                        <i class="bi bi-app"></i>
                                    </div>
                                    <div class="app-info">
                                        <div class="app-name"><?php echo htmlspecialchars($app['name']); ?></div>
                                        <div class="app-id"><?php echo $app['app_id']; ?></div>
                                    </div>
                                    <span class="app-status active">
                                        <i class="bi bi-check-circle me-1"></i>正常
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($recentApps) >= 5): ?>
                            <div class="text-center mt-3">
                                <a href="apps.php" class="text-primary text-decoration-none">
                                    查看全部 <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- 快速操作 -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">快速操作</h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="apps.php" class="action-btn">
                                <i class="bi bi-plus-circle"></i>
                                <span>创建应用</span>
                            </a>
                            <a href="recharge.php" class="action-btn">
                                <i class="bi bi-wallet"></i>
                                <span>充值</span>
                            </a>
                            <a href="vip.php" class="action-btn">
                                <i class="bi bi-gem"></i>
                                <span>升级会员</span>
                            </a>
                            <a href="profile.php" class="action-btn">
                                <i class="bi bi-person-gear"></i>
                                <span>账号设置</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!$canCreateApp): ?>
                <div class="alert alert-warning mt-3" style="border-radius: 12px; border: none; background: #fff3cd;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    应用数量已达上限，<a href="vip.php" class="alert-link">升级会员</a>可创建更多
                </div>
                <?php endif; ?>
                
                <?php if ($member['vip_level'] == 0 || $vipExpired): ?>
                <div class="dashboard-card mt-3" style="background: linear-gradient(135deg, <?php echo $vipColors[0]; ?> 0%, <?php echo $vipColors[1]; ?> 100%); color: white;">
                    <div class="card-body text-center">
                        <i class="bi bi-gem" style="font-size: 40px; opacity: 0.8;"></i>
                        <h5 class="mt-3 mb-2">升级会员</h5>
                        <p class="mb-3 opacity-75" style="font-size: 14px;">解锁更多功能和更高配额</p>
                        <a href="vip.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-right me-1"></i>立即查看
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 用户下拉菜单
        document.querySelector('.user-dropdown').addEventListener('click', function() {
            this.querySelector('.dropdown-menu').classList.toggle('show');
        });
        
        // 点击其他地方关闭下拉菜单
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
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
