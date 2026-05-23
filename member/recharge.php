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

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $payType = $_POST['pay_type'] ?? 'alipay';
    
    if ($amount < 0.01) {
        $error = '充值金额不能小于0.01元';
    } else {
        // 生成订单号
        $orderNo = 'R' . date('YmdHis') . generateRandom(6, true);
        
        // 创建订单
        $app->db->insert('orders', [
            'order_no' => $orderNo,
            'member_id' => $memberId,
            'type' => 'recharge',
            'amount' => $amount,
            'subject' => '账户充值 ' . $amount . ' 元',
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
                '账户充值',
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

// 充值金额选项
$amountOptions = [10, 50, 100, 200, 500, 1000];

// 支付方式
$payMethods = [
    'alipay' => ['name' => '支付宝', 'icon' => 'bi-alipay', 'color' => '#1677ff'],
    'wxpay' => ['name' => '微信支付', 'icon' => 'bi-wechat', 'color' => '#07c160'],
    'qqpay' => ['name' => 'QQ支付', 'icon' => 'bi-qq', 'color' => '#12b7f5'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账户充值 - <?php echo $siteName; ?></title>
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
        
        /* 余额卡片 */
        .balance-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            padding: 35px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .balance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .balance-content {
            position: relative;
            z-index: 1;
        }
        
        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .balance-amount {
            font-size: 42px;
            font-weight: 700;
        }
        
        .balance-amount span {
            font-size: 24px;
            font-weight: 500;
        }
        
        /* 充值区域 */
        .recharge-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
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
        
        /* 金额选项 */
        .amount-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .amount-option {
            position: relative;
        }
        
        .amount-option input {
            position: absolute;
            opacity: 0;
        }
        
        .amount-option label {
            display: block;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .amount-option label:hover {
            background: #f0f2f5;
        }
        
        .amount-option input:checked + label {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .amount-option .amount-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        
        .amount-option .amount-unit {
            font-size: 14px;
            color: #999;
        }
        
        /* 自定义金额 */
        .custom-amount {
            margin-bottom: 30px;
        }
        
        .custom-amount .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 14px;
            padding: 16px 20px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .custom-amount .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .custom-amount .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e8e8e8;
            border-right: none;
            border-radius: 14px 0 0 14px;
            padding: 16px 20px;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .custom-amount .form-control {
            border-left: none;
        }
        
        /* 支付方式 */
        .pay-methods {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
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
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pay-method label:hover {
            background: #f0f2f5;
        }
        
        .pay-method input:checked + label {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .pay-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .pay-icon.alipay { background: #e6f4ff; color: #1677ff; }
        .pay-icon.wxpay { background: #e6f7ed; color: #07c160; }
        .pay-icon.qqpay { background: #e6f7ff; color: #12b7f5; }
        
        .pay-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .pay-info p {
            font-size: 12px;
            color: #999;
            margin: 2px 0 0;
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
        
        /* 提示信息 */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-danger { background: #fee; color: #c33; }
        .alert-success { background: #efe; color: #2a6; }

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

            .balance-card {
                padding: 25px;
            }

            .balance-amount {
                font-size: 32px;
            }

            .recharge-section {
                padding: 25px;
            }

            .amount-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pay-methods {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .amount-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .amount-option label {
                padding: 15px 10px;
            }

            .amount-option .amount-value {
                font-size: 20px;
            }

            .custom-amount .input-group-text,
            .custom-amount .form-control {
                padding: 12px 15px;
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
                    <div style="font-size: 12px; color: #999;">账户充值</div>
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
                <a class="nav-link active" href="recharge.php"><i class="bi bi-wallet"></i><span>充值中心</span></a>
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
                <h2>账户充值</h2>
                <p>选择金额和支付方式完成充值</p>
            </div>
        </div>
        
        <!-- 余额卡片 -->
        <div class="balance-card">
            <div class="balance-content">
                <div class="balance-label"><i class="bi bi-wallet2 me-2"></i>当前余额</div>
                <div class="balance-amount"><span>¥</span><?php echo number_format($member['money'], 2); ?></div>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- 充值表单 -->
        <div class="recharge-section">
            <form method="POST" action="" id="rechargeForm">
                <h3 class="section-title"><i class="bi bi-cash-stack"></i>选择充值金额</h3>
                
                <div class="amount-grid">
                    <?php foreach ($amountOptions as $amt): ?>
                    <div class="amount-option">
                        <input type="radio" name="amount_preset" id="amt<?php echo $amt; ?>" value="<?php echo $amt; ?>" <?php echo $amt == 100 ? 'checked' : ''; ?>>
                        <label for="amt<?php echo $amt; ?>">
                            <div class="amount-value">¥<?php echo $amt; ?></div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="custom-amount">
                    <label class="form-label">或输入自定义金额</label>
                    <div class="input-group">
                        <span class="input-group-text">¥</span>
                        <input type="number" name="amount" class="form-control" placeholder="输入金额" step="0.01" min="0.01" value="100">
                    </div>
                </div>
                
                <h3 class="section-title"><i class="bi bi-credit-card"></i>选择支付方式</h3>
                
                <div class="pay-methods">
                    <?php foreach ($payMethods as $key => $method): ?>
                    <div class="pay-method">
                        <input type="radio" name="pay_type" id="pay_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $key == 'alipay' ? 'checked' : ''; ?>>
                        <label for="pay_<?php echo $key; ?>">
                            <div class="pay-icon <?php echo $key; ?>"><i class="bi <?php echo $method['icon']; ?>"></i></div>
                            <div class="pay-info">
                                <h4><?php echo $method['name']; ?></h4>
                                <p>安全快捷支付</p>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="bi bi-lightning-charge me-2"></i>立即充值
                </button>
            </form>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 预设金额点击时更新输入框
        document.querySelectorAll('input[name="amount_preset"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelector('input[name="amount"]').value = this.value;
            });
        });

        // 自定义金额输入时取消预设选择
        document.querySelector('input[name="amount"]').addEventListener('input', function() {
            document.querySelectorAll('input[name="amount_preset"]').forEach(radio => {
                radio.checked = false;
            });
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
