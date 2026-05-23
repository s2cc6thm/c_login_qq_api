<?php
/**
 * 支付接口类 - 易支付
 */

namespace Core\Lib;

class Pay
{
    private $apiUrl;
    private $pid;
    private $key;
    
    public function __construct($apiUrl, $pid, $key)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->pid = $pid;
        $this->key = $key;
    }
    
    /**
     * 创建支付订单
     * 
     * @param string $orderNo 订单号
     * @param float $amount 金额
     * @param string $subject 订单标题
     * @param string $notifyUrl 异步通知地址
     * @param string $returnUrl 同步回调地址
     * @param string $type 支付方式（alipay/qqpay/wxpay）
     * @return array
     */
    public function createOrder($orderNo, $amount, $subject, $notifyUrl, $returnUrl, $type = 'alipay')
    {
        $params = [
            'pid' => $this->pid,
            'type' => $type,
            'out_trade_no' => $orderNo,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'name' => $subject,
            'money' => $amount,
            'clientip' => getClientIp()
        ];
        
        $params['sign'] = $this->sign($params);
        $params['sign_type'] = 'MD5';
        
        return [
            'pay_url' => $this->apiUrl . '/submit.php?' . http_build_query($params),
            'params' => $params
        ];
    }
    
    /**
     * 验证异步回调签名
     * 
     * @return bool
     */
    public function verifyNotify()
    {
        $data = array_merge($_GET, $_POST);
        if (empty($data) || !isset($data['sign'])) {
            return false;
        }
        return $this->getSign($data) === $data['sign'];
    }

    /**
     * 验证同步回调签名
     * 
     * @return bool
     */
    public function verifyReturn()
    {
        $data = $_GET;
        if (empty($data) || !isset($data['sign'])) {
            return false;
        }
        return $this->getSign($data) === $data['sign'];
    }
    
    /**
     * 验证回调签名 (兼容旧版)
     * 
     * @param array $data 回调数据
     * @return bool
     */
    public function verify($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }
        
        return $this->getSign($data) === $data['sign'];
    }
    
    /**
     * 生成签名 (兼容旧版)
     * 
     * @param array $params
     * @return string
     */
    private function sign($params)
    {
        return $this->getSign($params);
    }

    /**
     * 计算签名 - 按照易支付标准算法
     * 
     * @param array $param
     * @return string
     */
    private function getSign($param)
    {
        ksort($param);
        reset($param);
        $signstr = '';
    
        foreach($param as $k => $v){
            if($k != "sign" && $k != "sign_type" && $v !== '' && $v !== null){
                $signstr .= $k.'='.$v.'&';
            }
        }
        $signstr = substr($signstr, 0, -1);
        $signstr .= $this->key;
        return md5($signstr);
    }
    
    /**
     * 查询订单状态
     * 
     * @param string $orderNo
     * @return array
     */
    public function queryOrder($orderNo)
    {
        $params = [
            'act' => 'order',
            'pid' => $this->pid,
            'key' => $this->key,
            'out_trade_no' => $orderNo
        ];
        
        $url = $this->apiUrl . '/api.php?' . http_build_query($params);
        $response = httpRequest($url);
        
        $data = json_decode($response, true);
        if (!$data) {
            return ['code' => -1, 'msg' => '请求失败'];
        }
        
        return $data;
    }
}
