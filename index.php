<?php
require_once __DIR__ . '/core/bootstrap.php';

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
$siteTitle = $app->getSetting('site_title', 'QQ快捷登录 - 免费QQ登录接入服务');
$siteDesc = $app->getSetting('site_desc', '为开发者提供便捷的QQ登录接入服务');

// 获取VIP等级列表（用于价格方案）
$vipLevels = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('vip_levels')} WHERE status=1 ORDER BY level ASC"
);

// 获取统计数据（真实数据）
$appResult = $app->db->fetch("SELECT COUNT(*) as count FROM {$app->db->table('apps')} WHERE status=1");
$appCount = intval($appResult['count'] ?? 0);

$loginResult = $app->db->fetch("SELECT COUNT(*) as count FROM {$app->db->table('login_logs')} WHERE create_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$loginCount = intval($loginResult['count'] ?? 0);

// 格式化数字
function formatNumber($num) {
    if ($num >= 10000) {
        return round($num / 10000, 1) . '万';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return (string)$num;
}

$appCountDisplay = formatNumber($appCount);
$loginCountDisplay = formatNumber($loginCount);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteTitle; ?></title>
    <meta name="description" content="<?php echo $siteDesc; ?>">
    <meta name="keywords" content="QQ登录,快捷登录,OAuth,第三方登录,免费登录接口">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./static/css/style.css?v=2">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="./">
                <i class="bi bi-shield-check me-2"></i>
                <strong><?php echo $siteName; ?></strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">功能特性</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">价格</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./docs.php">开发文档</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-light btn-sm" href="./member/">
                            <i class="bi bi-person-circle me-1"></i>用户中心
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="badge bg-white text-primary mb-3 px-3 py-2">
                        <i class="bi bi-stars me-1"></i>免费版已上线
                    </div>
                    <h1 class="display-4 fw-bold mb-4">
                        一键接入<br>
                        <span class="text-warning">QQ登录</span>
                    </h1>
                    <p class="lead mb-4 text-white-75">
                        简单、快速、免费的QQ登录解决方案。<br>
                        无需繁琐申请，注册即可使用，5分钟完成接入。
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                        <a href="./member/" class="btn btn-light btn-lg px-4 text-nowrap">
                            <i class="bi bi-rocket-takeoff me-2"></i>立即接入
                        </a>
                        <a href="./docs.php" class="btn btn-outline-light btn-lg px-4 text-nowrap">
                            <i class="bi bi-book me-2"></i>查看文档
                        </a>
                    </div>
                    <div class="hero-stats d-flex gap-4">
                        <div>
                            <h4 class="mb-0"><?php echo $appCountDisplay; ?></h4>
                            <small class="text-white-75">接入应用</small>
                        </div>
                        <div>
                            <h4 class="mb-0"><?php echo $loginCountDisplay; ?></h4>
                            <small class="text-white-75">日登录次数</small>
                        </div>
                        <div>
                            <h4 class="mb-0">99.9%</h4>
                            <small class="text-white-75">服务可用性</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image-wrapper">
                        <div class="platform-icons">
                            <div class="platform-icon qq-icon" style="left: 50%; transform: translateX(-50%);">
                                <img src="https://img.icons8.com/color/96/qq.png" alt="QQ">
                            </div>
                        </div>
                        <div class="your-app" style="bottom: 20%;">
                            <i class="bi bi-app-indicator"></i>
                            <span>您的应用</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-wave">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
            </svg>
        </div>
    </section>

    <!-- 功能特性 -->
    <section class="features-section py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">为什么选择我们</h2>
                <p class="text-muted">专业的QQ登录解决方案</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon bg-primary-soft">
                            <i class="bi bi-lightning-charge-fill text-primary"></i>
                        </div>
                        <h4>极速接入</h4>
                        <p class="text-muted mb-0">只需几行代码，5分钟即可完成接入。提供完整的SDK和示例代码，让接入变得简单高效。</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon bg-success-soft">
                            <i class="bi bi-shield-check text-success"></i>
                        </div>
                        <h4>安全可靠</h4>
                        <p class="text-muted mb-0">采用标准OAuth2.0协议，数据传输全程加密。多重安全验证机制，保障用户信息安全。</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon bg-info-soft">
                            <i class="bi bi-currency-dollar text-info"></i>
                        </div>
                        <h4>完全免费</h4>
                        <p class="text-muted mb-0">基础功能永久免费使用，无隐藏费用。提供充足的免费调用额度，满足大部分开发需求。</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon bg-warning-soft">
                            <i class="bi bi-graph-up-arrow text-warning"></i>
                        </div>
                        <h4>数据分析</h4>
                        <p class="text-muted mb-0">提供详细的登录数据统计，实时了解用户登录情况，助力业务决策。</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon bg-danger-soft">
                            <i class="bi bi-headset text-danger"></i>
                        </div>
                        <h4>技术支持</h4>
                        <p class="text-muted mb-0">专业的技术团队提供支持，详细的开发文档，活跃的开发者社区。</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon bg-purple-soft">
                            <i class="bi bi-arrow-repeat text-purple"></i>
                        </div>
                        <h4>多模式对接</h4>
                        <p class="text-muted mb-0">支持官方平台模式和同系统对接模式，灵活满足不同场景需求。</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 快速开始 -->
    <section class="quickstart-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">快速开始</h2>
                <p class="text-muted">三步完成登录接入</p>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="step-card text-center">
                        <div class="step-number">1</div>
                        <h4>注册应用</h4>
                        <p class="text-muted">在用户中心注册您的应用，获取AppID和AppKey</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card text-center">
                        <div class="step-number">2</div>
                        <h4>集成代码</h4>
                        <p class="text-muted">参考开发文档，将登录按钮集成到您的网站</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card text-center">
                        <div class="step-number">3</div>
                        <h4>上线运行</h4>
                        <p class="text-muted">处理回调逻辑，即可开始使用登录功能</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="code-demo">
                        <div class="code-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-code-slash me-2"></i>接入示例</span>
                            <button class="btn btn-sm btn-outline-light" onclick="copyCode()">
                                <i class="bi bi-clipboard me-1"></i>复制
                            </button>
                        </div>
                        <pre class="m-0"><code class="language-html">&lt;!-- 在您的登录页面添加 --&gt;
&lt;a href="<?php echo $siteUrl; ?>connect.php?app_id=YOUR_APP_ID&return_url=YOUR_CALLBACK"&gt;
    &lt;img src="https://img.icons8.com/color/96/qq.png" width="40"&gt;
    &lt;span&gt;QQ登录&lt;/span&gt;
&lt;/a&gt;

&lt;!-- 或者使用JavaScript方式 --&gt;
&lt;script&gt;
function qqLogin() {
    const params = new URLSearchParams({
        app_id: 'YOUR_APP_ID',
        return_url: window.location.origin + '/callback'
    });
    window.location.href = '<?php echo $siteUrl; ?>connect.php?' + params;
}
&lt;/script&gt;</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 价格方案 -->
    <section class="pricing-section py-5" id="pricing">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">价格方案</h2>
                <p class="text-muted">选择适合您的方案</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php if (!empty($vipLevels)): ?>
                    <?php foreach ($vipLevels as $index => $level): 
                        $isPopular = ($index == 1); // 第二个设为推荐
                        $isFree = ($level['price'] == 0);
                        
                        // 构建特性列表
                        $features = [];
                        if ($level['level'] == 0) {
                            $features = [
                                ['icon' => 'check', 'text' => 'QQ登录'],
                                ['icon' => 'check', 'text' => '应用数量：' . ($level['app_limit'] > 0 ? $level['app_limit'] . '个' : '不限')],
                                ['icon' => 'check', 'text' => '调用次数：' . ($level['login_limit'] > 0 ? $level['login_limit'] . '/月' : '不限')],
                                ['icon' => 'check', 'text' => '基础数据统计'],
                                ['icon' => 'x', 'text' => '优先技术支持', 'muted' => true],
                            ];
                        } else {
                            $features = [
                                ['icon' => 'check', 'text' => 'QQ登录'],
                                ['icon' => 'check', 'text' => '应用数量：' . ($level['app_limit'] > 0 ? $level['app_limit'] . '个' : '不限')],
                                ['icon' => 'check', 'text' => '调用次数：' . ($level['login_limit'] > 0 ? $level['login_limit'] . '/月' : '不限')],
                                ['icon' => 'check', 'text' => '高级数据分析'],
                                ['icon' => 'check', 'text' => '优先技术支持'],
                            ];
                        }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="pricing-card <?php echo $isPopular ? 'popular' : ''; ?>">
                            <?php if ($isPopular): ?>
                                <div class="popular-badge">推荐</div>
                            <?php endif; ?>
                            <div class="pricing-header">
                                <h4><?php echo htmlspecialchars($level['name']); ?></h4>
                                <div class="pricing-price">
                                    <span class="currency">¥</span>
                                    <span class="amount"><?php echo number_format($level['price'], $level['price'] == intval($level['price']) ? 0 : 2); ?></span>
                                    <span class="period">/月</span>
                                </div>
                            </div>
                            <ul class="pricing-features">
                                <?php foreach ($features as $feature): ?>
                                    <li class="<?php echo $feature['muted'] ? 'text-muted' : ''; ?>">
                                        <?php if ($feature['icon'] == 'check'): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-x-circle"></i>
                                        <?php endif; ?>
                                        <?php echo $feature['text']; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="./member/" class="btn <?php echo $isPopular ? 'btn-primary' : 'btn-outline-primary'; ?> w-100">
                                <?php echo $isFree ? '免费开始' : '立即升级'; ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- 默认价格方案 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="pricing-card">
                            <div class="pricing-header">
                                <h4>免费版</h4>
                                <div class="pricing-price">
                                    <span class="currency">¥</span>
                                    <span class="amount">0</span>
                                    <span class="period">/月</span>
                                </div>
                            </div>
                            <ul class="pricing-features">
                                <li><i class="bi bi-check-circle-fill text-success"></i> QQ登录</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 每日1000次调用</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 基础数据统计</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 邮件技术支持</li>
                                <li class="text-muted"><i class="bi bi-x-circle"></i> 优先技术支持</li>
                            </ul>
                            <a href="./member/" class="btn btn-outline-primary w-100">免费开始</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="pricing-card popular">
                            <div class="popular-badge">推荐</div>
                            <div class="pricing-header">
                                <h4>专业版</h4>
                                <div class="pricing-price">
                                    <span class="currency">¥</span>
                                    <span class="amount">9</span>
                                    <span class="period">/月</span>
                                </div>
                            </div>
                            <ul class="pricing-features">
                                <li><i class="bi bi-check-circle-fill text-success"></i> QQ登录</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 应用数量：5个</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 每月10000次调用</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 高级数据分析</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 优先技术支持</li>
                            </ul>
                            <a href="./member/" class="btn btn-primary w-100">立即升级</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="pricing-card">
                            <div class="pricing-header">
                                <h4>高级版</h4>
                                <div class="pricing-price">
                                    <span class="currency">¥</span>
                                    <span class="amount">29</span>
                                    <span class="period">/月</span>
                                </div>
                            </div>
                            <ul class="pricing-features">
                                <li><i class="bi bi-check-circle-fill text-success"></i> QQ登录</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 应用数量：不限</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 调用次数：不限</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 高级数据分析</li>
                                <li><i class="bi bi-check-circle-fill text-success"></i> 7×24技术支持</li>
                            </ul>
                            <a href="./member/" class="btn btn-outline-primary w-100">立即升级</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container">
            <div class="cta-card text-center">
                <h2 class="fw-bold mb-3">准备好开始了吗？</h2>
                <p class="lead mb-4">立即注册，5分钟完成接入</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <a href="./member/reg.php" class="btn btn-light btn-lg text-nowrap">
                        <i class="bi bi-person-plus me-2"></i>免费注册
                    </a>
                    <a href="./docs.php" class="btn btn-outline-light btn-lg text-nowrap">
                        <i class="bi bi-book me-2"></i>查看文档
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3" style="color: rgba(255,255,255,0.9);">
                        <i class="bi bi-shield-check me-2"></i><?php echo $siteName; ?>
                    </h5>
                    <p style="color: rgba(255,255,255,0.6);">专业的第三方登录解决方案提供商，为开发者提供简单、快速、安全的登录接入服务。</p>
                    <div class="social-links">
                        <a href="#" class="me-3"><i class="bi bi-github"></i></a>
                        <a href="#" class="me-3"><i class="bi bi-wechat"></i></a>
                        <a href="#"><i class="bi bi-envelope"></i></a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="mb-3" style="color: rgba(255,255,255,0.9);">产品</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#features">功能特性</a></li>
                        <li><a href="#pricing">价格方案</a></li>
                        <li><a href="./docs.php">开发文档</a></li>
                        <li><a href="#">更新日志</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="mb-3" style="color: rgba(255,255,255,0.9);">支持</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="./docs.php">帮助中心</a></li>
                        <li><a href="#">API文档</a></li>
                        <li><a href="#">SDK下载</a></li>
                        <li><a href="#">社区论坛</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="mb-3" style="color: rgba(255,255,255,0.9);">公司</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#">关于我们</a></li>
                        <li><a href="#">联系我们</a></li>
                        <li><a href="#">加入我们</a></li>
                        <li><a href="#">合作伙伴</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="mb-3" style="color: rgba(255,255,255,0.9);">法律</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="./agreement.php">用户协议</a></li>
                        <li><a href="#">隐私政策</a></li>
                        <li><a href="#">服务条款</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0" style="color: rgba(255,255,255,0.6);">&copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small style="color: rgba(255,255,255,0.6);">由快捷登录平台提供技术支持</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // 导航栏滚动效果
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });

    // 复制代码
    function copyCode() {
        const code = document.querySelector('.code-demo code').innerText;
        navigator.clipboard.writeText(code).then(() => {
            const btn = document.querySelector('.code-header button');
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check me-1"></i>已复制';
            setTimeout(() => btn.innerHTML = original, 2000);
        });
    }

    // 平滑滚动
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    </script>
</body>
</html>
