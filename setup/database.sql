-- 管理员表
CREATE TABLE IF NOT EXISTS `qlf_admins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `create_time` datetime DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `login_ip` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员账号 (密码: admin123)
INSERT INTO `qlf_admins` (`username`, `password`, `create_time`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 1);

-- 系统设置表
CREATE TABLE IF NOT EXISTS `qlf_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 会员等级表
CREATE TABLE IF NOT EXISTS `qlf_vip_levels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '等级名称',
  `level` int(11) NOT NULL DEFAULT '1' COMMENT '等级数值',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格(元/月)',
  `app_limit` int(11) NOT NULL DEFAULT '0' COMMENT '应用数量限制(0=无限制)',
  `login_limit` int(11) NOT NULL DEFAULT '0' COMMENT '登录次数限制(0=无限制)',
  `description` text COMMENT '等级描述',
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户表
CREATE TABLE IF NOT EXISTS `qlf_members` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `vip_level` int(11) NOT NULL DEFAULT '0' COMMENT 'VIP等级',
  `vip_expire` datetime DEFAULT NULL COMMENT 'VIP过期时间',
  `create_time` datetime DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `login_ip` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 应用表
CREATE TABLE IF NOT EXISTS `qlf_apps` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT '0',
  `app_id` varchar(50) NOT NULL,
  `app_key` varchar(64) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `domain` varchar(200) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 账号表（存储QQ登录用户信息）
CREATE TABLE IF NOT EXISTS `qlf_accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(11) unsigned NOT NULL,
  `openid` varchar(100) NOT NULL,
  `nickname` varchar(150) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL COMMENT '用户所在地',
  `token` varchar(255) DEFAULT NULL COMMENT 'access_token',
  `login_ip` varchar(50) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `login_count` int(11) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_openid` (`app_id`, `openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 登录日志表
CREATE TABLE IF NOT EXISTS `qlf_login_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(11) unsigned NOT NULL,
  `openid` varchar(100) DEFAULT NULL,
  `nickname` varchar(150) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `user_agent` text,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 快捷登录记录表（用于快捷登录接口回调）
CREATE TABLE IF NOT EXISTS `qlf_login_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL COMMENT '登录码',
  `app_id` int(11) unsigned NOT NULL COMMENT '应用ID',
  `type` varchar(20) NOT NULL COMMENT '登录类型(qq/wx/alipay等)',
  `domain` varchar(100) DEFAULT NULL COMMENT '回调域名',
  `redirect` varchar(500) NOT NULL COMMENT '回调地址',
  `state` varchar(200) DEFAULT NULL COMMENT 'state参数',
  `openid` varchar(100) DEFAULT NULL COMMENT '用户openid(登录完成后填充)',
  `ip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0=未登录，1=已登录',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `app_id` (`app_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='快捷登录记录表';

-- 订单表
CREATE TABLE IF NOT EXISTS `qlf_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `member_id` int(11) unsigned NOT NULL,
  `type` varchar(20) NOT NULL COMMENT '类型：recharge=充值,vip=购买会员',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `subject` varchar(100) DEFAULT NULL COMMENT '订单标题',
  `vip_level` int(11) DEFAULT '0' COMMENT '购买VIP等级',
  `vip_months` int(11) DEFAULT '0' COMMENT 'VIP月数',
  `pay_type` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_no` varchar(100) DEFAULT NULL COMMENT '支付平台订单号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=未支付,1=已支付,2=已关闭',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 资金记录表
CREATE TABLE IF NOT EXISTS `qlf_money_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL,
  `type` varchar(20) NOT NULL COMMENT '类型：recharge=充值,consume=消费',
  `amount` decimal(10,2) NOT NULL COMMENT '金额(正数收入,负数支出)',
  `balance` decimal(10,2) NOT NULL COMMENT '变动后余额',
  `description` varchar(255) DEFAULT NULL,
  `order_no` varchar(50) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 授权码表（用于第三方对接获取用户信息）
CREATE TABLE IF NOT EXISTS `qlf_auth_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL COMMENT '授权码',
  `app_id` int(11) unsigned NOT NULL COMMENT '应用ID',
  `openid` varchar(100) NOT NULL COMMENT '用户openid',
  `nickname` varchar(150) DEFAULT NULL COMMENT '用户昵称',
  `avatar` varchar(500) DEFAULT NULL COMMENT '用户头像',
  `gender` varchar(10) DEFAULT NULL COMMENT '性别',
  `state` varchar(64) DEFAULT NULL COMMENT 'state参数',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认会员等级
INSERT INTO `qlf_vip_levels` (`name`, `level`, `price`, `app_limit`, `login_limit`, `description`, `sort`) VALUES
('普通用户', 0, 0.00, 1, 1000, '免费用户，仅限创建1个应用', 0),
('VIP会员', 1, 9.90, 5, 10000, '可创建5个应用', 1),
('高级会员', 2, 29.90, 0, 0, '无限应用，无限登录', 2);
