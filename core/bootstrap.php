<?php
/**
 * 系统引导文件
 */

// 错误报告设置
error_reporting(E_ALL ^ E_NOTICE);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 启动会话
session_start();

// 定义常量
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CORE_PATH', __DIR__ . '/');
define('APP_DEBUG', true);

// 自动加载
spl_autoload_register(function ($class) {
    // 去掉 Core\ 命名空间前缀
    if (strpos($class, 'Core\\') === 0) {
        $class = substr($class, 5);
    }
    $file = CORE_PATH . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 加载辅助函数
require_once CORE_PATH . 'helpers/functions.php';

// 检查安装状态
if (!file_exists(ROOT_PATH . 'setup/install.lock') && 
    !strpos($_SERVER['REQUEST_URI'], 'setup/')) {
    header('Location: ./setup/');
    exit;
}

// 初始化应用
$app = \Core\App::getInstance();
$siteUrl = getBaseUrl();
