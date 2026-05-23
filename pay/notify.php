<?php
/**
 * 支付异步通知处理
 */

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Lib\Pay;

// 获取支付配置
$payUrl = $app->getSetting('pay_api_url');
$payPid = $app->getSetting('pay_pid');
$payKey = $app->getSetting('pay_key');

if (empty($payUrl) || empty($payPid) || empty($payKey)) {
    exit('支付接口未配置');
}

// 接收通知数据
$data = $_POST;
if (empty($data)) {
    exit('no data');
}

// 验证签名
$pay = new Pay($payUrl, $payPid, $payKey);
if (!$pay->verify($data)) {
    exit('sign error');
}

// 验证商户ID
if ($data['pid'] != $payPid) {
    exit('pid error');
}

// 获取订单信息
$orderNo = $data['out_trade_no'] ?? '';
$payNo = $data['trade_no'] ?? '';
$amount = $data['money'] ?? 0;

$order = $app->db->fetch(
    "SELECT * FROM {$app->db->table('orders')} WHERE order_no=? AND status=0",
    [$orderNo]
);

if (!$order) {
    exit('order not found or paid');
}

// 验证金额
if (abs(floatval($amount) - floatval($order['amount'])) > 0.01) {
    exit('amount error');
}

// 开始事务处理
$memberId = $order['member_id'];
$now = date('Y-m-d H:i:s');

try {
    // 更新订单状态
    $app->db->update('orders', [
        'status' => 1,
        'pay_time' => $now,
        'pay_no' => $payNo
    ], 'order_no=?', [$orderNo]);
    
    if ($order['type'] === 'recharge') {
        // 充值处理
        $member = $app->db->fetch(
            "SELECT money FROM {$app->db->table('members')} WHERE id=?",
            [$memberId]
        );
        $newBalance = $member['money'] + $amount;
        
        // 更新余额
        $app->db->update('members', ['money' => $newBalance], 'id=?', [$memberId]);
        
        // 记录资金日志
        $app->db->insert('money_logs', [
            'member_id' => $memberId,
            'type' => 'recharge',
            'amount' => $amount,
            'balance' => $newBalance,
            'description' => '账户充值',
            'order_no' => $orderNo,
            'create_time' => $now
        ]);
        
    } elseif ($order['type'] === 'vip') {
        // 购买会员处理
        $vipLevel = $order['vip_level'];
        $months = $order['vip_months'];
        
        $member = $app->db->fetch(
            "SELECT vip_level, vip_expire FROM {$app->db->table('members')} WHERE id=?",
            [$memberId]
        );
        
        // 计算过期时间
        if ($member['vip_level'] == $vipLevel && $member['vip_expire'] && strtotime($member['vip_expire']) > time()) {
            // 续费，在原有基础上增加
            $expireTime = date('Y-m-d H:i:s', strtotime($member['vip_expire'] . " +{$months} months"));
        } else {
            // 新购或升级
            $expireTime = date('Y-m-d H:i:s', strtotime("+{$months} months"));
        }
        
        // 更新会员信息
        $app->db->update('members', [
            'vip_level' => $vipLevel,
            'vip_expire' => $expireTime
        ], 'id=?', [$memberId]);
        
        // 获取等级名称
        $levelInfo = $app->db->fetch(
            "SELECT name FROM {$app->db->table('vip_levels')} WHERE level=?",
            [$vipLevel]
        );
        
        // 记录资金日志（消费）
        $app->db->insert('money_logs', [
            'member_id' => $memberId,
            'type' => 'consume',
            'amount' => -$amount,
            'balance' => 0,
            'description' => '购买' . ($levelInfo['name'] ?? '会员') . ' x ' . $months . '个月',
            'order_no' => $orderNo,
            'create_time' => $now
        ]);
    }
    
    // 清除缓存
    $app->cache->clear();
    
    echo 'success';
    
} catch (Exception $e) {
    error_log('支付处理失败: ' . $e->getMessage());
    exit('error');
}
