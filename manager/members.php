<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$adminId = $_SESSION['admin_id'];
$admin = $app->db->fetch("SELECT * FROM {$app->db->table('admins')} WHERE id=?", [$adminId]);

// 添加用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $vipLevel = intval($_POST['vip_level'] ?? 0);
    
    if (empty($username)) {
        $error = '用户名不能为空';
    } elseif (empty($password)) {
        $error = '密码不能为空';
    } else {
        // 检查用户名是否已存在
        $exists = $app->db->fetch("SELECT id FROM {$app->db->table('members')} WHERE username=?", [$username]);
        if ($exists) {
            $error = '用户名已存在';
        } else {
            // 检查邮箱是否已存在
            if (!empty($email)) {
                $emailExists = $app->db->fetch("SELECT id FROM {$app->db->table('members')} WHERE email=?", [$email]);
                if ($emailExists) {
                    $error = '邮箱已被使用';
                }
            }
        }
        
        if (empty($error)) {
            $data = [
                'username' => $username,
                'email' => $email ?: null,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'money' => 0.00,
                'vip_level' => $vipLevel,
                'create_time' => date('Y-m-d H:i:s'),
                'status' => 1
            ];
            
            $app->db->insert('members', $data);
            $success = '用户添加成功！用户名: ' . htmlspecialchars($username);
        }
    }
}

// 删除用户
if (!empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $app->db->delete('members', 'id=?', [$id]);
    header('Location: members.php');
    exit;
}

// 编辑用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $vipLevel = intval($_POST['vip_level'] ?? 0);
    $money = floatval($_POST['money'] ?? 0);
    $status = intval($_POST['status'] ?? 1);
    
    if (empty($username)) {
        $error = '用户名不能为空';
    } else {
        $exists = $app->db->fetch("SELECT id FROM {$app->db->table('members')} WHERE username=? AND id!=?", [$username, $id]);
        if ($exists) {
            $error = '用户名已存在';
        } else {
            if (!empty($email)) {
                $emailExists = $app->db->fetch("SELECT id FROM {$app->db->table('members')} WHERE email=? AND id!=?", [$email, $id]);
                if ($emailExists) {
                    $error = '邮箱已被使用';
                }
            }
        }
        
        if (empty($error)) {
            $data = [
                'username' => $username,
                'email' => $email ?: null,
                'vip_level' => $vipLevel,
                'money' => $money,
                'status' => $status
            ];
            
            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $app->db->update('members', $data, 'id=?', [$id]);
            $success = '用户信息已更新！';
        }
    }
}

// 获取用户列表
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$members = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('members')} ORDER BY id DESC LIMIT {$offset}, {$perPage}"
);

$total = $app->db->count('members');
$totalPages = ceil($total / $perPage);

// 获取VIP等级列表
$vipLevels = $app->db->fetchAll("SELECT * FROM {$app->db->table('vip_levels')} WHERE status=1 ORDER BY level ASC");

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - <?php echo $siteName; ?></title>
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
                    <a class="nav-link active" href="members.php">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">用户管理</h1>
            <div class="page-actions">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="bi bi-person-plus"></i> 添加用户
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

        <!-- Members Card -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-people me-2"></i>用户列表
                    <span class="badge badge-secondary ms-2">共 <?php echo $total; ?> 人</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户信息</th>
                                <th>邮箱</th>
                                <th>余额</th>
                                <th>VIP等级</th>
                                <th>注册时间</th>
                                <th>最后登录</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td><strong>#<?php echo $m['id']; ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                            <?php echo mb_substr($m['username'], 0, 1); ?>
                                        </div>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($m['username']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($m['email']): ?>
                                        <i class="bi bi-envelope me-1 text-muted"></i>
                                        <?php echo $m['email']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold" style="color: var(--success);">
                                        ¥<?php echo number_format($m['money'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($m['vip_level'] > 0): ?>
                                        <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white;">
                                            <i class="bi bi-gem me-1"></i>VIP<?php echo $m['vip_level']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">普通用户</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="bi bi-calendar me-1 text-muted"></i>
                                    <?php echo $m['create_time']; ?>
                                </td>
                                <td>
                                    <?php if ($m['login_time']): ?>
                                        <i class="bi bi-clock-history me-1 text-muted"></i>
                                        <?php echo $m['login_time']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editMemberModal" 
                                        data-id="<?php echo $m['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($m['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($m['email'] ?? ''); ?>"
                                        data-vip_level="<?php echo $m['vip_level']; ?>"
                                        data-money="<?php echo $m['money']; ?>"
                                        data-status="<?php echo $m['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定删除该用户？此操作不可恢复！')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="empty-state">
                                        <div class="empty-icon" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-title">暂无用户</div>
                                        <div class="empty-desc">还没有注册用户</div>
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

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2" style="color: var(--primary);"></i>添加用户
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">用户名 <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-person position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" class="form-control" name="username" required placeholder="请输入用户名" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">邮箱</label>
                            <div class="position-relative">
                                <i class="bi bi-envelope position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="email" class="form-control" name="email" placeholder="请输入邮箱（可选）" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">密码 <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-lock position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="password" class="form-control" name="password" required placeholder="请输入密码" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">VIP等级</label>
                            <select class="form-select" name="vip_level">
                                <option value="0">普通用户</option>
                                <?php foreach ($vipLevels as $level): ?>
                                <option value="<?php echo $level['level']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>确定添加
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2" style="color: var(--primary);"></i>编辑用户
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">用户名 <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-person position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" class="form-control" name="username" id="edit_username" required placeholder="请输入用户名" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">邮箱</label>
                            <div class="position-relative">
                                <i class="bi bi-envelope position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="email" class="form-control" name="email" id="edit_email" placeholder="请输入邮箱（可选）" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">新密码</label>
                            <div class="position-relative">
                                <i class="bi bi-lock position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="password" class="form-control" name="password" placeholder="留空则不修改密码" style="padding-left: 45px;">
                            </div>
                            <small class="text-muted">不修改密码请留空</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">VIP等级</label>
                                    <select class="form-select" name="vip_level" id="edit_vip_level">
                                        <option value="0">普通用户</option>
                                        <?php foreach ($vipLevels as $level): ?>
                                        <option value="<?php echo $level['level']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">余额</label>
                                    <div class="position-relative">
                                        <i class="bi bi-currency-dollar position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                        <input type="number" class="form-control" name="money" id="edit_money" step="0.01" min="0" placeholder="0.00" style="padding-left: 45px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">状态</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="1">正常</option>
                                <option value="0">禁用</option>
                            </select>
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
        
        // Edit Member Modal
        document.getElementById('editMemberModal').addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_username').value = button.getAttribute('data-username');
            document.getElementById('edit_email').value = button.getAttribute('data-email');
            document.getElementById('edit_vip_level').value = button.getAttribute('data-vip_level');
            document.getElementById('edit_money').value = button.getAttribute('data-money');
            document.getElementById('edit_status').value = button.getAttribute('data-status');
        });
    </script>
</body>
</html>
