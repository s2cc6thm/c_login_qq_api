<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$adminId = $_SESSION['admin_id'];
$admin = $app->db->fetch("SELECT * FROM {$app->db->table('admins')} WHERE id=?", [$adminId]);

// 添加应用
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $memberId = intval($_POST['member_id'] ?? 0);
    
    if (empty($name)) {
        $error = '应用名称不能为空';
    } else {
        // 生成唯一的 app_id 和 app_key
        $appId = 'APP' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
        $appKey = md5(uniqid() . time() . rand(1000, 9999));
        
        $data = [
            'member_id' => $memberId,
            'app_id' => $appId,
            'app_key' => $appKey,
            'name' => $name,
            'domain' => $domain,
            'create_time' => date('Y-m-d H:i:s'),
            'status' => 1
        ];
        
        $app->db->insert('apps', $data);
        $success = '应用添加成功！AppID: ' . $appId . '，AppKey: ' . $appKey;
    }
}

// 删除应用
if (!empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $app->db->delete('apps', 'id=?', [$id]);
    header('Location: apps.php');
    exit;
}

// 编辑应用
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $memberId = intval($_POST['member_id'] ?? 0);
    $status = intval($_POST['status'] ?? 1);
    
    if (empty($name)) {
        $error = '应用名称不能为空';
    } else {
        $data = [
            'name' => $name,
            'domain' => $domain,
            'member_id' => $memberId,
            'status' => $status
        ];
        
        $app->db->update('apps', $data, 'id=?', [$id]);
        $success = '应用信息已更新！';
    }
}

// 获取应用列表
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$apps = $app->db->fetchAll(
    "SELECT a.*, m.username FROM {$app->db->table('apps')} a 
     LEFT JOIN {$app->db->table('members')} m ON a.member_id=m.id 
     ORDER BY a.id DESC LIMIT {$offset}, {$perPage}"
);

$total = $app->db->count('apps');
$totalPages = ceil($total / $perPage);

// 获取用户列表（用于选择所属用户）
$members = $app->db->fetchAll("SELECT id, username FROM {$app->db->table('members')} WHERE status=1 ORDER BY id DESC");

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>应用管理 - <?php echo $siteName; ?></title>
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
                    <a class="nav-link active" href="apps.php">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">应用管理</h1>
            <div class="page-actions">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAppModal">
                    <i class="bi bi-plus-lg"></i> 添加应用
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <!-- Apps Card -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-grid me-2"></i>应用列表
                    <span class="badge badge-secondary ms-2">共 <?php echo $total; ?> 个</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>应用信息</th>
                                <th>AppID</th>
                                <th>AppKey</th>
                                <th>所属用户</th>
                                <th>域名</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $item): ?>
                            <tr>
                                <td><strong>#<?php echo $item['id']; ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width: 36px; height: 36px; font-size: 0.9rem; background: linear-gradient(135deg, #10b981, #34d399);">
                                            <i class="bi bi-app"></i>
                                        </div>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: rgba(102, 126, 234, 0.1); color: var(--primary); padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                                        <?php echo $item['app_id']; ?>
                                    </code>
                                </td>
                                <td>
                                    <code style="background: rgba(0,0,0,0.05); padding: 6px 12px; border-radius: 8px; font-size: 0.85rem;">
                                        <?php echo substr($item['app_key'], 0, 16); ?>...
                                    </code>
                                </td>
                                <td>
                                    <?php if ($item['username']): ?>
                                        <span class="badge badge-info">
                                            <i class="bi bi-person me-1"></i><?php echo $item['username']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">系统应用</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['domain']): ?>
                                        <i class="bi bi-globe me-1 text-muted"></i>
                                        <?php echo $item['domain']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="bi bi-calendar me-1 text-muted"></i>
                                    <?php echo $item['create_time']; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editAppModal"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                        data-domain="<?php echo htmlspecialchars($item['domain'] ?? ''); ?>"
                                        data-member_id="<?php echo $item['member_id']; ?>"
                                        data-status="<?php echo $item['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定删除该应用？此操作不可恢复！')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($apps)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="empty-state">
                                        <div class="empty-icon" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-title">暂无应用</div>
                                        <div class="empty-desc">还没有创建任何应用</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </main>

    <!-- Add App Modal -->
    <div class="modal fade" id="addAppModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2" style="color: var(--success);"></i>添加应用
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">应用名称 <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-app position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" class="form-control" name="name" required placeholder="请输入应用名称" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">回调域名</label>
                            <div class="position-relative">
                                <i class="bi bi-globe position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" class="form-control" name="domain" placeholder="例如：www.example.com" style="padding-left: 45px;">
                            </div>
                            <small class="text-muted">填写网站域名，用于回调验证</small>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">所属用户</label>
                            <select class="form-select" name="member_id">
                                <option value="0">-- 不关联用户 --</option>
                                <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['username']); ?> (ID: <?php echo $m['id']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>确定添加
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit App Modal -->
    <div class="modal fade" id="editAppModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2" style="color: var(--primary);"></i>编辑应用
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">应用名称 <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-app position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" class="form-control" name="name" id="edit_name" required placeholder="请输入应用名称" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">回调域名</label>
                            <div class="position-relative">
                                <i class="bi bi-globe position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" class="form-control" name="domain" id="edit_domain" placeholder="例如：www.example.com" style="padding-left: 45px;">
                            </div>
                            <small class="text-muted">填写网站域名，用于回调验证</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">所属用户</label>
                                    <select class="form-select" name="member_id" id="edit_member_id">
                                        <option value="0">-- 不关联用户 --</option>
                                        <?php foreach ($members as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['username']); ?> (ID: <?php echo $m['id']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">状态</label>
                                    <select class="form-select" name="status" id="edit_status">
                                        <option value="1">正常</option>
                                        <option value="0">禁用</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>保存修改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        
        // Edit App Modal
        document.getElementById('editAppModal').addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_name').value = button.getAttribute('data-name');
            document.getElementById('edit_domain').value = button.getAttribute('data-domain');
            document.getElementById('edit_member_id').value = button.getAttribute('data-member_id');
            document.getElementById('edit_status').value = button.getAttribute('data-status');
        });
    </script>
</body>
</html>
