<?php
/**
 * 安装程序
 */

session_start();
define('INSTALL_PATH', __DIR__ . '/');
$step = intval($_GET['step'] ?? 1);
$error = '';

// 检查是否已安装
if (file_exists(INSTALL_PATH . 'install.lock')) {
    exit('系统已安装，如需重新安装请删除 setup/install.lock 文件');
}

// 步骤处理
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $installAction = $_POST['install_action'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE {$dbName}");
        
        // 检测已存在的表
        $existingTables = [];
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $expectedTables = ['qlf_admins', 'qlf_settings', 'qlf_vip_levels', 'qlf_members', 
                          'qlf_apps', 'qlf_accounts', 'qlf_login_logs', 'qlf_orders', 'qlf_money_logs'];
        
        foreach ($tables as $table) {
            if (in_array($table, $expectedTables)) {
                $existingTables[] = $table;
            }
        }
        
        // 如果有已存在的表，根据用户选择处理
        if (!empty($existingTables) && empty($installAction)) {
            // 显示选择界面
            $step = 2.5; // 显示选择操作的中间步骤
            $_SESSION['db_config'] = [
                'host' => $dbHost,
                'port' => $dbPort,
                'user' => $dbUser,
                'password' => $dbPass,
                'dbname' => $dbName,
                'existing_tables' => $existingTables
            ];
        } else {
            // 执行安装操作
            if ($installAction === 'clear') {
                // 清空数据库重新安装 - 删除所有表
                foreach ($existingTables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS {$table}");
                }
                // 执行完整SQL
                $sql = file_get_contents(INSTALL_PATH . 'database.sql');
                $pdo->exec($sql);
            } elseif ($installAction === 'skip') {
                // 跳过安装数据库 - 不执行任何SQL操作
            } else {
                // 只安装不存在的数据表（默认）
                $sql = file_get_contents(INSTALL_PATH . 'database.sql');
                
                // 分割SQL语句
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    // 检查是否是 CREATE TABLE 语句
                    if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?/i', $statement, $matches)) {
                        $tableName = $matches[1];
                        // 只创建不存在的表
                        if (!in_array($tableName, $existingTables)) {
                            $pdo->exec($statement);
                        }
                    } elseif (preg_match('/INSERT\s+INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                        // 插入数据时使用 INSERT IGNORE 避免重复
                        $insertSql = preg_replace('/INSERT\s+INTO/i', 'INSERT IGNORE INTO', $statement);
                        try {
                            $pdo->exec($insertSql);
                        } catch (PDOException $e) {
                            // 忽略重复插入错误
                        }
                    }
                }
            }
            
            // 保存配置文件
            $config = <<<PHP
<?php
return [
    'database' => [
        'host'     => '{$dbHost}',
        'port'     => {$dbPort},
        'user'     => '{$dbUser}',
        'password' => '{$dbPass}',
        'dbname'   => '{$dbName}',
        'charset'  => 'utf8mb4',
        'prefix'   => 'qlf_'
    ],
    'system' => [
        'name'    => 'QQ快捷登录',
        'version' => '1.0.0',
        'debug'   => false
    ]
];
PHP;
            file_put_contents(dirname(__DIR__) . '/core/config.php', $config);
            
            header('Location: index.php?step=3');
            exit;
        }
    } catch (Exception $e) {
        $error = '数据库连接失败: ' . $e->getMessage();
    }
}

