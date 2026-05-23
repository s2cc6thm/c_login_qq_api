<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$adminId = $_SESSION['admin_id'];
$admin = $app->db->fetch("SELECT * FROM {$app->db->table('admins')} WHERE id=?", [$adminId]);

$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'basic';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tab === 'basic') {
        // 基础设置
        $app->setSetting('site_name', $_POST['site_name'] ?? 'QQ快捷登录');
        $app->setSetting('site_title', $_POST['site_title'] ?? '');
        $app->setSetting('site_desc', $_POST['site_desc'] ?? '');

        // QQ登录配置（仅在官方平台模式下保存）
        if ($_POST['qq_login_mode'] !== 'third') {
            $app->setSetting('qq_app_id', $_POST['qq_app_id'] ?? '');
            $app->setSetting('qq_app_key', $_POST['qq_app_key'] ?? '');
        }

        // 登录模式设置
        $app->setSetting('qq_login_mode', $_POST['qq_login_mode'] ?? 'official');

        // 管理员账号设置
        if (!empty($_POST['admin_user'])) {
            $app->setSetting('admin_user', $_POST['admin_user']);
        }

        // 管理员密码设置（需要两次输入一致）
        if (!empty($_POST['admin_pass'])) {
            if ($_POST['admin_pass'] !== $_POST['admin_pass_confirm']) {
                $error = '两次输入的密码不一致，请重新输入';
            } else {
                $app->setSetting('admin_pass', password_hash($_POST['admin_pass'], PASSWORD_DEFAULT));
            }
        }

        if (empty($error)) {
            $message = '设置已保存';
        }
    } elseif ($tab === 'pay') {
        // 支付设置
        $app->setSetting('pay_api_url', $_POST['pay_api_url'] ?? '');
        $app->setSetting('pay_pid', $_POST['pay_pid'] ?? '');
        $app->setSetting('pay_key', $_POST['pay_key'] ?? '');
        $message = '设置已保存';
    } elseif ($tab === 'third') {
        // 对接配置
        $app->setSetting('third_api_url', rtrim($_POST['third_api_url'] ?? '', '/') . '/');
        $app->setSetting('third_app_id', $_POST['third_app_id'] ?? '');
        $app->setSetting('third_app_key', $_POST['third_app_key'] ?? '');
        $message = '设置已保存';
    }
}

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
$loginMode = $app->getSetting('qq_login_mode', 'official');
$siteUrl = $app->getSetting('site_url', 'http://' . $_SERVER['HTTP_HOST'] . '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?php echo $siteName; ?></title>
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
                    <a class="nav-link" href="index.php">
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
                    <a class="nav-link active" href="settings.php">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">系统设置</h1>
        </div>

        <!-- Success Message -->
        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-tab <?php echo $tab === 'basic' ? 'active' : ''; ?>" href="?tab=basic">
                    <i class="bi bi-gear me-2"></i>基础设置
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-tab <?php echo $tab === 'third' ? 'active' : ''; ?>" href="?tab=third">
                    <i class="bi bi-link me-2"></i>对接配置
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-tab <?php echo $tab === 'pay' ? 'active' : ''; ?>" href="?tab=pay">
                    <i class="bi bi-credit-card me-2"></i>支付配置
                </a>
            </li>
        </ul>

        <!-- Settings Content -->
        <div class="content-card">
            <div class="card-body">
                <?php if ($tab === 'basic'): ?>
                <form method="POST">
                    <h5 class="mb-4" style="color: var(--primary);">
                        <i class="bi bi-info-circle me-2"></i>网站信息
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">网站名称</label>
                                <div class="position-relative">
                                    <i class="bi bi-type position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($app->getSetting('site_name')); ?>" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">网站标题</label>
                                <div class="position-relative">
                                    <i class="bi bi-heading position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="site_title" class="form-control" value="<?php echo htmlspecialchars($app->getSetting('site_title')); ?>" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">网站描述</label>
                        <textarea name="site_desc" class="form-control" rows="2"><?php echo htmlspecialchars($app->getSetting('site_desc')); ?></textarea>
                    </div>

                    <h5 class="mb-4 mt-5" style="color: var(--primary);">
                        <i class="bi bi-qq me-2"></i>QQ登录配置
                    </h5>
                    
                    <!-- 登录模式选择 -->
                    <div class="mb-4">
                        <label class="form-label">登录模式选择</label>
                        <div class="mode-cards">
                            <div class="mode-card <?php echo $loginMode === 'official' ? 'active' : ''; ?>" onclick="selectMode('official')">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3" style="width: 48px; height: 48px; font-size: 1.25rem; background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                                            <i class="bi bi-gear-fill"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold">官方平台模式</h6>
                                            <p class="text-muted small mb-0">直接对接QQ互联官方平台，需要在QQ互联申请应用</p>
                                        </div>
                                    </div>
                                    <div class="check-icon">
                                        <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mode-card <?php echo $loginMode === 'third' ? 'active' : ''; ?>" onclick="selectMode('third')">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3" style="width: 48px; height: 48px; font-size: 1.25rem; background: linear-gradient(135deg, #10b981, #34d399);">
                                            <i class="bi bi-link-45deg"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold">对接同系统模式</h6>
                                            <p class="text-muted small mb-0">对接其他同类型系统（彩虹版或同系统），无需申请QQ互联</p>
                                        </div>
                                    </div>
                                    <div class="check-icon">
                                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="qq_login_mode" id="qq_login_mode" value="<?php echo $loginMode; ?>">
                    </div>

                    <!-- 官方平台配置 -->
                    <div id="official-config" style="display: <?php echo $loginMode === 'official' ? 'block' : 'none'; ?>;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <div style="flex: 1; min-width: 0;">
                                请在 <a href="https://connect.qq.com/" target="_blank" class="fw-bold">QQ互联</a> 申请应用，回调地址填写：<br class="d-md-none">
                                <code style="background: rgba(59, 130, 246, 0.1); color: #2563eb; padding: 4px 8px; border-radius: 6px;"><?php echo $siteUrl; ?>callback.php</code>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">QQ AppID</label>
                                    <div class="position-relative">
                                        <i class="bi bi-key position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                        <input type="text" name="qq_app_id" class="form-control" value="<?php echo htmlspecialchars($app->getSetting('qq_app_id')); ?>" style="padding-left: 45px;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">QQ AppKey</label>
                                    <div class="position-relative">
                                        <i class="bi bi-lock position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                        <input type="text" name="qq_app_key" class="form-control" value="<?php echo htmlspecialchars($app->getSetting('qq_app_key')); ?>" style="padding-left: 45px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 对接模式提示 -->
                    <div id="third-config-hint" class="alert alert-success" style="display: <?php echo $loginMode === 'third' ? 'block' : 'none'; ?>;">
                        <i class="bi bi-check-circle me-2"></i>
                        已选择对接同系统模式，请在「对接配置」选项卡中配置接口信息
                    </div>

                    <h5 class="mb-4 mt-5" style="color: var(--primary);">
                        <i class="bi bi-shield-lock me-2"></i>管理员设置
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">管理员账号</label>
                                <div class="position-relative">
                                    <i class="bi bi-person position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="admin_user" class="form-control" value="<?php echo htmlspecialchars($app->getSetting('admin_user', 'admin')); ?>" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">修改密码（留空则不修改）</label>
                                <div class="position-relative">
                                    <i class="bi bi-key-fill position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="password" name="admin_pass" class="form-control" placeholder="输入新密码" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">确认密码</label>
                                <div class="position-relative">
                                    <i class="bi bi-key position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="password" name="admin_pass_confirm" class="form-control" placeholder="再次输入新密码" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>保存设置
                        </button>
                    </div>
                </form>
                
                <script>
                function selectMode(mode) {
                    document.getElementById('qq_login_mode').value = mode;
                    
                    // 更新卡片样式
                    document.querySelectorAll('.mode-card').forEach(card => {
                        card.classList.remove('active');
                    });
                    event.currentTarget.classList.add('active');
                    
                    // 显示/隐藏配置区域
                    if (mode === 'official') {
                        document.getElementById('official-config').style.display = 'block';
                        document.getElementById('third-config-hint').style.display = 'none';
                    } else {
                        document.getElementById('official-config').style.display = 'none';
                        document.getElementById('third-config-hint').style.display = 'block';
                    }
                }
                </script>
                
                <?php elseif ($tab === 'third'): ?>
                <form method="POST">
                    <h5 class="mb-4" style="color: var(--primary);">
                        <i class="bi bi-link me-2"></i>对接同系统配置
                    </h5>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        可对接任意同类型系统（彩虹版或同系统），实现跨站登录。被对接站点需要添加本站点域名到授权回调地址。
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">接口地址</label>
                        <div class="position-relative">
                            <i class="bi bi-globe position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                            <input type="url" name="third_api_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($app->getSetting('third_api_url')); ?>"
                                   placeholder="https://www.example.com/" style="padding-left: 45px;">
                        </div>
                        <small class="text-muted">被对接站点的域名，以 http:// 或 https:// 开头，以 / 结尾</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">AppId</label>
                                <div class="position-relative">
                                    <i class="bi bi-app position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="third_app_id" class="form-control" 
                                           value="<?php echo htmlspecialchars($app->getSetting('third_app_id')); ?>" style="padding-left: 45px;">
                                </div>
                                <small class="text-muted">在被对接站点注册应用后获取的AppId</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">AppKey</label>
                                <div class="position-relative">
                                    <i class="bi bi-key position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="third_app_key" class="form-control" 
                                           value="<?php echo htmlspecialchars($app->getSetting('third_app_key')); ?>" style="padding-left: 45px;">
                                </div>
                                <small class="text-muted">在被对接站点注册应用后获取的AppKey</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <div style="flex: 1; min-width: 0;">
                            <h6 class="alert-heading">
                                <i class="bi bi-exclamation-triangle me-2"></i>使用说明
                            </h6>
                            <ol class="mb-0">
                                <li>在被对接站点注册一个应用，获取 AppId 和 AppKey</li>
                                <li>将被对接站点的回调地址填写为：<br class="d-md-none"><code><?php echo $siteUrl; ?>return.php</code></li>
                                <li>在本页面填写被对接站点的接口地址、AppId 和 AppKey</li>
                                <li>在「基础设置」中选择「对接同系统模式」并保存</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>保存配置
                        </button>
                    </div>
                </form>
                
                <?php else: ?>
                <form method="POST">
                    <h5 class="mb-4" style="color: var(--primary);">
                        <i class="bi bi-credit-card me-2"></i>易支付配置
                    </h5>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        支持彩虹易支付、码支付等易支付接口
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">支付接口地址</label>
                        <div class="position-relative">
                            <i class="bi bi-globe position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                            <input type="text" name="pay_api_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($app->getSetting('pay_api_url')); ?>"
                                   placeholder="https://pay.example.com/" style="padding-left: 45px;">
                        </div>
                        <small class="text-muted">易支付接口地址，如：https://pay.example.com/</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">商户ID (PID)</label>
                                <div class="position-relative">
                                    <i class="bi bi-person-badge position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="pay_pid" class="form-control" 
                                           value="<?php echo htmlspecialchars($app->getSetting('pay_pid')); ?>" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">商户密钥 (KEY)</label>
                                <div class="position-relative">
                                    <i class="bi bi-key position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                    <input type="text" name="pay_key" class="form-control" 
                                           value="<?php echo htmlspecialchars($app->getSetting('pay_key')); ?>" style="padding-left: 45px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>保存配置
                        </button>
                    </div>
                </form>
                <?php endif; ?>
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
