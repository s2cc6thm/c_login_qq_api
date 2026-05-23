<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['member_id'])) {
    header('Location: login.php');
    exit;
}

$memberId = $_SESSION['member_id'];
$member = $app->db->fetch(
    "SELECT m.*, v.name as vip_name, v.description as vip_desc 
     FROM {$app->db->table('members')} m 
     LEFT JOIN {$app->db->table('vip_levels')} v ON m.vip_level=v.level 
     WHERE m.id=?",
    [$memberId]
);

// 获取所有会员等级
$vipLevels = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('vip_levels')} WHERE status=1 ORDER BY level ASC"
);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $levelId = intval($_POST['level_id'] ?? 0);
    $months = intval($_POST['months'] ?? 1);
    $payType = $_POST['pay_type'] ?? 'alipay';
    
    $vipLevel = $app->db->fetch(
        "SELECT * FROM {$app->db->table('vip_levels')} WHERE id=? AND status=1",
        [$levelId]
    );
    
    if (!$vipLevel) {
        $error = '会员等级不存在';
    } elseif ($months < 1 || $months > 12) {
        $error = '购买月数不正确';
    } else {
        $amount = $vipLevel['price'] * $months;
        $orderNo = 'V' . date('YmdHis') . generateRandom(6, true);
        
        // 创建订单
        $app->db->insert('orders', [
            'order_no' => $orderNo,
            'member_id' => $memberId,
            'type' => 'vip',
            'amount' => $amount,
            'subject' => $vipLevel['name'] . ' x ' . $months . '个月',
            'vip_level' => $vipLevel['level'],
            'vip_months' => $months,
            'pay_type' => $payType,
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ]);
        
        // 获取支付配置
        $payUrl = $app->getSetting('pay_api_url');
        $payPid = $app->getSetting('pay_pid');
        $payKey = $app->getSetting('pay_key');
        
        if (!empty($payUrl) && !empty($payPid) && !empty($payKey)) {
            $pay = new \Core\Lib\Pay($payUrl, $payPid, $payKey);
            $result = $pay->createOrder(
                $orderNo,
                $amount,
                $vipLevel['name'],
                $siteUrl . 'pay/notify.php',
                $siteUrl . 'pay/return.php',
                $payType
            );
            
            header('Location: ' . $result['pay_url']);
            exit;
        } else {
            $error = '支付接口未配置';
        }
    }
}

$siteName = $app->getSetting('site_name', 'QQ快捷登录');

// 检查VIP是否过期
$vipExpired = false;
if ($member['vip_level'] > 0 && $member['vip_expire']) {
    $vipExpired = strtotime($member['vip_expire']) < time();
}

