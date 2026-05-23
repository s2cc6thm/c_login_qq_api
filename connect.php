<?php
/**
 * QQ登录连接入口
 * 用于第三方网站跳转过来的登录请求
 * 支持快捷登录接口：act=login, act=callback, act=query
 */

require_once __DIR__ . '/core/bootstrap.php';

use Core\Lib\QqAuth;
use Core\Lib\ThirdAuth;

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 获取操作类型
$act = $_GET['act'] ?? '';

// 根据操作类型处理不同的请求
switch ($act) {
    case 'login':
        // 快捷登录接口 - 获取登录跳转地址
        handleLogin();
        break;
    case 'callback':
        // 快捷登录回调接口 - 获取用户信息
        handleCallback();
        break;
    case 'query':
        // 查询用户信息接口
        handleQuery();
        break;
    default:
        // 默认处理 - 原有逻辑（兼容旧版跳转方式）
        handleDefault();
        break;
}

/**
 * 处理快捷登录请求
 * 请求参数：appid, appkey, type, redirect_uri, state
 */
function handleLogin()
{
    global $app, $siteUrl;
    
    $appId = $_GET['appid'] ?? '';
    $appKey = $_GET['appkey'] ?? '';
    $type = $_GET['type'] ?? '';
    $redirectUri = $_GET['redirect_uri'] ?? '';
    $state = $_GET['state'] ?? '';
    
    // 验证必填参数
    if (empty($appId) || empty($appKey) || empty($type) || empty($redirectUri)) {
        jsonError(-1, '参数不完整');
    }
    
    // 验证应用
    $appInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND app_key=? AND status=1",
        [$appId, $appKey]
    );
    
    if (!$appInfo) {
        jsonError(-1, '应用验证失败');
    }
    
    // 只支持QQ登录
    if ($type !== 'qq') {
        jsonError(-1, '不支持的登录方式');
    }
    
    // 生成登录记录
    $code = strtoupper(md5(uniqid(rand(), true)));
    $now = date('Y-m-d H:i:s');
    
    // 解析域名
    $urlParts = parse_url($redirectUri);
    $domain = $urlParts['host'] ?? '';
    
    // 保存登录记录到数据库
    $logId = $app->db->insert('login_records', [
        'code' => $code,
        'app_id' => $appInfo['id'],
        'type' => $type,
        'domain' => $domain,
        'redirect' => $redirectUri,
        'state' => $state,
        'create_time' => $now,
        'status' => 0
    ]);
    
    // 生成state
    $loginState = authcodeEncode($type . '||||' . $logId);
    
    // 获取登录模式
    $settingRow = $app->db->fetch("SELECT `value` FROM {$app->db->table('settings')} WHERE `key`=?", ['qq_login_mode']);
    $loginMode = $settingRow['value'] ?? 'official';
    
    if ($loginMode === 'third') {
        // 对接同系统模式 - 调用彩虹版接口
        $settingRow = $app->db->fetch("SELECT `value` FROM {$app->db->table('settings')} WHERE `key`=?", ['third_api_url']);
        $thirdApiUrl = $settingRow['value'] ?? '';
        $settingRow = $app->db->fetch("SELECT `value` FROM {$app->db->table('settings')} WHERE `key`=?", ['third_app_id']);
        $thirdAppId = $settingRow['value'] ?? '';
        $settingRow = $app->db->fetch("SELECT `value` FROM {$app->db->table('settings')} WHERE `key`=?", ['third_app_key']);
        $thirdAppKey = $settingRow['value'] ?? '';
        
        if (empty($thirdApiUrl) || empty($thirdAppId) || empty($thirdAppKey)) {
            jsonError(-1, '对接配置未完成，请检查第三方对接设置');
        }
        
        // 调用彩虹版的快捷登录接口
        $param = [
            'act' => 'login',
            'appid' => $thirdAppId,
            'appkey' => $thirdAppKey,
            'type' => $type,
            'redirect_uri' => $siteUrl . 'return.php',
            'state' => $loginState
        ];
        $url = rtrim($thirdApiUrl, '/') . '/connect.php?' . http_build_query($param);
        
        $response = file_get_contents($url);
        $arr = json_decode($response, true);
        
        if (!is_array($arr) || !isset($arr['code'])) {
            jsonError(-1, '对接站点接口返回格式错误');
        }
        
        if ($arr['code'] !== 0) {
            jsonError(-1, '对接站点返回错误：' . ($arr['msg'] ?? '未知错误'));
        }
        
        $loginUrl = $arr['url'];
        
    } else {
        // 官方平台模式 - 直接调用QQ登录
        $settingRow = $app->db->fetch("SELECT `value` FROM {$app->db->table('settings')} WHERE `key`=?", ['qq_app_id']);
        $qqAppId = $settingRow['value'] ?? '';
        $settingRow = $app->db->fetch("SELECT `value` FROM {$app->db->table('settings')} WHERE `key`=?", ['qq_app_key']);
        $qqAppKey = $settingRow['value'] ?? '';
        
        if (empty($qqAppId) || empty($qqAppKey)) {
            jsonError(-1, '平台未配置，请检查设置中的QQ登录配置');
        }
        
        // 生成QQ登录URL
        $qqAuth = new QqAuth($qqAppId, $qqAppKey, $siteUrl . 'return.php');
        $loginUrl = $qqAuth->getAuthUrl($loginState);
    }
    
    // 返回成功响应（彩虹版格式）
    $result = [
        'code' => 0,
        'msg' => 'succ',
        'type' => $type,
        'url' => $loginUrl
    ];
    
    echo json_encode($result);
    exit;
}

