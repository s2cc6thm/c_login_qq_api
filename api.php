<?php
/**
 * API接口 - 登录入口
 */

require_once __DIR__ . '/core/bootstrap.php';

use Core\Lib\QqAuth;
use Core\Lib\ThirdAuth;

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        // 获取登录地址
        $appId = $_GET['app_id'] ?? '';
        $appKey = $_GET['app_key'] ?? '';
        $returnUrl = $_GET['return_url'] ?? '';
        
        if (empty($appId) || empty($appKey)) {
            jsonResponse(1, '缺少应用ID或密钥');
        }
        
        // 验证应用
        $appInfo = $app->db->fetch(
            "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND app_key=? AND status=1",
            [$appId, $appKey]
        );
        
        if (!$appInfo) {
            jsonResponse(2, '应用验证失败');
        }
        
        // 保存登录会话
        $loginId = generateRandom(16);
        $_SESSION['qq_login_id'] = $loginId;
        $_SESSION['qq_login_app'] = $appInfo['id'];
        $_SESSION['qq_return_url'] = $returnUrl;
        
        // 获取登录模式
        $loginMode = $app->getSetting('qq_login_mode', 'official');
        
        if ($loginMode === 'third') {
            // 对接同系统模式
            $thirdApiUrl = $app->getSetting('third_api_url');
            $thirdAppId = $app->getSetting('third_app_id');
            $thirdAppKey = $app->getSetting('third_app_key');
            
            if (empty($thirdApiUrl) || empty($thirdAppId) || empty($thirdAppKey)) {
                jsonResponse(3, '对接配置未完成');
            }
            
            $_SESSION['qq_login_mode'] = 'third';
            
            // 生成对接系统的跳转URL
            $thirdAuth = new ThirdAuth($thirdApiUrl, $thirdAppId, $thirdAppKey, $siteUrl . 'callback.php');
            $loginUrl = $thirdAuth->getAuthUrl($loginId);
            
        } else {
            // 官方平台模式
            $qqAppId = $app->getSetting('qq_app_id');
            $qqAppKey = $app->getSetting('qq_app_key');
            
            if (empty($qqAppId) || empty($qqAppKey)) {
                jsonResponse(3, '平台未配置');
            }
            
            $_SESSION['qq_login_mode'] = 'official';
            
            // 生成QQ登录跳转URL
            $qqAuth = new QqAuth($qqAppId, $qqAppKey, $siteUrl . 'callback.php');
            $loginUrl = $qqAuth->getAuthUrl($loginId);
        }
        
        jsonResponse(0, 'success', [
            'login_url' => $loginUrl,
            'mode' => $loginMode
        ]);
        break;
        
    case 'query':
        // 查询用户信息
        $appId = $_GET['app_id'] ?? '';
        $appKey = $_GET['app_key'] ?? '';
        $openId = $_GET['openid'] ?? '';
        
        if (empty($appId) || empty($appKey) || empty($openId)) {
            jsonResponse(1, '参数不完整');
        }
        
        // 验证应用
        $appInfo = $app->db->fetch(
            "SELECT * FROM {$app->db->table('apps')} WHERE app_id=? AND app_key=? AND status=1",
            [$appId, $appKey]
        );
        
        if (!$appInfo) {
            jsonResponse(2, '应用验证失败');
        }
        
        // 查询用户
        $user = $app->db->fetch(
            "SELECT * FROM {$app->db->table('accounts')} 
             WHERE app_id=? AND openid=?",
            [$appInfo['id'], $openId]
        );
        
        if (!$user) {
            jsonResponse(4, '用户不存在');
        }
        
        jsonResponse(0, 'success', [
            'openid'   => $user['openid'],
            'nickname' => $user['nickname'],
            'avatar'   => $user['avatar'],
            'gender'   => $user['gender'],
            'login_ip' => $user['login_ip'],
            'login_time' => $user['login_time']
        ]);
        break;
        
    default:
        jsonResponse(404, '未知操作');
}
