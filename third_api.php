<?php
/**
 * 对接同系统API接口
 * 供其他站点对接时调用
 */

require_once __DIR__ . '/core/bootstrap.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 获取操作类型
$act = $_GET['act'] ?? '';

switch ($act) {
    case 'get_userinfo':
        // 获取用户信息 - 供对接站点调用
        handleGetUserInfo();
        break;
        
    case 'verify_sign':
        // 验证签名 - 用于测试对接
        handleVerifySign();
        break;
        
    default:
        jsonResponse(404, '未知操作');
}

/**
 * 处理获取用户信息请求
 */
function handleGetUserInfo()
{
    global $app;
    
    // 获取参数
    $appId = $_POST['app_id'] ?? '';
    $code = $_POST['code'] ?? '';
    $state = $_POST['state'] ?? '';
    $timestamp = $_POST['timestamp'] ?? '';
    $sign = $_POST['sign'] ?? '';
    
    // 验证必填参数
    if (empty($appId) || empty($code) || empty($state) || empty($sign)) {
        jsonResponse(1, '参数不完整');
    }
    
    // 验证时间戳（防止重放攻击，允许5分钟误差）
    if (!empty($timestamp) && abs(time() - intval($timestamp)) > 300) {
        jsonResponse(2, '请求已过期');
    }
    
    // 验证应用
    $appInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND status=1",
        [$appId]
    );
    
    if (!$appInfo) {
        jsonResponse(3, '应用验证失败');
    }
    
    // 验证签名
    $params = [
        'app_id' => $appId,
        'code' => $code,
        'state' => $state,
        'timestamp' => $timestamp
    ];
    
    $expectedSign = generateSign($params, $appInfo['app_key']);
    if (strtoupper($sign) !== $expectedSign) {
        jsonResponse(4, '签名验证失败');
    }
    
    // 从数据库验证授权码
    $authCodeInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('auth_codes')} WHERE code=? AND state=? AND expire_time > NOW()",
        [$code, $state]
    );
    
    if (!$authCodeInfo) {
        jsonResponse(6, '授权码无效或已过期');
    }
    
    // 获取用户信息
    $openId = $authCodeInfo['openid'] ?? '';
    $nickname = $authCodeInfo['nickname'] ?? 'QQ用户';
    $avatar = $authCodeInfo['avatar'] ?? '';
    $gender = $authCodeInfo['gender'] ?? 'unknown';
    
    if (empty($openId)) {
        jsonResponse(7, '用户信息不存在');
    }
    
    // 使用后删除授权码（一次性使用）
    $app->db->delete('auth_codes', 'id=?', [$authCodeInfo['id']]);
    
    // 返回用户信息
    jsonResponse(0, 'success', [
        'openid' => $openId,
        'nickname' => $nickname,
        'avatar' => $avatar,
        'gender' => $gender
    ]);
}

/**
 * 处理签名验证请求
 */
function handleVerifySign()
{
    global $app;
    
    $appId = $_POST['app_id'] ?? '';
    $timestamp = $_POST['timestamp'] ?? time();
    $sign = $_POST['sign'] ?? '';
    
    if (empty($appId) || empty($sign)) {
        jsonResponse(1, '参数不完整');
    }
    
    // 验证应用
    $appInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND status=1",
        [$appId]
    );
    
    if (!$appInfo) {
        jsonResponse(3, '应用验证失败');
    }
    
    // 验证签名
    $params = [
        'app_id' => $appId,
        'timestamp' => $timestamp
    ];
    
    $expectedSign = generateSign($params, $appInfo['app_key']);
    
    if (strtoupper($sign) === $expectedSign) {
        jsonResponse(0, '签名验证通过', [
            'valid' => true,
            'app_name' => $appInfo['name'] ?? ''
        ]);
    } else {
        jsonResponse(4, '签名验证失败', [
            'valid' => false,
            'expected' => $expectedSign
        ]);
    }
}

/**
 * 生成签名
 * @param array $params 参数数组
 * @param string $key 密钥
 * @return string 签名
 */
function generateSign($params, $key)
{
    // 移除签名参数
    unset($params['sign']);
    
    // 按参数名排序
    ksort($params);
    
    // 构建签名字符串
    $string = '';
    foreach ($params as $k => $v) {
        $string .= $k . '=' . $v . '&';
    }
    $string .= 'key=' . $key;
    
    // MD5签名并转大写
    return strtoupper(md5($string));
}