// 获取VIP等级样式
function getVipStyle($level) {
    $styles = [
        0 => ['#6c757d', '#e9ecef', '普通用户'],
        1 => ['#ffc107', '#fff3cd', '黄金会员'],
        2 => ['#0dcaf0', '#cff4fc', '钻石会员'],
        3 => ['#d63384', '#f8d7da', '至尊会员'],
    ];
    return $styles[$level] ?? $styles[0];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>购买会员 - <?php echo $siteName; ?></title>
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
        
        .user-dropdown > div:last-child {
            min-width: 0;
            overflow: hidden;
        }
        
        .user-dropdown > div:last-child > div {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-dropdown:hover {
            background: #f8f9fa;
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
        
        /* 当前会员状态 */
        .current-vip-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .vip-icon-large {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
        }
        
        .vip-info h3 {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .vip-info p {
            color: #888;
            margin: 0;
        }
        
        .vip-expire {
            margin-left: auto;
            text-align: right;
        }
        
        .vip-expire .label {
            font-size: 13px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .vip-expire .date {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        /* 会员卡片 */
        .vip-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .vip-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .vip-card {
            position: relative;
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .vip-card:hover {
            transform: translateY(-5px);
        }
        
        .vip-card.selected {
            box-shadow: 0 0 0 3px var(--primary);
        }
        
        .vip-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .vip-card-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .vip-card-title h4 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        
        .vip-card-title p {
            font-size: 13px;
            margin: 4px 0 0;
            opacity: 0.8;
        }
        
        .vip-card-price {
            margin-bottom: 20px;
        }
        
        .vip-card-price .price {
            font-size: 36px;
            font-weight: 700;
        }
        
        .vip-card-price .unit {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .vip-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .vip-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .vip-features li i {
            font-size: 16px;
        }
        
        /* 购买选项 */
        .purchase-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .option-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
        }
        
        .option-group select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            appearance: none;
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E") no-repeat right 15px center;
        }
        
        .option-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        /* 支付方式 */
        .pay-methods {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .pay-method {
            position: relative;
        }
        
        .pay-method input {
            position: absolute;
            opacity: 0;
        }
        
        .pay-method label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .pay-method label:hover {
            background: #f0f2f5;
        }
        
        .pay-method input:checked + label {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.05);
        }
        
        /* 提交按钮 */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        /* 提示 */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-danger { background: #fee; color: #c33; }
        
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
            
            .current-vip-card {
                flex-wrap: wrap;
                text-align: center;
            }
            
            .vip-expire {
                margin-left: 0;
                width: 100%;
                text-align: center;
                margin-top: 15px;
            }
            
            .vip-grid {
                grid-template-columns: 1fr;
            }
            
            .vip-section {
                padding: 25px;
            }
            
            .purchase-options {
                grid-template-columns: 1fr;
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
                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($member['username']); ?></div>
                    <div style="font-size: 12px; color: #999;">购买会员</div>
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
                <a class="nav-link active" href="vip.php"><i class="bi bi-gem"></i><span>购买会员</span></a>
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
                <h2>购买会员</h2>
                <p>升级会员解锁更多功能和更高配额</p>
            </div>
        </div>
        
        <?php if ($member['vip_level'] > 0): ?>
        <!-- 当前会员状态 -->
        <div class="current-vip-card">
            <?php $currentStyle = getVipStyle($member['vip_level']); ?>
            <div class="vip-icon-large" style="background: linear-gradient(135deg, <?php echo $currentStyle[0]; ?> 0%, <?php echo $currentStyle[0]; ?>dd 100%);">
                <i class="bi bi-gem"></i>
            </div>
            <div class="vip-info">
                <h3><?php echo $member['vip_name']; ?></h3>
                <p><?php echo $member['vip_desc'] ?? '享受会员专属权益'; ?></p>
            </div>
            <div class="vip-expire">
                <div class="label">到期时间</div>
                <div class="date <?php echo $vipExpired ? 'text-danger' : ''; ?>">
                    <?php echo $vipExpired ? '已过期' : ($member['vip_expire'] ? date('Y-m-d', strtotime($member['vip_expire'])) : '永久'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- 会员选择 -->
        <div class="vip-section">
            <h3 class="section-title"><i class="bi bi-stars"></i>选择会员等级</h3>
            
            <form method="POST" action="" id="vipForm">
                <div class="vip-grid">
                    <?php foreach ($vipLevels as $level): 
                        $style = getVipStyle($level['level']);
                        $isCurrent = $member['vip_level'] == $level['level'];
                    ?>
                    <div class="vip-card" style="background: linear-gradient(135deg, <?php echo $style[1]; ?> 0%, white 100%); border: 2px solid <?php echo $style[0]; ?>30;" onclick="selectVip(<?php echo $level['id']; ?>)">
                        <div class="vip-card-header">
                            <div class="vip-card-icon" style="background: linear-gradient(135deg, <?php echo $style[0]; ?> 0%, <?php echo $style[0]; ?>dd 100%);">
                                <i class="bi bi-gem"></i>
                            </div>
                            <div class="vip-card-title">
                                <h4 style="color: <?php echo $style[0]; ?>"><?php echo $level['name']; ?></h4>
                                <p style="color: <?php echo $style[0]; ?>99"><?php echo $level['description']; ?></p>
                            </div>
                        </div>
                        
                        <div class="vip-card-price">
                            <span class="price" style="color: <?php echo $style[0]; ?>">¥<?php echo $level['price']; ?></span>
                            <span class="unit" style="color: <?php echo $style[0]; ?>99">/月</span>
                        </div>
                        
                        <ul class="vip-features">
                            <li style="color: #555">
                                <i class="bi bi-check-circle-fill" style="color: <?php echo $style[0]; ?>"></i>
                                应用数量：<?php echo $level['app_limit'] > 0 ? $level['app_limit'] . '个' : '不限'; ?>
                            </li>
                            <li style="color: #555">
                                <i class="bi bi-check-circle-fill" style="color: <?php echo $style[0]; ?>"></i>
                                登录次数：<?php echo $level['login_limit'] > 0 ? $level['login_limit'] . '次/月' : '不限'; ?>
                            </li>
                            <li style="color: #555">
                                <i class="bi bi-check-circle-fill" style="color: <?php echo $style[0]; ?>"></i>
                                专属客服支持
                            </li>
                        </ul>
                        
                        <input type="radio" name="level_id" value="<?php echo $level['id']; ?>" style="display: none;" <?php echo $level['level'] == 1 ? 'checked' : ''; ?>>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <h3 class="section-title"><i class="bi bi-sliders"></i>购买选项</h3>
                
                <div class="purchase-options">
                    <div class="option-group">
                        <label>购买时长</label>
                        <select name="months">
                            <option value="1">1个月</option>
                            <option value="3">3个月 (95折)</option>
                            <option value="6">6个月 (9折)</option>
                            <option value="12">12个月 (8折)</option>
                        </select>
                    </div>
                    <div class="option-group">
                        <label>支付方式</label>
                        <select name="pay_type">
                            <option value="alipay">支付宝</option>
                            <option value="wxpay">微信支付</option>
                            <option value="qqpay">QQ支付</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="bi bi-lightning-charge me-2"></i>立即开通
                </button>
            </form>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectVip(levelId) {
            document.querySelectorAll('.vip-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
        }
        
        // 默认选中第一个
        document.querySelector('.vip-card').classList.add('selected');
        
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
