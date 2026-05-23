<?php
/**
 * QQ登录授权类
 */

namespace Core\Lib;

class QqAuth
{
    const AUTH_URL = 'https://graph.qq.com/oauth2.0/authorize';
    const TOKEN_URL = 'https://graph.qq.com/oauth2.0/token';
    const OPENID_URL = 'https://graph.qq.com/oauth2.0/me';
    const USER_INFO_URL = 'https://graph.qq.com/user/get_user_info';
    
    private $appId;
    private $appKey;
    private $callbackUrl;
    
    public function __construct($appId, $appKey, $callbackUrl)
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->callbackUrl = $callbackUrl;
    }
    
    /**
     * 获取授权跳转URL
     */
    public function getAuthUrl($state)
    {
        $params = [
            'response_type' => 'code',
            'client_id'     => $this->appId,
            'redirect_uri'  => $this->callbackUrl,
            'state'         => $state,
            'scope'         => 'get_user_info'
        ];
        return self::AUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * 获取Access Token
     */
    public function getAccessToken($code)
    {
        $params = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->appId,
            'client_secret' => $this->appKey,
            'code'          => $code,
            'redirect_uri'  => $this->callbackUrl
        ];
        
        $url = self::TOKEN_URL . '?' . http_build_query($params);
        $response = $this->httpGet($url);
        
        // 解析响应
        if (strpos($response, 'callback') !== false) {
            $response = $this->parseJsonp($response);
            if (isset($response['error'])) {
                throw new \Exception($response['error_description']);
            }
        } else {
            parse_str($response, $response);
        }
        
        if (!isset($response['access_token'])) {
            throw new \Exception('获取Access Token失败');
        }
        
        return $response['access_token'];
    }
    
    /**
     * 获取OpenID
     */
    public function getOpenId($accessToken)
    {
        $url = self::OPENID_URL . '?access_token=' . $accessToken;
        $response = $this->httpGet($url);
        
        $data = $this->parseJsonp($response);
        if (!isset($data['openid'])) {
            throw new \Exception('获取OpenID失败');
        }
        
        return $data['openid'];
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo($accessToken, $openId)
    {
        $params = [
            'access_token'       => $accessToken,
            'oauth_consumer_key' => $this->appId,
            'openid'             => $openId
        ];
        
        $url = self::USER_INFO_URL . '?' . http_build_query($params);
        $response = $this->httpGet($url);
        $data = json_decode($response, true);
        
        if (!isset($data['ret']) || $data['ret'] !== 0) {
            throw new \Exception($data['msg'] ?? '获取用户信息失败');
        }
        
        return [
            'openid'   => $openId,
            'nickname' => $data['nickname'],
            'avatar'   => $data['figureurl_qq_2'] ?: $data['figureurl_qq_1'],
            'gender'   => $data['gender'] === '男' ? 'male' : ($data['gender'] === '女' ? 'female' : 'unknown')
        ];
    }
    
    /**
     * 完整登录流程
     */
    public function callback($code)
    {
        $accessToken = $this->getAccessToken($code);
        $openId = $this->getOpenId($accessToken);
        $userInfo = $this->getUserInfo($accessToken, $openId);
        $userInfo['access_token'] = $accessToken;
        return $userInfo;
    }
    
    /**
     * HTTP GET请求
     */
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.0');
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    /**
     * 解析JSONP
     */
    private function parseJsonp($jsonp)
    {
        $jsonp = preg_replace('/^.*?\((.*?)\).*$/', '$1', $jsonp);
        return json_decode($jsonp, true);
    }
}
