<?php

namespace Core;

class DBPSQL
{
    protected $central;
    private $action = 'get';
    private $selects = '*';
    private $tables = [];
    private $wheres = [];
    private $prepares = [];
    private $connect = null;

    public function __construct($central)
    {
        $this->central = $central;
    }
    public function clearQuery()
    {
        $this->selects = '*';
        $this->tables = [];
        $this->wheres = [];
        $this->prepares = [];
        return $this;
    }
    public function select($columns)
    {
        if (is_array($columns)) {
            $this->selects = [];
            foreach ($columns as $c) {
                $this->selects[] = $c;
            }
        } else {
            $this->selects[] = $columns;
        }
    }
    public function from($table)
    {
        $this->tables[] = $table;
    }
    public function where($a, $b, $c)
    {
        $this->wheres[] = [$a, $b, $c];
    }
    public function insert($data)
    {
        $this->action = 'insert';
        $this->prepares = $data;
    }
    public function prepareQuery()
    {
        if($this->action == 'insert') {
            $rp = 'INSERT INTO ' . implode(', ', $this->tables);
            $rp .= " (" . (implode(',', array_map(function($e) { return $e; }, array_keys($this->prepares)))) . ")";
            $rp .= " VALUES (" . (implode(',', array_map(function($e) { return ':' . $e; }, array_keys($this->prepares)))) . ")";
            //$rp .= " RETURNING id";
            $rp .= " RETURNING *";
            return [$rp, $this->prepares];

        } elseif($this->action == 'get') {
            $ce = $this;
            if (!empty($this->wheres)) {
                $this->wheres = array_map(function ($n) use ($ce) {
                    $uu = 'v' . uniqid();
                    if ($n[1] == '=') {
                        $ce->prepares[$uu] = $n[2];
                        return $n[0] . ' = :' . $uu;
                    }
                    return $n[0];
                }, $this->wheres);
            }
            $rp = 'SELECT ' . (is_string($this->selects) ? $this->selects : implode(', ', $this->selects)) . "\n";
            $rp .= 'FROM ' . implode(', ', $this->tables) . "\n";
            if(!empty($this->wheres)) {
                $rp .= 'WHERE ' . implode(' AND ', $this->wheres);
            }
            return [$rp, $this->prepares];
        }
    }
    public function connect($central)
    {
        try {
            if (!empty($central->getHosts()['port'])) {
                $dsn = $central->getProtocol() . ':host=' . $central->getHosts()['host'] . ';port=' . $central->getHosts()['port'] . ';dbname=' . $central->getDatabase();
            } else {
                $dsn = $central->getProtocol() . ':host=' . $central->getHosts()['host'] . ';dbname=' . $central->getDatabase();
            }
            $db = new \PDO($dsn, $central->getAuthentication()['username'], $central->getAuthentication()['password']);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            dd($e);
            $central->except('<b>DB:</b> Can\'t connect to database ' . $central->getProtocol() . '!');
        } catch (\Exception $e) {
            dd($e);
        }
        $this->connect = $db;
        return $db;
    }
    public function execute($connect, $query, $prepare)
    {
        try {
            $result = $connect->prepare($query);
            $rp = $result->execute($prepare);
        } catch (\Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
            exit;
        }
        return $result;
    }
    public function fetch($result, $first = false)
    {
        if ($first === false) {
            $rp = $result->fetchAll(\PDO::FETCH_CLASS);
        } else {
            $rp = $result->fetch(\PDO::FETCH_ASSOC);
            return $rp;
            if(!empty($this->central->query)) {
                $model = $this->central->query->getModel()::class;
                return new $model($rp ? $rp : []);
            }
            if (!empty($rp)) {
                $rp = (object) $rp;
            }
        }
        return $rp;
    }
    public function lastInsertId() {
        if(!empty($this->connect)) {
            return $this->connect->lastInsertId();
        }
        return null;
    }
}