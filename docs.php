<?php
require_once __DIR__ . '/core/bootstrap.php';

$siteName = $app->getSetting('site_name', '快捷登录平台');
$apiBaseUrl = $siteUrl;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开发文档 - <?php echo $siteName; ?></title>
    <meta name="description" content="快捷登录平台完整开发文档，包含API接口说明、SDK下载和接入示例">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./static/css/style.css?v=2">
    <style>
        .docs-sidebar {
            position: sticky;
            top: 90px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .docs-nav .nav-link {
            color: #666;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        .docs-nav .nav-link:hover,
        .docs-nav .nav-link.active {
            background: rgba(18, 183, 245, 0.1);
            color: var(--primary-color);
        }
        .docs-content h2 { 
            margin-top: 50px; 
            margin-bottom: 20px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
        .docs-content h3 { 
            margin-top: 35px; 
            margin-bottom: 15px; 
            font-weight: 600;
        }
        .docs-content h4 {
            margin-top: 25px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .docs-content pre { 
            background: #1e1e2e;
            color: #a6accd;
            padding: 20px; 
            border-radius: 10px;
            font-size: 14px;
            overflow-x: auto;
        }
        .docs-content code {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        }
        .docs-content code:not(pre code) {
            background: #f1f3f4;
            padding: 2px 8px;
            border-radius: 4px;
            color: #e83e8c;
            font-size: 0.9em;
        }
        .param-table {
            font-size: 0.95rem;
        }
        .param-table th { 
            background: #f8f9fa;
            font-weight: 600;
        }
        .api-method {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 10px;
        }
        .api-method.get { background: #28a745; color: white; }
        .api-method.post { background: #007bff; color: white; }
        .endpoint-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 15px 0;
            font-family: 'Consolas', 'Monaco', monospace;
            word-break: break-all;
        }
        .copy-btn {
            float: right;
            padding: 4px 12px;
            font-size: 0.8rem;
        }
        .alert-api {
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="./">
                <i class="bi bi-shield-check me-2"></i>
                <strong><?php echo $siteName; ?></strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="./">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="./docs.php">开发文档</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./member/">用户中心</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <!-- 侧边栏导航 -->
            <div class="col-lg-3">
                <div class="docs-sidebar">
                    <h5 class="mb-3"><i class="bi bi-journal-text me-2"></i>文档目录</h5>
                    <nav class="nav flex-column docs-nav">
                        <a class="nav-link active" href="#overview">概览</a>
                        <a class="nav-link" href="#quickstart">快速开始</a>
                        <a class="nav-link" href="#api-standard">接口规范</a>
                        <a class="nav-link" href="#api-login">1. 获取登录地址</a>
                        <a class="nav-link" href="#api-callback">2. 登录回调</a>
                        <a class="nav-link" href="#api-query">3. 查询用户信息</a>
                        <a class="nav-link" href="#api-quick">4. 快捷登录接口</a>
                        <a class="nav-link" href="#sdk">SDK与示例</a>
                        <a class="nav-link" href="#faq">常见问题</a>
                        <a class="nav-link" href="#error-code">错误码说明</a>
                    </nav>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-2"><i class="bi bi-headset me-2"></i>需要帮助？</h6>
                        <p class="small text-muted mb-2">遇到问题可以联系我们</p>
                        <a href="#" class="btn btn-sm btn-primary w-100">联系客服</a>
                    </div>
                </div>
            </div>
            
            <!-- 文档内容 -->
            <div class="col-lg-9">
                <div class="docs-content">
                    <h1 class="mb-4">开发文档</h1>
                    
                    <!-- 概览 -->
                    <h2 id="overview">概览</h2>
                    <p>本系统提供标准化的 OAuth2.0 接口，帮助开发者快速接入 QQ 登录功能。通过简单的 HTTP 请求即可实现用户授权和获取用户信息。</p>
                    
                    <div class="alert alert-info alert-api">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>协议规则</h6>
                        <ul class="mb-0">
                            <li><strong>传输方式：</strong>HTTP/HTTPS</li>
                            <li><strong>数据格式：</strong>JSON</li>
                            <li><strong>字符编码：</strong>UTF-8</li>
                            <li><strong>请求方式：</strong>GET/POST</li>
                        </ul>
                    </div>

                    <h2 id="quickstart">快速开始</h2>
                    <p>只需三步即可接入 QQ 登录功能：</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px;font-size:1.25rem;font-weight:bold;">1</div>
                                    <h6>注册应用</h6>
                                    <p class="small text-muted mb-0">在<a href="./member/">用户中心</a>创建应用，获取 AppID 和 AppKey</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px;font-size:1.25rem;font-weight:bold;">2</div>
                                    <h6>调用接口</h6>
                                    <p class="small text-muted mb-0">使用获取到的凭证调用登录接口</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:50px;height:50px;font-size:1.25rem;font-weight:bold;">3</div>
                                    <h6>处理回调</h6>
                                    <p class="small text-muted mb-0">用户授权后处理回调，获取用户信息</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 id="api-standard">接口规范</h2>
                    
                    <h4>基础 URL</h4>
                    <div class="endpoint-box">
                        <?php echo $apiBaseUrl; ?>
                    </div>

                    <h4>通用响应格式</h4>
                    <pre><code>{
    "code": 0,          // 状态码，0表示成功
    "msg": "success",   // 提示信息
    "data": { }         // 响应数据（可选）
}</code></pre>

                    <h4>公共请求参数</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>必填</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>app_id</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用唯一标识，在用户中心获取</td>
                                </tr>
                                <tr>
                                    <td>app_key</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用密钥，用于接口鉴权</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 id="api-login">1. 获取登录地址</h2>
                    <p>获取 QQ 登录跳转地址，引导用户前往 QQ 授权。</p>
                    
                    <h4>请求信息</h4>
                    <div class="endpoint-box">
                        <span class="api-method get">GET</span><?php echo $apiBaseUrl; ?>connect.php?act=login
                    </div>

                    <h4>请求参数</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>必填</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>appid</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用ID</td>
                                </tr>
                                <tr>
                                    <td>appkey</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用密钥</td>
                                </tr>
                                <tr>
                                    <td>type</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>登录类型，目前仅支持 qq</td>
                                </tr>
                                <tr>
                                    <td>redirect_uri</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>登录成功后的回调地址，需 URL 编码</td>
                                </tr>
                                <tr>
                                    <td>state</td>
                                    <td>string</td>
                                    <td>否</td>
                                    <td>自定义状态参数，回调时会原样返回</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4>请求示例</h4>
                    <pre><code>GET <?php echo $apiBaseUrl; ?>connect.php?act=login&appid=YOUR_APP_ID&appkey=YOUR_APP_KEY&type=qq&redirect_uri=https%3A%2F%2Fyour-site.com%2Fcallback&state=xyz123</code></pre>

                    <h4>响应示例（成功）</h4>
                    <pre><code>{
    "code": 0,
    "msg": "succ",
    "type": "qq",
    "url": "https://graph.qq.com/oauth2.0/authorize?..."
}</code></pre>

                    <h4>响应参数</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>code</td>
                                    <td>int</td>
                                    <td>状态码，0 表示成功</td>
                                </tr>
                                <tr>
                                    <td>msg</td>
                                    <td>string</td>
                                    <td>状态说明</td>
                                </tr>
                                <tr>
                                    <td>type</td>
                                    <td>string</td>
                                    <td>登录类型</td>
                                </tr>
                                <tr>
                                    <td>url</td>
                                    <td>string</td>
                                    <td>QQ 授权页面地址，需引导用户跳转</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 id="api-callback">2. 登录回调</h2>
                    <p>用户完成 QQ 授权后，系统会将用户重定向到您指定的回调地址，并携带授权信息。</p>

                    <h4>回调 URL 格式</h4>
                    <div class="endpoint-box">
                        https://your-site.com/callback?code=AUTH_CODE&state=STATE
                    </div>

                    <h4>回调参数</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>code</td>
                                    <td>string</td>
                                    <td>授权码，用于换取用户信息</td>
                                </tr>
                                <tr>
                                    <td>state</td>
                                    <td>string</td>
                                    <td>请求时传入的 state 参数</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>注意：</strong>回调地址必须是您注册应用时填写的域名，否则会被拒绝。
                    </div>

                    <h2 id="api-query">3. 查询用户信息</h2>
                    <p>通过授权码查询用户详细信息。</p>
                    
                    <h4>请求信息</h4>
                    <div class="endpoint-box">
                        <span class="api-method get">GET</span><?php echo $apiBaseUrl; ?>connect.php?act=callback
                    </div>

                    <h4>请求参数</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>必填</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>appid</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用ID</td>
                                </tr>
                                <tr>
                                    <td>appkey</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用密钥</td>
                                </tr>
                                <tr>
                                    <td>type</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>登录类型，固定值 qq</td>
                                </tr>
                                <tr>
                                    <td>code</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>回调时获取的授权码</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4>响应示例（成功）</h4>
                    <pre><code>{
    "code": 0,
    "msg": "succ",
    "type": "qq",
    "access_token": "abc123...",
    "social_uid": "QQ_OPENID",
    "faceimg": "https://thirdqq.qlogo.cn/g?b=oidb&k=xxx",
    "nickname": "QQ昵称",
    "location": "北京",
    "gender": "男",
    "ip": "192.168.1.1"
}</code></pre>

                    <h4>响应参数说明</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>code</td>
                                    <td>int</td>
                                    <td>状态码，0 表示成功，2 表示等待登录完成</td>
                                </tr>
                                <tr>
                                    <td>social_uid</td>
                                    <td>string</td>
                                    <td>用户在 QQ 的唯一标识（OpenID）</td>
                                </tr>
                                <tr>
                                    <td>access_token</td>
                                    <td>string</td>
                                    <td>访问令牌</td>
                                </tr>
                                <tr>
                                    <td>nickname</td>
                                    <td>string</td>
                                    <td>用户昵称</td>
                                </tr>
                                <tr>
                                    <td>faceimg</td>
                                    <td>string</td>
                                    <td>用户头像 URL</td>
                                </tr>
                                <tr>
                                    <td>gender</td>
                                    <td>string</td>
                                    <td>性别：男/女</td>
                                </tr>
                                <tr>
                                    <td>location</td>
                                    <td>string</td>
                                    <td>用户所在地区</td>
                                </tr>
                                <tr>
                                    <td>ip</td>
                                    <td>string</td>
                                    <td>用户登录IP</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 id="api-quick">4. 快捷查询用户信息</h2>
                    <p>通过已知的 social_uid 直接查询用户信息，无需再次授权。</p>
                    
                    <h4>请求信息</h4>
                    <div class="endpoint-box">
                        <span class="api-method get">GET</span><?php echo $apiBaseUrl; ?>connect.php?act=query
                    </div>

                    <h4>请求参数</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>参数名</th>
                                    <th>类型</th>
                                    <th>必填</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>appid</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用ID</td>
                                </tr>
                                <tr>
                                    <td>appkey</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>应用密钥</td>
                                </tr>
                                <tr>
                                    <td>type</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>登录类型，固定值 qq</td>
                                </tr>
                                <tr>
                                    <td>social_uid</td>
                                    <td>string</td>
                                    <td>是</td>
                                    <td>用户的 OpenID</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4>响应示例</h4>
                    <pre><code>{
    "code": 0,
    "msg": "succ",
    "type": "qq",
    "social_uid": "QQ_OPENID",
    "access_token": "abc123...",
    "nickname": "QQ昵称",
    "faceimg": "https://thirdqq.qlogo.cn/g?b=oidb&k=xxx",
    "location": "北京",
    "gender": "男",
    "ip": "192.168.1.1"
}</code></pre>

                    <h2 id="sdk">SDK与示例</h2>
                    
                    <h3>PHP 接入示例</h3>
                    <pre><code>&lt;?php
// 配置信息
$appId = 'YOUR_APP_ID';
$appKey = 'YOUR_APP_KEY';
$apiUrl = '<?php echo $apiBaseUrl; ?>';

// 1. 获取登录地址
function getLoginUrl($appId, $appKey, $redirectUri, $state = '') {
    global $apiUrl;
    
    $params = [
        'act' => 'login',
        'appid' => $appId,
        'appkey' => $appKey,
        'type' => 'qq',
        'redirect_uri' => $redirectUri,
        'state' => $state
    ];
    
    $url = $apiUrl . 'connect.php?' . http_build_query($params);
    
    // 发送请求
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['code'] === 0) {
        return $data['url'];
    }
    
    throw new Exception('获取登录地址失败: ' . $data['msg']);
}

// 2. 处理回调
function handleCallback($appId, $appKey, $code) {
    global $apiUrl;
    
    $params = [
        'act' => 'callback',
        'appid' => $appId,
        'appkey' => $appKey,
        'type' => 'qq',
        'code' => $code
    ];
    
    $url = $apiUrl . 'connect.php?' . http_build_query($params);
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['code'] === 0) {
        // 登录成功，处理用户信息
        $userInfo = [
            'openid' => $data['social_uid'],
            'nickname' => $data['nickname'],
            'avatar' => $data['faceimg'],
            'gender' => $data['gender'],
            'location' => $data['location']
        ];
        
        // TODO: 将用户信息保存到数据库，设置登录状态等
        return $userInfo;
    }
    
    throw new Exception('获取用户信息失败: ' . $data['msg']);
}
?&gt;</code></pre>

                    <h3>JavaScript 接入示例</h3>
                    <pre><code>// 配置
const CONFIG = {
    appId: 'YOUR_APP_ID',
    appKey: 'YOUR_APP_KEY',
    apiUrl: '<?php echo $apiBaseUrl; ?>'
};

// QQ 登录按钮点击事件
async function qqLogin() {
    const redirectUri = encodeURIComponent(window.location.origin + '/callback');
    const state = generateRandomString(16); // 生成随机状态码
    
    // 保存 state 用于后续验证
    sessionStorage.setItem('qq_login_state', state);
    
    // 构建请求 URL
    const params = new URLSearchParams({
        act: 'login',
        appid: CONFIG.appId,
        appkey: CONFIG.appKey,
        type: 'qq',
        redirect_uri: window.location.origin + '/callback',
        state: state
    });
    
    try {
        const response = await fetch(`${CONFIG.apiUrl}connect.php?${params}`);
        const data = await response.json();
        
        if (data.code === 0) {
            // 跳转到 QQ 授权页面
            window.location.href = data.url;
        } else {
            alert('登录初始化失败: ' + data.msg);
        }
    } catch (error) {
        console.error('登录请求失败:', error);
        alert('网络错误，请稍后重试');
    }
}

// 处理回调（在回调页面调用）
async function handleCallback() {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    const state = urlParams.get('state');
    
    // 验证 state 防止 CSRF 攻击
    const savedState = sessionStorage.getItem('qq_login_state');
    if (state !== savedState) {
        alert('状态验证失败，请重新登录');
        return;
    }
    
    // 查询用户信息
    const params = new URLSearchParams({
        act: 'callback',
        appid: CONFIG.appId,
        appkey: CONFIG.appKey,
        type: 'qq',
        code: code
    });
    
    try {
        const response = await fetch(`${CONFIG.apiUrl}connect.php?${params}`);
        const data = await response.json();
        
        if (data.code === 0) {
            // 登录成功
            console.log('用户信息:', data);
            // TODO: 发送用户信息到后端完成登录
        } else {
            alert('登录失败: ' + data.msg);
        }
    } catch (error) {
        console.error('获取用户信息失败:', error);
    }
}

// 生成随机字符串
function generateRandomString(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}</code></pre>

                    <h3>HTML 登录按钮示例</h3>
                    <pre><code>&lt;!-- 简单的 QQ 登录按钮 --&gt;
&lt;button onclick="qqLogin()" class="qq-login-btn"&gt;
    &lt;img src="https://img.icons8.com/color/48/qq.png" alt="QQ"&gt;
    &lt;span&gt;QQ 登录&lt;/span&gt;
&lt;/button&gt;

&lt;style&gt;
.qq-login-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    background: #12b7f5;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}
.qq-login-btn:hover {
    background: #0a8dc7;
    transform: translateY(-2px);
}
&lt;/style&gt;</code></pre>

                    <h2 id="faq">常见问题</h2>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    如何获取 AppID 和 AppKey？
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    注册账号后，进入<a href="./member/">用户中心</a>，在"我的应用"页面点击"创建应用"，填写应用名称和回调域名后即可获取。
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    回调地址有什么要求？
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    回调地址必须是您注册应用时填写的域名下的页面，且需要使用完整的 URL（包含协议，如 https://）。出于安全考虑，不支持 IP 地址和 localhost。
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    登录流程中的 state 参数有什么用？
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    state 参数用于防止 CSRF 攻击。建议生成一个随机字符串作为 state，在发起登录请求前保存到 Session 或 LocalStorage，回调时验证返回的 state 是否一致。
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    用户拒绝授权怎么办？
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    如果用户拒绝授权，QQ 会重定向到回调地址并带有错误参数。您的回调处理逻辑需要检查是否存在 code 参数，如果不存在则表示用户拒绝了授权。
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    免费版有什么限制？
                                </button>
                            </h3>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    免费版支持 QQ 登录，每日调用限额为 1000 次，足够小型网站使用。如需更多调用次数或更多登录方式，可考虑升级到专业版。
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 id="error-code">错误码说明</h2>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered param-table">
                            <thead>
                                <tr>
                                    <th>错误码</th>
                                    <th>说明</th>
                                    <th>解决方案</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>0</td>
                                    <td>成功</td>
                                    <td>请求正常处理</td>
                                </tr>
                                <tr>
                                    <td>-1</td>
                                    <td>参数错误</td>
                                    <td>检查请求参数是否完整、正确</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>等待登录完成（轮询状态）</td>
                                    <td>用户尚未完成授权，需继续轮询或等待</td>
                                </tr>
                                <tr>
                                    <td>其他</td>
                                    <td>应用验证失败</td>
                                    <td>检查 AppID 和 AppKey 是否正确，应用是否已启用</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-4">
                        <h6 class="alert-heading"><i class="bi bi-lightbulb me-2"></i>提示</h6>
                        <p class="mb-0">如需更多帮助，请联系客服或加入开发者社区。我们会在 24 小时内回复您的问题。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' })
                document.querySelectorAll('.docs-nav .nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });
    const sections = document.querySelectorAll('.docs-content h2[id]');
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollY >= sectionTop - 150) {
                current = section.getAttribute('id');
            }
        });
        
        document.querySelectorAll('.docs-nav .nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
    </script>
</body>
</html>
