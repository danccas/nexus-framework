<?php

namespace Core;

use Core\Database\Builder;
use Core\JSON;

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
    if (empty($cdr)) {
      $this->cdr = static::defaultCDR();
    } else {
      static::$default_cdr = $cdr;
    }
    //		print_r($this->cdr);
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
    if (static::$default_cdr === null) {
      static::$default_cdr = $cdr;
    }
  }
  public static function defaultCDR()
  {
    return static::$default_cdr;
  }
  public function setQuery(Builder $query)
  {
    $this->query = $query;
    return $this;
  }
  /* Proceso de parseo de DSN */

  private function hashConnection()
  {
    return isset(static::$listConnection[$this->cdr]);
  }
  public function existsConnection()
  {
    return $this->hashConnection();
  }
  public function connection()
  {
    if (!$this->hashConnection()) {
      return false;
    }
    //		print_r($this->cdr);
    return static::$listConnection[$this->cdr];
  }
  private function engine()
  {
    if (!$this->hashConnection()) {
      return false;
    }
    return (static::$listConnection[$this->cdr])->engine;
  }
  function establishConnection()
  {
    $this->connection()->setCore($this)->connect();
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
      if ($this->query instanceof Builder) {
        $comodin = $this->query->prepareQuery();
        if ($comodin === null) {
          return new \Core\Concerns\Collection([]);
        }
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
      $token = 'query_' . md5(JSON::encode([$sql, $prepare]));
      $cache = cache($token)->expire($this->cacheExpire);
      if (!$cache->hasExpired()) {
        $rp = $cache->dump();
        $unix = $rp->time;
        $rp = $rp->data;
        $is_cached = true;
        goto saltarCache;
      }
    }
    $unix = time();
    $result = $this->exec($sql, $prepare, false);
    if ($result === false) {
      $rp = false;
    } else {
      $rp = $this->engine()->fetch($result, $first);
    }
    $diff = microtime(true) - $time_start;
    $sec = intval($diff);
    $micro = $diff - $sec;
    if ($diff > 1) {
      file_put_contents('/tmp/query_slow.log', date('Y-m-d H:i:s') . ' => [' . date('H:i:s', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro)) . '] ' . $sql . " = " . JSON::encode($prepare) . "\n\n", FILE_APPEND | LOCK_EX);
    }

    if ($this->cache && !empty($cache)) {
      $cache->set($rp);
    }

    saltarCache:
    $diff = microtime(true) - $time_start;

    if ((!empty($model) && is_array($rp)) || true) {
      if (!empty($model)) {
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
      $rp->execute->unix  = $unix;
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
  function except($err = null, $excep = null)
  {
    kernel()->exception($err, $excep);
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

      $unix = strtotime($t);
      if ($unix === false) {
        if ($t != ($r = str_replace('/', '-', $t))) {
          $unix = strtotime($r);
          if ($unix == false) {
            $r = str_replace(' AM', '', $r);
            $r = str_replace(' PM', '', $r);
            $unix = strtotime($r);
          }
        }
      }
      return date("Y-m-d H:i:s.u", $unix);
    }
  }
  function transaction($callback = null)
  {
    $this->establishConnection();
    _log($this->flog, 'INI TRANSACTION =>');
    if (is_callable($callback)) {
      $this->engine()->transaction();

      $bound = \Closure::bind($callback, $this);
      $rp = $bound($this);
      if ($rp) {
        $this->engine()->commit();
      } else {
        $this->engine()->rollback();
      }
    } else {
      $this->engine()->transaction();
    }
  }
  function commit()
  {
    $this->establishConnection();
    _log($this->flog, ' <= END TRANSACTION: COMMIT');
    return $this->engine()->commit();
  }
  function rollback()
  {
    $this->establishConnection();
    _log($this->flog, ' <= END TRANSACTION: ROLLBACK');
    return $this->engine()->rollback();
  }
}
