<?php
/**
 * 缓存类
 */

namespace Core\Lib;

class Cache
{
    private $cacheDir;
    private $defaultExpire;
    
    public function __construct($cacheDir = __DIR__ . '/../../runtime/cache/', $defaultExpire = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->defaultExpire = $defaultExpire;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }
    
    private function getFile($key)
    {
        return $this->cacheDir . md5($key) . '.cache';
    }
    
    public function get($key)
    {
        $file = $this->getFile($key);
        if (!file_exists($file)) {
            return null;
        }
        $data = unserialize(file_get_contents($file));
        if ($data['expire'] < time()) {
            @unlink($file);
            return null;
        }
        return $data['value'];
    }
    
    public function set($key, $value, $expire = null)
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        $expire = $expire ?: $this->defaultExpire;
        $data = [
            'expire' => time() + $expire,
            'value' => $value
        ];
        return file_put_contents($file, serialize($data), LOCK_EX);
    }
    
    public function delete($key)
    {
        $file = $this->getFile($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }
    
    public function clear()
    {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
}
