<?php
namespace Core;

use Core\Concerns\Collection;

class Query {
    protected $model;
    protected $table;
    protected $action;
    protected $connection;
    protected $columns;
    protected $wheres;
    protected $orders;
    protected $dbconnect;
    protected $values;
    public $first = false;

    public function setAction($action) {
        $this->action = $action;
        return $this;
    }
    public function getAction() {
        return $this->action;
    }
    public function getModel() {
        return $this->model;
    }
    public function setConnection($dsn) {
        $this->connection = $dsn;
        return $this;
    }
    public function setModel($model) {
        $this->model = $model;
        $this->connection = $this->model->getConnection();
        $this->dbconnect = $this->connection->connection();
        $this->dbconnect->engine()->clearQuery();
        $this->setTable($this->model->getTable());
        return $this;
    }
    public function setTable($name) {
        $this->table = $name;
        $this->dbconnect->engine()->from($name);
        return $this;
    }
    public function setColumns($name) {
        $this->columns = $name;
        $this->dbconnect->engine()->select($name);
        return $this;
    }
    public function find($pk) {
        $this->action = 'get';
        $this->where($this->model->getPrimaryKey(), '=' , $pk);
        return $this;
    }
    public function where($a, $b, $c) {
        $this->dbconnect->engine()->where($a, $b, $c);
        return $this;
    }
    public function orderBy($campo, $by = 'ASC') {
			$this->action = 'get';
			$this->dbconnect->engine()->order($campo, $by);
      return $this;
    }
    public function first() {
        $this->action = 'get';
        $this->first = true;
        return $this->get();
    }
    public function all() {
        $this->action = 'get';
        $this->first = false;
        return $this->get()->get();
    }
    public function insert($values) {
        $this->action = 'insert';
        $this->values = $values;
        $this->dbconnect->engine()->insert($values);
        return $this->get();
		}
		public function update($values) {
        $this->action = 'update';
        $this->values = $values;
        $this->dbconnect->engine()->update($values);
        return $this->get();
    }
    public function get() {
        return $this->connection->execQuery($this);
    }
    public function prepareQuery() {
        return $this->dbconnect->engine()->prepareQuery();
    }
}
