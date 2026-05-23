<?php
/**
 * 对接同系统登录授权类
 * 用于对接其他同类型系统（商业版或同系统）
 */

namespace Core\Lib;

class ThirdAuth
{
    private $apiUrl;
    private $appId;
    private $appKey;
    private $callbackUrl;
    
    /**
     * 构造函数
     * @param string $apiUrl 对接站点接口地址
     * @param string $appId 应用ID
     * @param string $appKey 应用密钥
     * @param string $callbackUrl 回调地址
     */
    public function __construct($apiUrl, $appId, $appKey, $callbackUrl)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->callbackUrl = $callbackUrl;
    }
    
    /**
     * 获取授权跳转URL
     * @param string $state 状态码
     * @return string 授权URL
     */
    public function getAuthUrl($state)
    {
        // 构建参数
        $params = [
            'app_id' => $this->appId,
            'return_url' => $this->callbackUrl,
            'state' => $state
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        // 跳转到被对接系统的connect.php
        return $this->apiUrl . 'connect.php?' . http_build_query($params);
    }
    
    /**
     * 处理回调
     * @param string $code 授权码
     * @param string $state 状态码
     * @return array 用户信息
     * @throws \Exception
     */
    public function callback($code, $state)
    {
        // 验证参数
        if (empty($code) || empty($state)) {
            throw new \Exception('授权参数不完整');
        }
        
        // 构建获取用户信息的请求参数
        $params = [
            'app_id' => $this->appId,
            'code' => $code,
            'state' => $state,
            'timestamp' => time()
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        // 调用被对接系统的API获取用户信息
        $url = $this->apiUrl . 'third_api.php?act=get_userinfo';
        $response = $this->httpPost($url, $params);
        
        $data = json_decode($response, true);
        
        if (!isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception($data['msg'] ?? '获取用户信息失败');
        }
        
        if (empty($data['data']['openid'])) {
            throw new \Exception('返回数据不完整');
        }
        
        return [
            'openid' => $data['data']['openid'],
            'nickname' => $data['data']['nickname'] ?? 'QQ用户',
            'avatar' => $data['data']['avatar'] ?? '',
            'gender' => $data['data']['gender'] ?? 'unknown'
        ];
    }
    
    /**
     * 验证签名
     * @param array $params 参数数组
     * @param string $sign 待验证的签名
     * @return bool 是否有效
     */
    public function verifySign($params, $sign)
    {
        return $this->generateSign($params) === strtoupper($sign);
    }
    
    /**
     * 生成签名
     * @param array $params 参数数组
     * @return string 签名
     */
    private function generateSign($params)
    {
        // 移除签名参数
        unset($params['sign']);
        
        // 按参数名排序
        ksort($params);
        
        // 构建签名字符串
        $string = '';
        foreach ($params as $key => $value) {
            $string .= $key . '=' . $value . '&';
        }
        $string .= 'key=' . $this->appKey;
        
        // MD5签名并转大写
        return strtoupper(md5($string));
    }
    
    /**
     * HTTP POST请求
     * @param string $url 请求URL
     * @param array $data POST数据
     * @return string 响应内容
     * @throws \Exception
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 QQLogin ThirdAuth');
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('请求失败：' . $error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('HTTP错误：' . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * HTTP GET请求
     * @param string $url 请求URL
     * @return string 响应内容
     * @throws \Exception
     */
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 QQLogin ThirdAuth');
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('请求失败：' . $error);
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('HTTP错误：' . $httpCode);
        }
        
        return $response;
    }
}
