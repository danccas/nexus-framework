<?php

namespace Core;

use Core\Concerns\Collection;
use Core\DBCore;
use Core\Database\Builder;
use Core\Database\Raw;
use Core\Nexus\Tablefy;

class DB extends DBCore
{
  public function execQuery(Builder $query)
  {
    $this->setQuery($query);
    return $this;
  }
  function select($query = null, $params = [])
  {
    return $this->exec_get($query, $params, false);
  }
  function get($query = null, $params = [])
  {
    return $this->exec_get($query, $params, false);
    //return $this->collect($query, $params, false)->all();
  }
  function first($query = null, $params = [])
  {
    return $this->exec_get($query, $params, false)->first();
    //return $this->collect($query, $params, true)->first();
  }
  function tablefy($query, $params = [])
  {
    $opo = new Tablefy;
    $opo->query($query, $params);
    return $opo;
  }
  function PaginationQuery($query, $params = [])
  {
    $opo = new Tablefy;
    $opo->query($query, $params);
    return $opo;
  }
  function escape($texto) {
    return str_replace("'", "\'", $texto);
	}
	public static function raw($val) {
		return new Raw($val);
	}
  function collect($query, $params = [])
  {
    return $this->select($query, $params);
  }
}
