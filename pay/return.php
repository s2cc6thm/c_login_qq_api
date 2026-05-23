<?php
/**
 * 支付同步回调页面
 */

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Lib\Pay;

// 获取参数
$orderNo = $_GET['out_trade_no'] ?? '';

if (empty($orderNo)) {
    showAlert('订单号不能为空', '../member/orders.php');
}

// 查询订单
$order = $app->db->fetch(
    "SELECT * FROM {$app->db->table('orders')} WHERE order_no=?",
    [$orderNo]
);

if (!$order) {
    showAlert('订单不存在', '../member/orders.php');
}

$siteName = $app->getSetting('site_name', 'QQ快捷登录');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>支付结果 - <?php echo $siteName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .result-box { max-width: 500px; margin: 100px auto; background: white; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .success-icon { color: #28a745; font-size: 64px; }
        .pending-icon { color: #ffc107; font-size: 64px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-box">
            <?php if ($order['status'] == 1): ?>
                <i class="bi bi-check-circle-fill success-icon"></i>
                <h3 class="mt-3">支付成功</h3>
                <p class="text-muted">订单号：<?php echo $orderNo; ?></p>
                <p class="text-muted">金额：¥<?php echo number_format($order['amount'], 2); ?></p>
                <a href="../member/orders.php" class="btn btn-primary">查看订单</a>
            <?php else: ?>
                <i class="bi bi-hourglass-split pending-icon"></i>
                <h3 class="mt-3">等待支付结果</h3>
                <p class="text-muted">订单号：<?php echo $orderNo; ?></p>
                <p class="text-muted">如已支付成功，请稍后在订单页面查看</p>
                <a href="../member/orders.php" class="btn btn-primary">查看订单</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
