<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$memberId = $_SESSION['member_id'];
$member = $app->db->fetch(
    "SELECT m.*, v.app_limit, v.name as vip_name 
     FROM {$app->db->table('members')} m 
     LEFT JOIN {$app->db->table('vip_levels')} v ON m.vip_level=v.level 
     WHERE m.id=?",
    [$memberId]
);

$action = $_GET['action'] ?? 'list';
$appLimit = $member['app_limit'] ?? 1;
$appCount = $app->db->count('apps', 'member_id=?', [$memberId]);
$canCreateApp = $appLimit == 0 || $appCount < $appLimit;

// 创建应用
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canCreateApp) {
        $error = '您的应用数量已达上限，请升级会员';
    } else {
        $name = trim($_POST['name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        
        if (!empty($name) && !empty($domain)) {
            $appKey = generateRandom(32);
            $app->db->insert('apps', [
                'member_id' => $memberId,
                'app_id' => 'APP' . date('Ymd') . generateRandom(6, true),
                'app_key' => $appKey,
                'name' => $name,
                'domain' => $domain,
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 1
            ]);
            header('Location: apps.php');
            exit;
        }
    }
}

// 删除应用
if ($action === 'delete' && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $app->db->delete('apps', 'id=? AND member_id=?', [$id, $memberId]);
    header('Location: apps.php');
    exit;
}

// 获取应用列表
$apps = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('apps')} WHERE member_id=? ORDER BY id DESC",
    [$memberId]
);

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的应用 - <?php echo $siteName; ?></title>
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
            display: flex;
            align-items: center;
            justify-content: space-between;
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
        
        /* 配额卡片 */
        .quota-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        
        .quota-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .quota-title {
            font-weight: 600;
            color: #333;
        }
        
        .quota-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .quota-progress {
            height: 10px;
            background: #e8e8e8;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .quota-progress-bar {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            transition: width 0.5s ease;
        }
        
        .quota-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 13px;
            color: #888;
        }
        
        /* 应用卡片网格 */
        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        
        .app-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .app-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        
        .app-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }
        
        .app-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .app-logo {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .app-actions {
            display: flex;
            gap: 8px;
        }
        
        .app-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: #f5f7fa;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .app-action-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .app-action-btn.danger:hover {
            background: #dc3545;
        }
        
        .app-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .app-domain {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .app-meta {
            display: flex;
            gap: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .app-meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .app-meta-label {
            font-size: 12px;
            color: #999;
        }
        
        .app-meta-value {
            font-size: 13px;
            color: #333;
            font-weight: 500;
            font-family: monospace;
        }
        
        .app-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .app-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .app-status.active::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #28a745;
            border-radius: 50%;
        }
        
        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-icon {
            width: 120px;
            height: 120px;
            background: #f5f7fa;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }
        
        .empty-icon i {
            font-size: 50px;
            color: #ccc;
        }
        
        .empty-state h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #888;
            margin-bottom: 25px;
        }
        
        /* 创建按钮 */
        .btn-create {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-upgrade {
            padding: 12px 24px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
            color: white;
        }
        
        /* 模态框样式 */
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #f0f0f0;
            padding: 25px;
        }
        
        .modal-title {
            font-weight: 700;
            color: #333;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 20px 25px;
        }
        
        .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
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
            
            .page-header {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .app-grid {
                grid-template-columns: 1fr;
            }
            
            .quota-card {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .btn-create, .btn-upgrade {
                width: 100%;
                justify-content: center;
            }
            
            .app-card {
                padding: 20px;
            }
            
            .modal-dialog {
                margin: 10px;
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
            </div>
        </div>
    </header>
    
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="nav-section">
            <div class="nav-title">主菜单</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>控制台</span>
                </a>
                <a class="nav-link active" href="apps.php">
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
        <div class="page-header">
            <div class="page-title">
                <h2>我的应用</h2>
                <p>管理您的应用和获取API密钥</p>
            </div>
            
            <?php if ($canCreateApp): ?>
            <button class="btn-create" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-lg"></i>创建应用
            </button>
            <?php else: ?>
            <a href="vip.php" class="btn-upgrade">
                <i class="bi bi-gem"></i>升级会员
            </a>
            <?php endif; ?>
        </div>
        
        <!-- 配额卡片 -->
        <div class="quota-card">
            <div class="quota-header">
                <span class="quota-title">应用配额</span>
                <span class="quota-badge">
                    <?php echo $appCount; ?> / <?php echo $appLimit > 0 ? $appLimit : '不限'; ?>
                </span>
            </div>
            <div class="quota-progress">
                <div class="quota-progress-bar" style="width: <?php echo $appLimit > 0 ? ($appCount / $appLimit * 100) : 0; ?>%"></div>
            </div>
            <div class="quota-info">
                <span>已使用 <?php echo $appCount; ?> 个应用</span>
                <span><?php echo $appLimit > 0 ? '剩余 ' . ($appLimit - $appCount) . ' 个' : '无限制'; ?></span>
            </div>
        </div>
        
        <?php if (empty($apps)): ?>
        <!-- 空状态 -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-grid-3x3-gap"></i>
            </div>
            <h3>还没有应用</h3>
            <p>创建您的第一个应用开始使用服务</p>
            <?php if ($canCreateApp): ?>
            <button class="btn-create" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-lg"></i>创建应用
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- 应用列表 -->
        <div class="app-grid">
            <?php foreach ($apps as $item): ?>
            <div class="app-card">
                <div class="app-card-header">
                    <div class="app-logo">
                        <i class="bi bi-app"></i>
                    </div>
                    <div class="app-actions">
                        <button class="app-action-btn" onclick="showKey('<?php echo $item['app_key']; ?>')" title="查看密钥">
                            <i class="bi bi-key"></i>
                        </button>
                        <a href="?action=delete&id=<?php echo $item['id']; ?>" class="app-action-btn danger" onclick="return confirm('确定要删除该应用吗？此操作不可恢复。')" title="删除应用">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
                
                <div class="app-name">
                    <?php echo htmlspecialchars($item['name']); ?>
                    <span class="app-status active">运行中</span>
                </div>
                <div class="app-domain">
                    <i class="bi bi-globe me-1"></i><?php echo $item['domain']; ?>
                </div>
                
                <div class="app-meta">
                    <div class="app-meta-item">
                        <span class="app-meta-label">AppID</span>
                        <span class="app-meta-value"><?php echo $item['app_id']; ?></span>
                    </div>
                    <div class="app-meta-item">
                        <span class="app-meta-label">创建时间</span>
                        <span class="app-meta-value"><?php echo date('Y-m-d', strtotime($item['create_time'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
    
    <!-- 创建应用模态框 -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>创建新应用</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?action=create">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">应用名称</label>
                            <input type="text" name="name" class="form-control" placeholder="输入应用名称" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">网站域名</label>
                            <input type="text" name="domain" class="form-control" placeholder="example.com" required>
                            <div class="form-text">填写您的网站顶级域名</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border: none;">
                            <i class="bi bi-check-lg me-1"></i>创建
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 查看密钥模态框 -->
    <div class="modal fade" id="keyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>应用密钥</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" style="border-radius: 10px; border: none; background: #fff3cd;">
                        <i class="bi bi-exclamation-triangle me-2"></i>请妥善保管密钥，不要泄露给他人
                    </div>
                    <div class="p-3 bg-light rounded-3">
                        <code id="appKey" class="d-block" style="word-break: break-all; font-size: 14px; color: #333;"></code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="copyKey()" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border: none;">
                        <i class="bi bi-clipboard me-1"></i>复制密钥
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentKey = '';
        
        function showKey(key) {
            currentKey = key;
            document.getElementById('appKey').textContent = key;
            new bootstrap.Modal(document.getElementById('keyModal')).show();
        }
        
        function copyKey() {
            navigator.clipboard.writeText(currentKey).then(() => {
                alert('密钥已复制到剪贴板');
            });
        }
        
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
