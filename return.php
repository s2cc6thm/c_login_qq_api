<?php
/**
 * 快捷登录回调处理
 * 处理QQ登录成功后的回调，并更新登录记录
 */

require_once __DIR__ . '/core/bootstrap.php';

use Core\Lib\QqAuth;

// 获取参数
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code)) {
    showAlert('登录失败：授权码为空');
}

// 解码state获取登录类型和记录ID
$decodedState = authcodeDecode($state);
$parts = explode('||||', $decodedState);

if (count($parts) !== 2) {
    showAlert('登录失败：状态验证失败');
}

$type = $parts[0];
$logId = intval($parts[1]);

if (empty($type) || $logId <= 0) {
    showAlert('登录失败：参数解析错误');
}

// 查询登录记录
$record = $app->db->fetch(
    "SELECT * FROM {$app->db->table('login_records')} WHERE id=? AND status=0",
    [$logId]
);

if (!$record) {
    showAlert('登录失败：登录记录不存在或已过期');
}

try {
    // 获取QQ登录配置
    $qqAppId = $app->getSetting('qq_app_id');
    $qqAppKey = $app->getSetting('qq_app_key');
    
    if (empty($qqAppId) || empty($qqAppKey)) {
        throw new \Exception('平台配置不完整');
    }
    
    // 调用QQ登录获取用户信息
    $qqAuth = new QqAuth($qqAppId, $qqAppKey, $siteUrl . 'return.php');
    $userInfo = $qqAuth->callback($code);
    
    $clientIp = getClientIp();
    $now = date('Y-m-d H:i:s');
    
    // 检查用户是否已存在
    $existing = $app->db->fetch(
        "SELECT * FROM {$app->db->table('accounts')} 
         WHERE app_id=? AND openid=?",
        [$record['app_id'], $userInfo['openid']]
    );
    
    // 生成access_token
    $accessToken = md5($userInfo['openid'] . time() . rand(1000, 9999));
    
    if ($existing) {
        // 更新登录信息
        $app->db->update('accounts', [
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'gender' => $userInfo['gender'],
            'token' => $accessToken,
            'login_ip' => $clientIp,
            'login_time' => $now,
            'login_count' => $existing['login_count'] + 1
        ], 'id=?', [$existing['id']]);
    } else {
        // 创建新用户
        $app->db->insert('accounts', [
            'app_id' => $record['app_id'],
            'openid' => $userInfo['openid'],
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'gender' => $userInfo['gender'],
            'token' => $accessToken,
            'login_ip' => $clientIp,
            'login_time' => $now,
            'create_time' => $now,
            'login_count' => 1
        ]);
    }
    
    // 记录登录日志
    $app->db->insert('login_logs', [
        'app_id' => $record['app_id'],
        'openid' => $userInfo['openid'],
        'nickname' => $userInfo['nickname'],
        'ip' => $clientIp,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'create_time' => $now
    ]);
    
    // 更新登录记录状态
    $app->db->update('login_records', [
        'openid' => $userInfo['openid'],
        'status' => 1,
        'ip' => $clientIp,
        'update_time' => $now
    ], 'id=?', [$logId]);
    
    // 跳转到原始请求的回调地址
    $redirectUri = $record['redirect'];
    $sep = strpos($redirectUri, '?') === false ? '?' : '&';
    
    // 构建回调URL参数
    $callbackParams = [
        'type' => $type,
        'code' => $record['code'],
        'state' => $record['state']
    ];
    
    // 如果redirect_uri中已经有type参数，则不再添加
    if (strpos($redirectUri, 'type=') !== false) {
        unset($callbackParams['type']);
    }
    
    $redirectUrl = $redirectUri . $sep . http_build_query($callbackParams);
    
    redirect($redirectUrl);
    
} catch (\Exception $e) {
    showAlert('登录失败：' . $e->getMessage());
}

/**
 * 解码加密字符串（类似彩虹版的authcode2）
 */
function authcodeDecode($string)
{
    $fixed = str_replace(['-', '_'], ['+', '/'], $string);
    $mod4 = strlen($fixed) % 4;
    if ($mod4) {
        $fixed .= str_repeat('=', 4 - $mod4);
    }
    return base64_decode($fixed);
}
