<?php

namespace Core;

use Core\ElegantException;
use Core\DBFuncs;

class DBPSQL
{
    protected $central;
    private $action = 'get';
    private $selects = [];
    private $tables = [];
    private $wheres = [];
    private $orders = [];
    private $prepares = [];
    private $connect = null;

    public function __construct($central)
    {
        $this->central = $central;
    }
    public function clearQuery()
    {
        $this->action = 'get';
        $this->selects = ['*'];
        $this->tables = [];
        $this->wheres = [];
        $this->orders = [];
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
    public function where($a, $b, $c = null)
    {
        $this->wheres[] = [$a, $b, $c];
    }
    public function order($a, $b = 'ASC')
    {
        $this->orders[] = [$a, $b];
    }

    public function insert($data)
    {
        $this->action = 'insert';
        $this->prepares = $data;
    }
    public function update($data)
    {
        $this->action = 'update';
        $this->prepares = $data;
    }

    public function prepareQuery()
    {
        $ce = $this;
        $ce->prepares_mod = [];
        if (!empty($this->wheres) && in_array($this->action, ['get', 'update'])) {
            $this->wheres = array_map(function ($n) use ($ce) {
                $uu = 'v' . uniqid();
                if (is_null($n[2])) {
                    return $n[0] . ' ' . $n[1];
                }
                if ($n[1] == '=') {
                    $ce->prepares_mod[$uu] = $n[2];
                    return $n[0] . ' = :' . $uu;
                }
                return $n[0];
            }, $this->wheres);
        }
        if ($this->action == 'insert') {
            DBFuncs::process_data('values', $this->prepares, $fieldlist, $datalist, $duplelist);
            //				dd([$fieldlist, $datalist, $duplelist]);
            $rp = 'INSERT INTO ' . implode(', ', $this->tables) . ' ';
            $rp .= $fieldlist;
            //						$rp .= " (" . (implode(',', array_map(function($e) { return $e; }, array_keys($this->prepares)))) . ")";
            //						$rp .= " VALUES (" . $fieldlist . ")";
            //            $rp .= " VALUES (" . (implode(',', array_map(function($e) { return ':' . $e; }, array_keys($this->prepares)))) . ")";
            //$rp .= " RETURNING id";
            if (!empty($duplelist)) {
                $rp .= ' ON DUPLICATE KEY UPDATE ' . $duplelist;
            }
            $rp .= " RETURNING *";
            #						if(in_array('robusto.terminal_permiso', $this->tables)) {
            #							dd([$fieldlist, $datalist, $duplelist]);
            #						}
            return [$rp, $datalist];
        } elseif ($this->action == 'update') {
            if (empty($ce->prepares)) {
                return null;
            }
            DBFuncs::process_data('set', $this->prepares, $fieldlist, $datalist, $duplelist);
            //dd([$fieldlist, $datalist, $duplelist]);
            $rp1 = 'UPDATE ' . implode(', ', $this->tables) . ' SET ';
            $rp1 .= $fieldlist . ' ';
            if (!empty($this->wheres)) {
                $rp1 .= 'WHERE ' . implode(' AND ', $this->wheres) . "\n";
            }
            return [$rp1, array_merge($this->prepares_mod, $datalist)];
        } elseif ($this->action == 'get') {
            $rp = 'SELECT ' . (empty($this->selects) ? '*' : implode(', ', $this->selects)) . "\n";
            $rp .= 'FROM ' . implode(', ', $this->tables) . "\n";
            if (!empty($this->wheres)) {
                $rp .= 'WHERE ' . implode(' AND ', $this->wheres) . "\n";
            }
            if (!empty($this->orders)) {
                $this->orders = array_map(function ($n) use ($ce) {
                    return $n[0] . ' ' . $n[1];
                }, $this->orders);
                $rp .= 'ORDER BY ' . implode(', ', $this->orders);
            }
            #						print_r($rp);
            return [$rp, $this->prepares_mod];
        }
    }
    public function connect()
    {
        try {
            if (!empty($this->central->getHosts()['port'])) {
                $dsn = $this->central->getProtocol() . ':host=' . $this->central->getHosts()['host'] . ';port=' . $this->central->getHosts()['port'] . ';dbname=' . $this->central->getDatabase();
            } else {
                $dsn = $this->central->getProtocol() . ':host=' . $this->central->getHosts()['host'] . ';dbname=' . $this->central->getDatabase();
            }
            $db = new \PDO($dsn, $this->central->getAuthentication()['username'], $this->central->getAuthentication()['password']);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
        #    dd($e);
            $this->central->except('<b>DB:</b> Can\'t connect to database ' . $this->central->getProtocol() . '!', $e);
        } catch (\Exception $e) {
          $this->central->except('Error', $e);
#            dd($e);
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
            $this->central->except('Query: ' . $query . '<br>' . json_encode($prepare), $e);
            #       } catch (ElegantException $e) {
            #         echo $e;
            //if( DEVEL_MODE ){
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
            if (!empty($this->central->query)) {
                $model = $this->central->query->getModel()::class;
                return new $model($rp ? $rp : []);
            }
            if (!empty($rp)) {
                $rp = (object) $rp;
            }
        }
        return $rp;
    }
    public function lastInsertId()
    {
      if (!empty($this->connect)) {
        try {
          return $this->connect->lastInsertId();
        } catch(\PDOException $err) {
          return null;
        }
        }
        return null;
    }
    public function transaction() {
      $this->connect->beginTransaction();
      return $this;
    }
    public function commit() {
      $this->connect->commit();
      return $this;
    }
    public function rollback() {
      $this->connect->rollback();
      return $this;
    }
}
