<?php
/**
 * QQ登录回调处理
 */

require_once __DIR__ . '/core/bootstrap.php';

use Core\Lib\QqAuth;
use Core\Lib\ThirdAuth;

// 获取参数
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code)) {
    showAlert('登录失败：授权码为空');
}

// 验证state
if (empty($_SESSION['qq_login_id']) || $_SESSION['qq_login_id'] !== $state) {
    showAlert('登录失败：安全验证失败');
}

$appId = $_SESSION['qq_login_app'];
$returnUrl = $_SESSION['qq_return_url'] ?? '';
$loginMode = $_SESSION['qq_login_mode'] ?? 'official';

// 获取应用信息
$appInfo = $app->db->fetch(
    "SELECT * FROM {$app->db->table('apps')} WHERE id=?",
    [$appId]
);

if (!$appInfo) {
    showAlert('登录失败：应用不存在');
}

try {
    if ($loginMode === 'third') {
        // 对接同系统模式 - 本系统作为对接方
        $thirdApiUrl = $app->getSetting('third_api_url');
        $thirdAppId = $app->getSetting('third_app_id');
        $thirdAppKey = $app->getSetting('third_app_key');
        
        if (empty($thirdApiUrl) || empty($thirdAppId) || empty($thirdAppKey)) {
            throw new \Exception('对接配置不完整');
        }
        
        // 调用对接系统获取用户信息
        $thirdAuth = new ThirdAuth($thirdApiUrl, $thirdAppId, $thirdAppKey, $siteUrl . 'callback.php');
        $userInfo = $thirdAuth->callback($code, $state);
        
        // 处理用户登录
        processUserLogin($appId, $userInfo, $returnUrl);
        
    } else {
        // 官方平台模式 - 本系统作为被对接方或直接登录
        $qqAppId = $app->getSetting('qq_app_id');
        $qqAppKey = $app->getSetting('qq_app_key');
        
        if (empty($qqAppId) || empty($qqAppKey)) {
            throw new \Exception('平台配置不完整');
        }
        
        // 调用QQ登录
        $qqAuth = new QqAuth($qqAppId, $qqAppKey, $siteUrl . 'callback.php');
        $userInfo = $qqAuth->callback($code);
        
        // 生成授权码（用于被其他系统对接时获取用户信息）
        $authCode = generateRandom(32);
        $state = $_SESSION['qq_login_id'] ?? '';
        
        // 保存授权信息到数据库，供third_api.php使用
        $now = date('Y-m-d H:i:s');
        $expireTime = date('Y-m-d H:i:s', time() + 300); // 5分钟有效期
        
        $app->db->insert('auth_codes', [
            'code' => $authCode,
            'app_id' => $appId,
            'openid' => $userInfo['openid'],
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'gender' => $userInfo['gender'],
            'state' => $state,
            'expire_time' => $expireTime,
            'create_time' => $now
        ]);
        
        // 处理用户登录
        processUserLogin($appId, $userInfo, $returnUrl, $authCode);
    }
    
} catch (\Exception $e) {
    showAlert('登录失败：' . $e->getMessage());
}

/**
 * 处理用户登录
 * @param int $appId 应用ID
 * @param array $userInfo 用户信息
 * @param string $returnUrl 返回URL
 * @param string|null $authCode 授权码（用于被对接场景）
 */
function processUserLogin($appId, $userInfo, $returnUrl, $authCode = null)
{
    global $app;
    
    // 检查用户是否已存在
    $existing = $app->db->fetch(
        "SELECT * FROM {$app->db->table('accounts')} 
         WHERE app_id=? AND openid=?",
        [$appId, $userInfo['openid']]
    );
    
    $clientIp = getClientIp();
    $now = date('Y-m-d H:i:s');
    
    if ($existing) {
        // 更新登录信息
        $app->db->update('accounts', [
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'gender' => $userInfo['gender'],
            'login_ip' => $clientIp,
            'login_time' => $now,
            'login_count' => $existing['login_count'] + 1
        ], 'id=?', [$existing['id']]);
    } else {
        // 创建新用户
        $app->db->insert('accounts', [
            'app_id' => $appId,
            'openid' => $userInfo['openid'],
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'gender' => $userInfo['gender'],
            'login_ip' => $clientIp,
            'login_time' => $now,
            'create_time' => $now,
            'login_count' => 1
        ]);
    }
    
    // 记录登录日志
    $app->db->insert('login_logs', [
        'app_id' => $appId,
        'openid' => $userInfo['openid'],
        'nickname' => $userInfo['nickname'],
        'ip' => $clientIp,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'create_time' => $now
    ]);
    
    // 清理会话
    unset($_SESSION['qq_login_id']);
    unset($_SESSION['qq_login_app']);
    unset($_SESSION['qq_return_url']);
    unset($_SESSION['qq_login_mode']);
    
    // 跳转回应用
    if (!empty($returnUrl)) {
        $sep = strpos($returnUrl, '?') === false ? '?' : '&';
        
        // 如果提供了授权码，则添加授权码参数（用于被对接场景）
        if ($authCode) {
            $redirectUrl = $returnUrl . $sep . 'code=' . urlencode($authCode) . 
                           '&state=' . urlencode(session_id());
        } else {
            $redirectUrl = $returnUrl . $sep . 'openid=' . urlencode($userInfo['openid']) . 
                           '&nickname=' . urlencode($userInfo['nickname']);
        }
        
        redirect($redirectUrl);
    } else {
        showAlert('登录成功！', './');
    }
}
