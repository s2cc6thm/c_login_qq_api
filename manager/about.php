<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$adminId = $_SESSION['admin_id'];
$admin = $app->db->fetch("SELECT * FROM {$app->db->table('admins')} WHERE id=?", [$adminId]);

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于程序 - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    

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
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-sliders"></i>
                        <span>系统设置</span>
                    </a>
                    <a class="nav-link active" href="about.php">
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


    <main class="main-content">

        <div class="page-header">
            <h1 class="page-title">关于程序</h1>
        </div>

        <div class="content-card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2.5rem; background: linear-gradient(135deg, #12b7f5 0%, #0066ff 100%);">
                        <i class="bi bi-tencent-qq"></i>
                    </div>
                    <h4 class="mb-2"><?php echo $siteName; ?></h4>
                    <p class="text-muted">便捷高效的第三方登录解决方案</p>
                </div>

                <hr class="my-4">

                <div class="mb-4">
                    <h5 class="mb-3" style="color: var(--primary);">
                        <i class="bi bi-lightbulb me-2"></i>开发背景
                    </h5>
                    <p class="text-muted" style="line-height: 1.8;">
                        本程序的设计灵感来源于<strong>消失的彩虹海</strong>开发的
                        <a href="https://blog.cccyun.cn/post-405.html" target="_blank" class="text-decoration-none">
                            <i class="bi bi-box-arrow-up-right small"></i> 彩虹聚合登录中转API程序
                        </a>
                        ，受其技术理念启发，我们致力于打造一款更加轻量、易用、高效的聚合登录解决方案。在开发过程中，我们深入分析了开发者的实际需求，重构了系统架构，优化了用户交互体验，并加入了会员等级、支付对接等商业化功能模块，使其能够更好地服务于个人开发者和中小企业，降低第三方登录接入的技术门槛和运营成本。
                    </p>
                </div>

                <div class="mb-4">
                    <h5 class="mb-3" style="color: var(--primary);">
                        <i class="bi bi-stars me-2"></i>程序功能
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1.25rem; background: linear-gradient(135deg, #12b7f5, #0066ff);">
                                        <i class="bi bi-tencent-qq"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">QQ快捷登录</h6>
                                    <p class="text-muted small mb-0">支持QQ一键登录，用户无需注册即可快速登录</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, #10b981, #34d399);">
                                        <i class="bi bi-grid"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">多应用管理</h6>
                                    <p class="text-muted small mb-0">支持创建和管理多个应用，独立配置回调地址</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">域名限制</h6>
                                    <p class="text-muted small mb-0">支持设置域名白名单，确保接口安全调用</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, #ec4899, #f472b6);">
                                        <i class="bi bi-clipboard-data"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">登录记录</h6>
                                    <p class="text-muted small mb-0">详细的登录日志记录，方便追踪和管理</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                                        <i class="bi bi-link-45deg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">系统对接</h6>
                                    <p class="text-muted small mb-0">支持对接同类型系统，无需申请QQ互联即可使用</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, #ef4444, #f87171);">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">会员系统</h6>
                                    <p class="text-muted small mb-0">完善的会员等级和充值系统，支持易支付接口</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="mb-4">
                    <h5 class="mb-3" style="color: var(--primary);">
                        <i class="bi bi-person-circle me-2"></i>关于作者
                    </h5>
                    <div class="alert alert-light border">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="https://q1.qlogo.cn/g?b=qq&nk=3514574884&s=100" alt="作者头像" 
                                     class="rounded-circle me-3" 
                                     style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #e5e7eb;">
                            </div>
                            <div>
                                <h6 class="mb-1">Knight</h6>
                                <p class="text-muted mb-2">专注于Web开发，致力于提供优质的应用解决方案</p>
                                <a href="https://blog.knight.vin" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-globe me-1"></i>访问作者博客
                                </a>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="mb-4">
                    <h5 class="mb-3" style="color: var(--primary);">
                        <i class="bi bi-chat-dots me-2"></i>反馈建议
                    </h5>
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="bi bi-info-circle-fill me-3 mt-1"></i>
                        <div>
                            <p class="mb-2">如果您在使用过程中遇到任何问题，或者有好的建议，欢迎加入我们的QQ交流群：</p>
                            <a href="https://qm.qq.com/q/aIXgBC2uUE" target="_blank" class="btn btn-primary">
                                <i class="bi bi-qq me-2"></i>加入反馈交流群
                            </a>
                        </div>
                    </div>
                </div>


                <div>
                    <h5 class="mb-3" style="color: var(--primary);">
                        <i class="bi bi-heart me-2"></i>致谢
                    </h5>
                    <p class="text-muted small" style="line-height: 1.8;">
                        感谢<strong>消失的彩虹海</strong>开发的彩虹聚合登录程序提供的参考和灵感。
                        本项目在开发过程中参考了众多开源项目和文档，在此一并表示感谢。
                    </p>
                </div>
            </div>
        </div>


        <div class="content-card mt-4">
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">当前版本</div>
                        <div class="fw-semibold">v1.0.0</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">PHP版本</div>
                        <div class="fw-semibold"><?php echo phpversion(); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">系统时间</div>
                        <div class="fw-semibold"><?php echo date('Y-m-d H:i'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.getElementById('userDropdown').addEventListener('click', function() {
            this.querySelector('.dropdown-menu').classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#userDropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
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
        
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>
