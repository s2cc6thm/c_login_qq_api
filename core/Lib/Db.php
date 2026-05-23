<?php
/**
 * 数据库操作类
 */

namespace Core\Lib;

use PDO;
use PDOException;

class Db
{
    private static $instance = null;
    private $pdo;
    private $prefix;
    
    private function __construct($config)
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $this->prefix = $config['prefix'];
        } catch (PDOException $e) {
            throw new \Exception('数据库连接失败: ' . $e->getMessage());
        }
    }
    
    public static function getInstance($config = null)
    {
        if (self::$instance === null) {
            if ($config === null) {
                $globalConfig = require __DIR__ . '/../config.php';
                $config = $globalConfig['database'];
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    public function getPdo()
    {
        return $this->pdo;
    }
    
    public function table($name)
    {
        return $this->prefix . $name;
    }
    
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function insert($table, $data)
    {
        $table = $this->table($table);
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        $quotedFields = array_map(fn($f) => "`{$f}`", $fields);

        $sql = "INSERT INTO {$table} (" . implode(',', $quotedFields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $this->query($sql, $values);
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = [])
    {
        $table = $this->table($table);
        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            $fields[] = "`{$k}`=?";
            $values[] = $v;
        }
        $sql = "UPDATE {$table} SET " . implode(',', $fields) . " WHERE {$where}";
        $stmt = $this->query($sql, array_merge($values, $whereParams));
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = [])
    {
        $table = $this->table($table);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function count($table, $where = '1', $params = [])
    {
        $table = $this->table($table);
        $sql = "SELECT COUNT(*) as num FROM {$table} WHERE {$where}";
        $row = $this->fetch($sql, $params);
        return $row ? (int)$row['num'] : 0;
    }
}
