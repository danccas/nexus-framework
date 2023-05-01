<?php

namespace Core;

class DBCore extends \Core\DBFuncs
{

  protected $established = false;
  protected $connection = null;
  protected static $listConnection = [];
  protected static $default_cdr;
  protected $query = null;
  protected $cdr;

  public function __construct($cdr = null)
  {
    $this->cdr = $cdr;
    if(empty($cdr)) {
      $this->cdr = static::defaultCDR();
    }
  }
  public static function createDSN($cdr, $dsn, $user = null, $pass = null)
  {
      $cdr = strtolower($cdr);
      if (!is_null($user)) {
          $dsn .= '||' . $user;
      }
      if (!is_null($pass)) {
          $dsn .= '||' . $pass;
      }
      static::$listConnection[$cdr] = new \Core\DBConnect($dsn);
      if(static::$default_cdr === null) {
        static::$default_cdr = $cdr;
      }
  }
  public static function defaultCDR() {
    return static::$default_cdr;
  }
  public function setQuery(Query $query)
  {
    $this->query = $query;
    return $this;
  }
  /* Proceso de parseo de DSN */
  
  private function hashConnection() {
    return isset(static::$listConnection[$this->cdr]);
  }
  public function existsConnection() {
    return $this->hashConnection();
  }
  public function connection() {
    if(!$this->hashConnection()) {
      return false;
    }
    return static::$listConnection[$this->cdr];
  }
  private function engine() {
    if(!$this->hashConnection()) {
      return false;
    }
    return (static::$listConnection[$this->cdr])->engine;
  }
  function establishConnection()
  {
    $this->connection()->connect();
  }
  function exec($query, $prepare = null, $is_cmd = false)
    {
      $this->establishConnection();
      if ($this->log) {
          _log($this->flog, $query, $prepare, $is_cmd);
      }
      return $this->connection()->execute($query, $prepare);
    }
    function exec_get($sql = null, $prepare = null, $first = false)
    {
      $time_start = microtime(true);
      $is_cached = false;
      $model = null;
      if (is_null($sql)) {
          if ($this->query instanceof Query) {
              $comodin = $this->query->prepareQuery();
              $sql = $comodin[0];
              $prepare = $comodin[1];
              unset($comodin);
              if ($model = $this->query->getModel()) {
              }
          } else {
              exit('NO ES QUERY FORMAL');
          }
      }
      if ($this->cache) {
          $token = 'query_' . md5(json_encode([$sql, $prepare]));
          echo "Token: " . $token . "\n";
          $cache = cache($token)->expire($this->cacheExpire);
          if(!$cache->hasExpired()) {
            $rp = $cache->get();
            $is_cached = true;
            goto saltarCache;
          }
      }
      $result = $this->exec($sql, $prepare, false);
      if ($result === false) {
          $rp = false;
      } else {
          $rp = $this->engine()->fetch($result, $first);
      }

      if ($this->cache && !empty($cache)) {
        $cache->set($rp);
      }

      saltarCache:
      $diff = microtime(true) - $time_start;

      if ((!empty($model) && is_array($rp)) || true) {
        if(!empty($model)) {
          $rp = array_map(function ($e) use ($model) {
              if (!empty($model)) {
                  return new ($model::class)((array) $e);
              }
              return $e;
          }, $rp);
        }
        $rp = new \Core\Concerns\Collection($rp);
        $sec = intval($diff);
        $micro = $diff - $sec;
        $rp->execute = new \stdClass();
        $rp->execute->cache_expire  = $this->cacheExpire;
        $rp->execute->cache_current = $is_cached;
        $rp->execute->cache_service = $this->cache;
        $rp->execute->time  = date('H:i:s', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro));
      }
      return $rp;
    }
  function expire($minutes = -1)
  {
    $this->cache = $minutes != 0;
    $this->cacheExpire = $minutes;
    return $this;
  }
  function when()
  {
    return $this->whenConsultCache;
  }

  function map($sql, $cb)
  {
    $rp = $this->get($sql);
    return !empty($rp) ? array_map($cb, $rp) : null;
  }
  private function std_to_array($d)
  {
    $d = is_object($d) ? get_object_vars($d) : $d;
    $ce = &$this;
    return is_array($d) ? array_map(function ($n) use ($ce) {
      return $ce->std_to_array($n);
    }, $d) : $d;
  }
  function count($query, $prepare = null)
  {
    $this->establishConnection();
    if (in_array($this->type, ['pdo', 'oci'])) {
      $rp = $this->first($query, $prepare);

      if (!empty($rp) && is_object($rp)) {
        return !empty($rp) && !empty($rp->cantidad) ? $rp->cantidad : 0;
      }
      return  !empty($rp) ? $rp : 0;
    } elseif ($this->type == 'mongodb') {
      $query = is_array($query) ? $query : array($query);
      $query[1] = !empty($query[1]) ? $query[1] : array();
      $query[2] = !empty($query[2]) ? $query[2] : array();
      $query[0] = str_replace($this->database . '.', '', $query[0]);
      $command = array('count' => $query[0], 'query' => $query[1]);
      $rp = $this->cmd($command);
      return current($rp)['n'];
    } else {
      $this->except('type invalid count');
    }
  }
  
  function last_insert_id()
  {
    //$this->establishConnection();
    $r = $this->engine()->lastInsertId();
    return $r;
    /*} else {
      $this->except('<b>DB:</b> I don\'t know how to return the last insert ID with this DB type: ' . $this->type . '!');
    }*/
  }
  function except($err = null)
  {
    echo "Servidor En mantenimiento...";
    echo $err;
    echo "<h1>DB Error:</h1>\n<br><h3>Trace:</h3><br><pre>" . var_export(debug_backtrace(), true) . "</pre>";
    exit;
  }
  function time($t = null, $m = null)
  {
    if (!is_null($m)) {
      return date("Y-m-d H:i:s.u", strtotime($t . ' ' . $m . ':00'));
    }
    if (is_numeric($t)) {
      return date("Y-m-d H:i:s.u", $t);
    } elseif (is_null($t)) {
      return date("Y-m-d H:i:s.u");
    } else {
      return date("Y-m-d H:i:s.u", strtotime($t));
    }
  }
  function transaction($callback = null)
  {
    $this->establishConnection();
    _log($this->flog, 'INI TRANSACTION =>');
    if(is_callable($callback)) {
      $this->connection->beginTransaction();

      $bound = \Closure::bind($callback, $this);
      $rp = $bound($this);
      if($rp) {
        $this->commit();
      } else {
        $this->rollback();
      }
    } else {
      $this->connection->beginTransaction();
    }
  }
  function commit()
  {
    $this->establishConnection();
    _log($this->flog, ' <= END TRANSACTION: COMMIT');
    return $this->connection->commit();
  }
  function rollback()
  {
    $this->establishConnection();
    _log($this->flog, ' <= END TRANSACTION: ROLLBACK');
    return $this->connection->rollback();
  }
}