/**
 * 处理登录回调请求
 * 请求参数：appid, appkey, type, code
 */
function handleCallback()
{
    global $app;
    
    $appId = $_GET['appid'] ?? '';
    $appKey = $_GET['appkey'] ?? '';
    $type = $_GET['type'] ?? '';
    $code = $_GET['code'] ?? '';
    
    // 验证必填参数
    if (empty($appId) || empty($appKey) || empty($type) || empty($code)) {
        jsonError(-1, '参数不完整');
    }
    
    // 验证应用
    $appInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND app_key=? AND status=1",
        [$appId, $appKey]
    );
    
    if (!$appInfo) {
        jsonError(-1, '应用验证失败');
    }
    
    // 查询登录记录
    $record = $app->db->fetch(
        "SELECT * FROM {$app->db->table('login_records')} WHERE code=? AND app_id=? ORDER BY id DESC LIMIT 1",
        [$code, $appInfo['id']]
    );
    
    if (!$record) {
        jsonError(-1, '登录记录不存在');
    }
    
    // 检查是否已完成登录
    if ($record['status'] == 1) {
        // 登录已完成，查询用户信息
        if (strtotime($record['update_time']) < time() - 60) {
            jsonError(-1, 'CODE已失效');
        }
        
        // 查询用户账号信息
        $account = $app->db->fetch(
            "SELECT * FROM {$app->db->table('accounts')} WHERE app_id=? AND openid=? ORDER BY id DESC LIMIT 1",
            [$appInfo['id'], $record['openid']]
        );
        
        if ($account) {
            $result = [
                'code' => 0,
                'msg' => 'succ',
                'type' => $record['type'],
                'access_token' => $account['token'] ?? md5($account['openid'] . time()),
                'social_uid' => $account['openid'],
                'faceimg' => $account['avatar'],
                'nickname' => $account['nickname'],
                'location' => $account['location'] ?? '',
                'gender' => $account['gender'],
                'ip' => $record['ip'] ?? getClientIp()
            ];
            echo json_encode($result);
            exit;
        }
    }
    
    // 登录未完成，返回等待状态（code=2表示等待，彩虹版格式）
    jsonError(2, '等待登录完成');
}

/**
 * 处理查询用户信息请求
 * 请求参数：appid, appkey, type, social_uid
 */
