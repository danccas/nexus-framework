<?php

namespace Core\Database;

use Core\Concerns\Collection;
use Core\Concerns\Scope;

class Builder
{
    protected $model;
    protected $table;
    protected $action;
    protected $connection;
    protected $columns;
    protected $wheres;
    protected $orders;
    protected $dbconnect;
    protected $values;
    protected $scopes;
    public $first = false;

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
    public function getAction()
    {
        return $this->action;
    }
    public function getModel()
    {
        return $this->model;
    }
    public function setConnection($dsn)
    {
        $this->connection = $dsn;
        return $this;
    }
    public function setModel($model)
    {
        $this->model = $model;
        $this->connection = $this->model->getConnection();
        if (is_null($this->connection)) {
            kernel()->exception('without Connection: ' . $model::class);
        }
        $this->dbconnect = $this->connection->connection();
        $this->dbconnect->engine()->clearQuery();
        $this->setTable($this->model->getTable());
        return $this;
    }
    public function setTable($name)
    {
        $this->table = $name;
        $this->dbconnect->engine()->from($name);
        return $this;
    }
    public function select($value, $name = null)
    {
        return $this->addSelect($value, $name);
    }
    public function addSelect($value, $name = null)
    {
        $raw = is_null($name) ? $value : '(' . $value . ') as ' . $name;
        $this->columns[] = $raw;
        $this->dbconnect->engine()->select($raw);
        return $this;
    }
    public function setColumns($name)
    {
        $this->columns = $name;
        $this->dbconnect->engine()->select($name);
        return $this;
    }
    public function find($pk)
    {
        $this->action = 'get';
        $this->where($this->model->getPrimaryKey(), '=', $pk);
        return $this;
    }
    public function where($a, $b = '__NODEFINIDO__', $c = '__NODEFINIDO__')
    {
        $this->dbconnect->engine()->where($a, $b, $c);
        return $this;
    }
    public function whereNull($a)
    {
        $this->dbconnect->engine()->whereRaw($a . ' IS NULL');
        return $this;
    }
    public function whereRaw($a)
    {
        $this->dbconnect->engine()->whereRaw($a);
        return $this;
    }
    public function orderBy($campo, $by = 'ASC')
    {
        $this->action = 'get';
        $this->dbconnect->engine()->order($campo, $by);
        return $this;
    }
    public function first()
    {
        $this->action = 'get';
        $this->first = true;
        return $this->get()->first();
    }
    public function all()
    {
        $this->action = 'get';
        $this->first = false;
        return $this->get()->get();
    }
    public function insert($values)
    {
        $this->action = 'insert';
        $this->values = $values;
        $this->dbconnect->engine()->insert($values);
        return $this->get();
    }
    public function update($values)
    {
        $this->action = 'update';
        $this->values = $values;
        $this->dbconnect->engine()->update($values);
        return $this->get();
    }
    public function delete()
    {
        $this->action = 'delete';
        $this->dbconnect->engine()->delete();
        return $this->get()->get();
    }
    public function get()
    {
        $builder = $this->applyScopes();
        return $this->connection->execQuery($builder);
    }
    public function prepareQuery()
    {
        return $this->dbconnect->engine()->prepareQuery();
    }

    public function withGlobalScope($identifier, $scope)
    {
        $this->scopes[$identifier] = $scope;
        #if (method_exists($scope, 'extend')) {
        #    $scope->extend($this);
        #}

        return $this;
    }

    /**
     * Remove a registered global scope.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return $this
     */
    public function withoutGlobalScope($scope)
    {
        if (!is_string($scope)) {
            $scope = get_class($scope);
        }

        unset($this->scopes[$scope]);

        $this->removedScopes[] = $scope;

        return $this;
    }

    /**
     * Remove all or passed registered global scopes.
     *
     * @param  array|null  $scopes
     * @return $this
     */
    public function withoutGlobalScopes(array $scopes = null)
    {
        if (!is_array($scopes)) {
            $scopes = array_keys($this->scopes);
        }

        foreach ($scopes as $scope) {
            $this->withoutGlobalScope($scope);
        }

        return $this;
    }

    /**
     * Get an array of global scopes that were removed from the query.
     *
     * @return array
     */
    public function removedScopes()
    {
        return $this->removedScopes;
    }
    public function applyScopes()
    {
        if (!$this->scopes) {
            return $this;
        }

        #				$builder = clone $this;
        $builder = $this;

        foreach ($this->scopes as $identifier => $scope) {
            if (!isset($builder->scopes[$identifier])) {
                continue;
            }

            $builder->callScope(function (self $builder) use ($scope) {
                // If the scope is a Closure we will just go ahead and call the scope with the
                // builder instance. The "callScope" method will properly group the clauses
                // that are added to this query so "where" clauses maintain proper logic.
                if ($scope instanceof \Closure) {
                    return $scope($builder);
                }

                // If the scope is a scope object, we will call the apply method on this scope
                // passing in the builder and the model instance. After we run all of these
                // scopes we will return back the builder instance to the outside caller.
                if ($scope instanceof Scope) {
                    if (method_exists($scope, 'apply')) {
                        return $scope->apply($builder, $this->getModel());
                    }
                }
            });
        }

        return $builder;
    }
    protected function callScope(callable $scope, array $parameters = [])
    {
        array_unshift($parameters, $this);

        #        $query = $this->getQuery();

        // We will keep track of how many wheres are on the query before running the
        // scope so that we can properly group the added scope constraints in the
        // query as their own isolated nested where statement and avoid issues.
        #        $originalWhereCount = is_null($query->wheres)
        #                    ? 0 : count($query->wheres);

        $result = $scope(...array_values($parameters)) ?? $this;

        #        if (count((array) $query->wheres) > $originalWhereCount) {
        #            $this->addNewWheresWithinGroup($query, $originalWhereCount);
        #        }

        return $result;
    }
    protected function callNamedScope($scope, array $parameters = [])
    {
        return $this->callScope(function (...$parameters) use ($scope) {
            return $this->model->callNamedScope($scope, $parameters);
        }, $parameters);
    }
    public function hasNamedScope($scope)
    {
        return $this->model && $this->model->hasNamedScope($scope);
    }
    public function __call($method, $parameters)
    {
        if ($this->hasNamedScope($method)) {
            return $this->callNamedScope($method, $parameters);
        }
        return $this;
    }
}