// 处理数据库选择操作
if ($step === 2.5 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $installAction = $_POST['install_action'] ?? 'skip';
    
    if (!empty($_SESSION['db_config'])) {
        $dbConfig = $_SESSION['db_config'];
        $dbHost = $dbConfig['host'];
        $dbPort = $dbConfig['port'];
        $dbUser = $dbConfig['user'];
        $dbPass = $dbConfig['password'];
        $dbName = $dbConfig['dbname'];
        $existingTables = $dbConfig['existing_tables'];
        
        try {
            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($installAction === 'clear') {
                // 清空数据库重新安装
                foreach ($existingTables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS {$table}");
                }
                $sql = file_get_contents(INSTALL_PATH . 'database.sql');
                $pdo->exec($sql);
            } elseif ($installAction === 'new') {
                // 只安装不存在的数据表
                $sql = file_get_contents(INSTALL_PATH . 'database.sql');
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?/i', $statement, $matches)) {
                        $tableName = $matches[1];
                        if (!in_array($tableName, $existingTables)) {
                            $pdo->exec($statement);
                        }
                    } elseif (preg_match('/INSERT\s+INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                        $insertSql = preg_replace('/INSERT\s+INTO/i', 'INSERT IGNORE INTO', $statement);
                        try {
                            $pdo->exec($insertSql);
                        } catch (PDOException $e) {
                            // 忽略重复插入错误
                        }
                    }
                }
            }
            // skip 操作不需要处理
            
            // 保存配置文件
            $config = <<<PHP
<?php
return [
    'database' => [
        'host'     => '{$dbHost}',
        'port'     => {$dbPort},
        'user'     => '{$dbUser}',
        'password' => '{$dbPass}',
        'dbname'   => '{$dbName}',
        'charset'  => 'utf8mb4',
        'prefix'   => 'qlf_'
    ],
    'system' => [
        'name'    => 'QQ快捷登录',
        'version' => '1.0.0',
        'debug'   => false
    ]
];
PHP;
            file_put_contents(dirname(__DIR__) . '/core/config.php', $config);
            
            // 清空session
            unset($_SESSION['db_config']);
            
            header('Location: index.php?step=3');
            exit;
        } catch (Exception $e) {
            $error = '数据库操作失败: ' . $e->getMessage();
        }
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = $_POST['site_name'] ?? 'QQ快捷登录';
    $adminUser = $_POST['admin_user'] ?? 'admin';
    $adminPass = $_POST['admin_pass'] ?? '';
    $qqAppId = $_POST['qq_app_id'] ?? '';
    $qqAppKey = $_POST['qq_app_key'] ?? '';
    
    // 加载配置
    $config = require dirname(__DIR__) . '/core/config.php';
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset=utf8mb4",
            $config['database']['user'],
            $config['database']['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $prefix = $config['database']['prefix'];
        
        // 插入设置（使用 INSERT IGNORE 避免重复）
        $settings = [
            ['site_name', $siteName],
            ['site_title', $siteName . ' - 免费版'],
            ['site_desc', '为开发者提供便捷的QQ登录接入服务'],
            ['admin_user', $adminUser],
            ['admin_pass', password_hash($adminPass, PASSWORD_DEFAULT)],
            ['qq_app_id', $qqAppId],
            ['qq_app_key', $qqAppKey]
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO {$prefix}settings (`key`, `value`) VALUES (?, ?)");
        foreach ($settings as $s) {
            $stmt->execute($s);
        }
        
        // 创建锁定文件
        file_put_contents(INSTALL_PATH . 'install.lock', date('Y-m-d H:i:s'));
        
        header('Location: index.php?step=4');
        exit;
    } catch (Exception $e) {
        $error = '安装失败: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装程序 - QQ快捷登录免费版</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .install-box { max-width: 600px; margin: 50px auto; background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; margin: 0 10px; }
        .step.active { background: #0d6efd; color: white; }
        .step.completed { background: #198754; color: white; }
        .install-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .install-option:hover {
            border-color: #0d6efd;
            background: #f8f9ff;
        }
        .install-option.selected {
            border-color: #0d6efd;
            background: #f8f9ff;
        }
        .install-option i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .table-list {
            max-height: 150px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-box">
            <h3 class="text-center mb-2">QQ快捷登录</h3>
            <p class="text-muted text-center mb-4">免费版安装程序</p>
            
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2.5 ? 'completed' : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <h5>环境检测</h5>
            <table class="table">
                <tr>
                    <td>PHP版本 >= 7.1</td>
                    <td><?php echo version_compare(PHP_VERSION, '7.1.0', '>=') ? '<span class="text-success"><i class="bi bi-check-circle"></i> 通过</span>' : '<span class="text-danger"><i class="bi bi-x-circle"></i> 失败</span>'; ?></td>
                </tr>
                <tr>
                    <td>PDO扩展</td>
                    <td><?php echo extension_loaded('pdo') ? '<span class="text-success"><i class="bi bi-check-circle"></i> 通过</span>' : '<span class="text-danger"><i class="bi bi-x-circle"></i> 失败</span>'; ?></td>
                </tr>
                <tr>
                    <td>PDO_MySQL扩展</td>
                    <td><?php echo extension_loaded('pdo_mysql') ? '<span class="text-success"><i class="bi bi-check-circle"></i> 通过</span>' : '<span class="text-danger"><i class="bi bi-x-circle"></i> 失败</span>'; ?></td>
                </tr>
                <tr>
                    <td>cURL扩展</td>
                    <td><?php echo extension_loaded('curl') ? '<span class="text-success"><i class="bi bi-check-circle"></i> 通过</span>' : '<span class="text-danger"><i class="bi bi-x-circle"></i> 失败</span>'; ?></td>
                </tr>
                <tr>
                    <td>目录可写</td>
                    <td><?php echo is_writable(dirname(__DIR__)) ? '<span class="text-success"><i class="bi bi-check-circle"></i> 通过</span>' : '<span class="text-danger"><i class="bi bi-x-circle"></i> 失败</span>'; ?></td>
                </tr>
            </table>
            <div class="text-center">
                <a href="?step=2" class="btn btn-primary">下一步</a>
            </div>

            <?php elseif ($step === 2): ?>
            <h5>数据库配置</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">数据库地址</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">数据库端口</label>
                    <input type="text" name="db_port" class="form-control" value="3306" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">数据库用户名</label>
                    <input type="text" name="db_user" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">数据库密码</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">数据库名称</label>
                    <input type="text" name="db_name" class="form-control" value="qq_login_free" required>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">检测并继续</button>
                </div>
            </form>

            <?php elseif ($step === 2.5): ?>
            <h5 class="mb-3"><i class="bi bi-database-exclamation text-warning"></i> 检测到已有数据表</h5>
            <p class="text-muted">数据库中已存在以下系统数据表：</p>
            <div class="table-list mb-4">
                <?php foreach ($_SESSION['db_config']['existing_tables'] as $table): ?>
                <span class="badge bg-secondary me-1 mb-1"><?php echo $table; ?></span>
                <?php endforeach; ?>
            </div>
            <p class="text-muted mb-3">请选择安装方式：</p>
            
            <form method="POST">
                <input type="hidden" name="install_action" id="install_action" value="">
                
                <div class="install-option" onclick="selectOption('clear')">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-trash text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">清空数据库重新安装</h6>
                            <p class="text-muted small mb-0">删除所有现有数据表，全新安装（数据将丢失）</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="bi bi-circle option-icon" id="icon-clear"></i>
                        </div>
                    </div>
                </div>
                
                <div class="install-option" onclick="selectOption('new')">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-layer-plus text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">只安装不存在的数据表</h6>
                            <p class="text-muted small mb-0">保留现有数据，只创建缺少的表（推荐）</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="bi bi-circle option-icon" id="icon-new"></i>
                        </div>
                    </div>
                </div>
                
                <div class="install-option" onclick="selectOption('skip')">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-skip-forward text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">跳过安装数据库</h6>
                            <p class="text-muted small mb-0">不执行任何数据库操作，只保存配置文件</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="bi bi-circle option-icon" id="icon-skip"></i>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="?step=2" class="btn btn-secondary me-2">返回</a>
                    <button type="submit" class="btn btn-primary" id="continue-btn" disabled>继续</button>
                </div>
            </form>
            
            <script>
                function selectOption(action) {
                    document.getElementById('install_action').value = action;
                    document.getElementById('continue-btn').disabled = false;
                    
                    // 更新选中样式
                    document.querySelectorAll('.install-option').forEach(el => {
                        el.classList.remove('selected');
                    });
                    document.querySelectorAll('.option-icon').forEach(el => {
                        el.classList.remove('bi-check-circle-fill', 'text-primary');
                        el.classList.add('bi-circle');
                    });
                    
                    event.currentTarget.classList.add('selected');
                    document.getElementById('icon-' + action).classList.remove('bi-circle');
                    document.getElementById('icon-' + action).classList.add('bi-check-circle-fill', 'text-primary');
                }
            </script>

            <?php elseif ($step === 3): ?>
            <h5>站点配置</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">网站名称</label>
                    <input type="text" name="site_name" class="form-control" value="QQ快捷登录" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">管理员账号</label>
                    <input type="text" name="admin_user" class="form-control" value="admin" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">管理员密码</label>
                    <input type="password" name="admin_pass" class="form-control" required>
                </div>
                <hr>
                <div class="alert alert-info">
                    <small><i class="bi bi-info-circle me-1"></i>以下配置可在后台修改，也可暂时跳过</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">QQ AppID</label>
                    <input type="text" name="qq_app_id" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">QQ AppKey</label>
                    <input type="text" name="qq_app_key" class="form-control">
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">完成安装</button>
                </div>
            </form>

            <?php elseif ($step === 4): ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle text-success" style="font-size: 64px;"></i>
                <h4 class="mt-3">安装完成！</h4>
                <p class="text-muted">请删除 setup 目录或设置权限保护</p>
                <a href="../" class="btn btn-primary">访问首页</a>
                <a href="../manager/" class="btn btn-outline-primary">进入后台</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
