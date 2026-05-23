<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取管理员信息
$adminId = $_SESSION['admin_id'];
$admin = $app->db->fetch("SELECT * FROM {$app->db->table('admins')} WHERE id=?", [$adminId]);

$action = $_GET['action'] ?? 'list';

// 添加/编辑会员等级
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'level' => intval($_POST['level'] ?? 1),
        'price' => floatval($_POST['price'] ?? 0),
        'app_limit' => intval($_POST['app_limit'] ?? 0),
        'login_limit' => intval($_POST['login_limit'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'sort' => intval($_POST['sort'] ?? 0),
        'status' => intval($_POST['status'] ?? 1)
    ];
    
    if ($id > 0) {
        $app->db->update('vip_levels', $data, 'id=?', [$id]);
    } else {
        $app->db->insert('vip_levels', $data);
    }
    
    header('Location: vip_levels.php');
    exit;
}

// 删除会员等级
if ($action === 'delete' && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $app->db->delete('vip_levels', 'id=?', [$id]);
    header('Location: vip_levels.php');
    exit;
}

// 获取会员等级列表
$levels = $app->db->fetchAll(
    "SELECT * FROM {$app->db->table('vip_levels')} ORDER BY level ASC"
);

// 编辑模式
$editLevel = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $editLevel = $app->db->fetch(
        "SELECT * FROM {$app->db->table('vip_levels')} WHERE id=?",
        [intval($_GET['id'])]
    );
}

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员等级 - <?php echo $siteName; ?></title>
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
                    <a class="nav-link active" href="vip_levels.php">
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
            <h1 class="page-title">会员等级管理</h1>
            <div class="page-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal" onclick="addNew()">
                    <i class="bi bi-plus-lg"></i> 添加等级
                </button>
            </div>
        </div>

        <!-- VIP Levels Grid -->
        <div class="row g-4">
            <?php foreach ($levels as $level): ?>
            <div class="col-md-6 col-lg-4">
                <div class="content-card h-100" style="transition: all 0.3s ease;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3" style="width: 50px; height: 50px; font-size: 1.25rem; background: <?php echo $level['level'] > 0 ? 'linear-gradient(135deg, #f59e0b, #fbbf24)' : 'linear-gradient(135deg, #6b7280, #9ca3af)'; ?>;">
                                    <?php if ($level['level'] > 0): ?>
                                        <i class="bi bi-gem"></i>
                                    <?php else: ?>
                                        <i class="bi bi-person"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($level['name']); ?></h5>
                                    <?php if ($level['level'] > 0): ?>
                                        <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white;">
                                            VIP <?php echo $level['level']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">免费用户</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary" onclick="editLevel(<?php echo htmlspecialchars(json_encode($level)); ?>)" title="编辑">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                                <span class="text-muted"><i class="bi bi-currency-yen me-2"></i>价格</span>
                                <span class="fw-bold fs-5" style="color: var(--success);">¥<?php echo number_format($level['price'], 2); ?><small class="text-muted fs-6">/月</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                                <span class="text-muted"><i class="bi bi-grid me-2"></i>应用限制</span>
                                <span class="fw-semibold"><?php echo $level['app_limit'] > 0 ? $level['app_limit'] . ' 个' : '不限'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-2">
                                <span class="text-muted"><i class="bi bi-lightning me-2"></i>登录限制</span>
                                <span class="fw-semibold"><?php echo $level['login_limit'] > 0 ? $level['login_limit'] . ' 次/月' : '不限'; ?></span>
                            </div>
                        </div>
                        
                        <?php if ($level['description']): ?>
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($level['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge <?php echo $level['status'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $level['status'] ? '启用' : '禁用'; ?>
                            </span>
                            <?php if ($level['level'] > 0): ?>
                            <a href="?action=delete&id=<?php echo $level['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定删除该等级？')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($levels)): ?>
        <div class="content-card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-gem"></i>
                    </div>
                    <div class="empty-title">暂无会员等级</div>
                    <div class="empty-desc">点击右上角按钮添加会员等级</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="?action=save">
                    <input type="hidden" name="id" id="level_id" value="0">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-gem me-2" style="color: var(--warning);"></i>
                            <span id="modal_title">会员等级</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">等级名称</label>
                            <div class="position-relative">
                                <i class="bi bi-tag position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                                <input type="text" name="name" id="name" class="form-control" required placeholder="例如：黄金会员" style="padding-left: 45px;">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">等级数值</label>
                                    <input type="number" name="level" id="level" class="form-control" required min="0">
                                    <small class="text-muted">0为免费用户</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">价格/月</label>
                                    <div class="position-relative">
                                        <span class="position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;">¥</span>
                                        <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required style="padding-left: 35px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">应用数量限制</label>
                                    <input type="number" name="app_limit" id="app_limit" class="form-control" min="0">
                                    <small class="text-muted">0表示不限制</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">登录次数限制</label>
                                    <input type="number" name="login_limit" id="login_limit" class="form-control" min="0">
                                    <small class="text-muted">0表示不限制</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">描述</label>
                            <textarea name="description" id="description" class="form-control" rows="2" placeholder="简短描述该等级的特权..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-0">
                                    <label class="form-label">排序</label>
                                    <input type="number" name="sort" id="sort" class="form-control" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-0">
                                    <label class="form-label">状态</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="1">启用</option>
                                        <option value="0">禁用</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>保存
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
        
        // Level functions
        function addNew() {
            document.getElementById('modal_title').textContent = '添加等级';
            document.getElementById('level_id').value = '0';
            document.getElementById('name').value = '';
            document.getElementById('level').value = '1';
            document.getElementById('price').value = '9.90';
            document.getElementById('app_limit').value = '5';
            document.getElementById('login_limit').value = '0';
            document.getElementById('description').value = '';
            document.getElementById('sort').value = '0';
            document.getElementById('status').value = '1';
        }
        
        function editLevel(data) {
            document.getElementById('modal_title').textContent = '编辑等级';
            document.getElementById('level_id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('level').value = data.level;
            document.getElementById('price').value = data.price;
            document.getElementById('app_limit').value = data.app_limit;
            document.getElementById('login_limit').value = data.login_limit;
            document.getElementById('description').value = data.description;
            document.getElementById('sort').value = data.sort;
            document.getElementById('status').value = data.status;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>
