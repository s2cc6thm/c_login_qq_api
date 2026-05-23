<?php
/**
 * 全局辅助函数
 */

/**
 * 获取客户端IP
 */
function getClientIp()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * 生成随机字符串
 */
function generateRandom($length = 16, $numeric = false)
{
    $chars = $numeric ? '0123456789' : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $result;
}

/**
 * 数据加密
 */
function encryptData($data, $key)
{
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * 数据解密
 */
function decryptData($data, $key)
{
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * 发送HTTP请求
 */
function httpRequest($url, $method = 'GET', $data = [], $headers = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        }
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * JSON响应
 */
function jsonResponse($code, $msg = '', $data = [])
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data
    ]);
    exit;
}

/**
 * 成功响应
 */
function success($msg = '操作成功', $data = [])
{
    jsonResponse(0, $msg, $data);
}

/**
 * 错误响应
 */
function error($msg = '操作失败', $code = 1)
{
    jsonResponse($code, $msg, []);
}

/**
 * 跳转页面
 */
function redirect($url)
{
    header("Location: {$url}");
    exit;
}

/**
 * 显示提示信息
 */
function showAlert($msg, $url = null)
{
    $back = $url ? "window.location.href='{$url}'" : "history.back()";
    echo "<script>alert('{$msg}');{$back};</script>";
    exit;
}

/**
 * 过滤输入
 */
function filterInput($data)
{
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $data[$k] = filterInput($v);
        }
    } else {
        $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * 获取当前网址
 */
function getCurrentUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 获取基础网址
 */
function getBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . '/';
}
