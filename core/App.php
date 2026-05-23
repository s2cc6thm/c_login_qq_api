<?php
/**
 * 应用启动类
 */

namespace Core;

use Core\Lib\Db;
use Core\Lib\Cache;

class App
{
    private static $instance = null;
    public $db;
    public $cache;
    public $config = [];
    public $settings = [];
    
    private function __construct()
    {
        // 加载配置
        $this->config = require __DIR__ . '/config.php';
        
        // 初始化数据库
        $this->db = Db::getInstance($this->config['database']);
        
        // 初始化缓存
        $this->cache = new Cache();
        
        // 加载系统设置
        $this->loadSettings();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadSettings()
    {
        $cached = $this->cache->get('system_settings');
        if ($cached !== null) {
            $this->settings = $cached;
            return;
        }
        
        try {
            $rows = $this->db->fetchAll("SELECT `key`, `value` FROM {$this->db->table('settings')}");
            foreach ($rows as $row) {
                $this->settings[$row['key']] = $row['value'];
            }
            $this->cache->set('system_settings', $this->settings);
        } catch (\Exception $e) {
            // 表可能不存在，使用默认配置
            $this->settings = [
                'site_name' => 'QQ快捷登录',
                'site_title' => 'QQ快捷登录 - 免费版',
                'qq_app_id' => '',
                'qq_app_key' => ''
            ];
        }
    }
    
    public function getSetting($key, $default = null)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    public function setSetting($key, $value)
    {
        $table = $this->db->table('settings');
        $exists = $this->db->fetch("SELECT 1 FROM {$table} WHERE `key`=?", [$key]);
        
        if ($exists) {
            $this->db->update('settings', ['value' => $value], '`key`=?', [$key]);
        } else {
            $this->db->insert('settings', ['key' => $key, 'value' => $value]);
        }
        
        $this->settings[$key] = $value;
        $this->cache->delete('system_settings');
    }
}
