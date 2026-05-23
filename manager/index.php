<?php
require_once __DIR__ . '/../core/bootstrap.php';

// 管理员登录检查
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$adminId = $_SESSION['admin_id'];
$admin = $app->db->fetch("SELECT * FROM {$app->db->table('admins')} WHERE id=?", [$adminId]);

// 统计数据
$today = date('Y-m-d');
$stats = [
    'members' => $app->db->count('members'),
    'apps' => $app->db->count('apps'),
    'accounts' => $app->db->count('accounts'),
    'today_logins' => $app->db->count('login_logs', "DATE(create_time)=?", [$today])
];

// 最近登录记录
$recentLogins = $app->db->fetchAll(
    "SELECT l.*, a.name as app_name FROM {$app->db->table('login_logs')} l 
     LEFT JOIN {$app->db->table('apps')} a ON l.app_id=a.id 
     ORDER BY l.id DESC LIMIT 10"
);

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
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
            <a href="index.php" class="brand">管理后台</a>
        </div>
        
        <div class="header-right">
            <a href="../" class="header-icon" title="返回首页">
                <i class="bi bi-house"></i>
            </a>
            <div class="user-dropdown dropdown" id="userDropdown">
                <div class="user-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($admin['username'] ?? '管理员'); ?></div>
                    <div style="font-size: 12px; color: #999;">超级管理员</div>
                </div>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>系统设置</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>退出登录</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="nav-menu">
            <div class="nav-section">
                <div class="nav-section-title">主菜单</div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>控制台</span>
                    </a>
                    <a class="nav-link" href="members.php">
                        <i class="bi bi-people"></i>
                        <span>用户管理</span>
                    </a>
                    <a class="nav-link" href="apps.php">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span>应用管理</span>
                    </a>
                    <a class="nav-link" href="vip_levels.php">
                        <i class="bi bi-gem"></i>
                        <span>会员等级</span>
                    </a>
                </nav>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">系统</div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-sliders"></i>
                        <span>系统设置</span>
                    </a>
                    <a class="nav-link" href="about.php">
                        <i class="bi bi-info-circle"></i>
                        <span>关于程序</span>
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>退出登录</span>
                    </a>
                </nav>
            </div>
        </div>
    </aside>

    <!-- 主内容 -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">控制台</h1>
            <div class="page-actions">
                <a href="settings.php" class="btn btn-secondary">
                    <i class="bi bi-gear"></i> 系统设置
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['members']); ?></h3>
                    <p>注册用户</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['apps']); ?></h3>
                    <p>应用数量</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['accounts']); ?></h3>
                    <p>登录用户</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['today_logins']); ?></h3>
                    <p>今日登录</p>
                </div>
            </div>
        </div>

        <!-- Recent Logins Card -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-clock-history me-2"></i>最近登录记录
                </h5>
                <a href="#" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-right"></i> 查看全部
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>应用</th>
                                <th>用户</th>
                                <th>IP地址</th>
                                <th>时间</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogins as $log): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-info">
                                        <i class="bi bi-app me-1"></i>
                                        <?php echo htmlspecialchars($log['app_name'] ?? '未知应用'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?php echo mb_substr($log['nickname'], 0, 1); ?>
                                        </div>
                                        <?php echo htmlspecialchars($log['nickname']); ?>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 6px; font-size: 0.85rem;">
                                        <?php echo $log['ip']; ?>
                                    </code>
                                </td>
                                <td>
                                    <i class="bi bi-clock me-1 text-muted"></i>
                                    <?php echo $log['create_time']; ?>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <i class="bi bi-check-circle me-1"></i>成功
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentLogins)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state">
                                        <div class="empty-icon" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-title">暂无记录</div>
                                        <div class="empty-desc">还没有用户登录记录</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-lightning-charge me-2"></i>快捷操作
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="members.php" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> 添加用户
                            </a>
                            <a href="apps.php" class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> 添加应用
                            </a>
                            <a href="settings.php" class="btn btn-secondary">
                                <i class="bi bi-gear"></i> 系统配置
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle me-2"></i>系统信息
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-muted small mb-1">PHP版本</div>
                                <div class="fw-semibold"><?php echo phpversion(); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small mb-1">当前时间</div>
                                <div class="fw-semibold"><?php echo date('Y-m-d H:i'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 用户下拉菜单
        document.getElementById('userDropdown').addEventListener('click', function() {
            this.querySelector('.dropdown-menu').classList.toggle('show');
        });
        
        // 点击其他地方关闭下拉菜单
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#userDropdown')) {
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
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });
        
        // 窗口大小改变时重置侧边栏状态
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>
