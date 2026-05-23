<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$memberId = $_SESSION['member_id'];

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 获取订单列表
$orders = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('orders')} 
     WHERE member_id=? 
     ORDER BY id DESC 
     LIMIT {$offset}, {$perPage}",
    [$memberId]
);

$total = $app->db->count('orders', 'member_id=?', [$memberId]);
$totalPages = ceil($total / $perPage);

$siteName = $app->getSetting('site_name', 'QQ快捷登录');

function getStatusText($status) {
    switch ($status) {
        case 0: return ['待支付', 'warning'];
        case 1: return ['已完成', 'success'];
        case 2: return ['已关闭', 'secondary'];
        default: return ['未知', 'secondary'];
    }
}

function getTypeText($type) {
    return $type === 'recharge' ? ['充值', 'primary'] : ['会员', 'info'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单记录 - <?php echo $siteName; ?></title>
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
        
        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        
        .stat-box h4 {
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .stat-box .value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        
        /* 订单表格 */
        .orders-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        
        .table {
            margin: 0;
            table-layout: auto;
        }
        
        .table th:nth-child(3),
        .table td:nth-child(3) {
            min-width: 150px;
        }
        
        .table th {
            border: none;
            color: #888;
            font-weight: 600;
            font-size: 13px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .table td {
            border: none;
            padding: 20px 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        .table tbody tr:hover {
            background: #fafafa;
        }
        
        .table tbody tr:last-child {
            border-bottom: none;
        }
        
        .order-no {
            font-family: monospace;
            font-size: 13px;
            color: #666;
            background: #f5f7fa;
            padding: 6px 12px;
            border-radius: 8px;
        }
        
        .order-subject {
            font-weight: 500;
            color: #333;
            white-space: nowrap;
        }
        
        .order-amount {
            font-weight: 700;
            color: #dc3545;
            font-size: 16px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-type {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }
        
        /* 分页 */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .pagination {
            gap: 8px;
        }
        
        .page-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #f5f7fa;
            color: var(--primary);
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            width: 100px;
            height: 100px;
            background: #f5f7fa;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .empty-icon i {
            font-size: 40px;
            color: #ccc;
        }
        
        .empty-state h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #888;
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
            
            .orders-section {
                padding: 20px;
            }
            
            .table th, .table td {
                padding: 15px 10px;
            }
            
            .order-no {
                font-size: 11px;
                padding: 4px 8px;
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
                    <div style="font-weight: 600; font-size: 14px;">订单记录</div>
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
                <a class="nav-link active" href="orders.php"><i class="bi bi-receipt"></i><span>订单记录</span></a>
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
                <a class="nav-link" href="profile.php"><i class="bi bi-person-gear"></i><span>账号设置</span></a>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>退出登录</span></a>
            </nav>
        </div>
    </aside>
    
    <!-- 主内容 -->
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h2>订单记录</h2>
                <p>查看您的所有交易记录</p>
            </div>
        </div>
        
        <!-- 订单列表 -->
        <div class="orders-section">
            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3>暂无订单</h3>
                <p>您还没有任何交易记录</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>类型</th>
                            <th>商品</th>
                            <th>金额</th>
                            <th>状态</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $status = getStatusText($order['status']);
                            $type = getTypeText($order['type']);
                        ?>
                        <tr>
                            <td><span class="order-no"><?php echo $order['order_no']; ?></span></td>
                            <td><span class="badge badge-type"><?php echo $type[0]; ?></span></td>
                            <td><span class="order-subject"><?php echo htmlspecialchars($order['subject']); ?></span></td>
                            <td><span class="order-amount">¥<?php echo number_format($order['amount'], 2); ?></span></td>
                            <td><span class="badge bg-<?php echo $status[1]; ?>"><?php echo $status[0]; ?></span></td>
                            <td style="color: #888; font-size: 13px;"><?php echo date('Y-m-d H:i', strtotime($order['create_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
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
