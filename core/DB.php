<?php

namespace Core;

use Core\Concerns\Collection;
use Core\DBCore;
use Core\PaginationQuery;

class DB extends DBCore
{
  public function execQuery(Query $query)
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
    $opo = new PaginationQuery;
    $opo->query($query, $params);
    return $opo;
  }
  function PaginationQuery($query, $params = [])
  {
    $opo = new PaginationQuery;
    $opo->query($query, $params);
    return $opo;
  }
  function escape($texto) {
    return str_replace("'", "\'", $texto);
  }
  function collect($query, $params = [])
  {
    return $this->select($query, $params);
  }
}