function handleQuery()
{
    global $app;
    
    $appId = $_GET['appid'] ?? '';
    $appKey = $_GET['appkey'] ?? '';
    $type = $_GET['type'] ?? '';
    $socialUid = $_GET['social_uid'] ?? '';
    
    // 验证必填参数
    if (empty($appId) || empty($appKey) || empty($type) || empty($socialUid)) {
        jsonError(-1, '参数不完整');
    }
    
    // 验证应用
    $appInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND app_key=? AND status=1",
        [$appId, $appKey]
    );
    
    if (!$appInfo) {
        jsonError(-1, '应用验证失败');
    }
    
    // 查询用户账号
    $account = $app->db->fetch(
        "SELECT * FROM {$app->db->table('accounts')} WHERE app_id=? AND openid=? LIMIT 1",
        [$appInfo['id'], $socialUid]
    );
    
    if (!$account) {
        jsonError(-1, '用户不存在');
    }
    
    // 返回用户信息（彩虹版格式）
    $result = [
        'code' => 0,
        'msg' => 'succ',
        'type' => $type,
        'social_uid' => $account['openid'],
        'access_token' => $account['token'] ?? md5($account['openid'] . time()),
        'nickname' => $account['nickname'],
        'faceimg' => $account['avatar'],
        'location' => $account['location'] ?? '',
        'gender' => $account['gender'],
        'ip' => $account['login_ip'] ?? ''
    ];
    
    echo json_encode($result);
    exit;
}

/**
 * 默认处理 - 原有逻辑
 */
function handleDefault()
{
    global $app, $siteUrl;
    
    // 恢复非JSON响应（用于页面跳转）
    header('Content-Type: text/html; charset=utf-8');
    
    $appId = $_GET['app_id'] ?? '';
    $returnUrl = $_GET['return_url'] ?? '';
    $sign = $_GET['sign'] ?? '';
    
    if (empty($appId)) {
        showAlert('参数错误：缺少应用ID');
    }
    
    // 验证应用
    $appInfo = $app->db->fetch(
        "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND status=1",
        [$appId]
    );
    
    if (!$appInfo) {
        showAlert('应用验证失败');
    }
    
    // 验证签名（防止非法调用）
    if (!empty($sign)) {
        $params = $_GET;
        unset($params['sign']);
        ksort($params);
        
        $string = '';
        foreach ($params as $k => $v) {
            $string .= $k . '=' . $v . '&';
        }
        $string .= 'key=' . $appInfo['app_key'];
        
        $expectedSign = strtoupper(md5($string));
        
        if ($sign !== $expectedSign) {
            showAlert('签名验证失败');
        }
    }
    
    // 保存登录会话
    $loginId = generateRandom(16);
    $_SESSION['qq_login_id'] = $loginId;
    $_SESSION['qq_login_app'] = $appInfo['id'];
    $_SESSION['qq_return_url'] = $returnUrl;
    $_SESSION['qq_is_third_party'] = !empty($sign);
    
    // 获取登录模式
    $loginMode = $app->getSetting('qq_login_mode', 'official');
    
    if ($loginMode === 'third') {
        // 对接同系统模式
        $thirdApiUrl = $app->getSetting('third_api_url');
        $thirdAppId = $app->getSetting('third_app_id');
        $thirdAppKey = $app->getSetting('third_app_key');
        
        if (empty($thirdApiUrl) || empty($thirdAppId) || empty($thirdAppKey)) {
            showAlert('对接配置未完成，请联系管理员');
        }
        
        $_SESSION['qq_login_mode'] = 'third';
        
        $thirdAuth = new ThirdAuth($thirdApiUrl, $thirdAppId, $thirdAppKey, $siteUrl . 'callback.php');
        $loginUrl = $thirdAuth->getAuthUrl($loginId);
        
        redirect($loginUrl);
    } else {
        // 官方平台模式
        $qqAppId = $app->getSetting('qq_app_id');
        $qqAppKey = $app->getSetting('qq_app_key');
        
        if (empty($qqAppId) || empty($qqAppKey)) {
            showAlert('平台未配置，请联系管理员');
        }
        
        $_SESSION['qq_login_mode'] = 'official';
        
        $qqAuth = new QqAuth($qqAppId, $qqAppKey, $siteUrl . 'callback.php');
        $loginUrl = $qqAuth->getAuthUrl($loginId);
        
        redirect($loginUrl);
    }
}

/**
 * 生成加密字符串（类似彩虹版的authcode2）
 */
function authcodeEncode($string)
{
    $key = defined('SYS_KEY') ? SYS_KEY : 'default_key';
    $encoded = base64_encode($string);
    return str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
}

/**
 * JSON错误响应（彩虹版格式，不含data字段）
 */
function jsonError($code, $msg)
{
    echo json_encode([
        'code' => $code,
        'msg' => $msg
    ]);
    exit;
}
