<?php

namespace Core;

use Core\Concerns\Collection;
use Core\Database\Builder;

class Model implements \JsonSerializable
{
    use Concerns\HasAttributes;
    use Concerns\HasGlobalScopes;
    use Concerns\GuardsAttributes;
    use Concerns\HasEvents;

    protected $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $exists = false;
    protected static $booted = [];
    protected static $traitInitializers = [];
    protected $fireEvents = [];
    protected static $instance = [];
    protected static $globalScopes = [];
    public $wasRecentlyCreated = false;
    protected static $dispatcher;

    public static function instance()
    {
        if (!isset(static::$instance[static::class])) {
            static::$instance[static::class] = new static();
        }
        return static::$instance[static::class];
    }
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();
        $this->initializeTraits();
        $this->fill($attributes);
    }
    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->fireModelEvent('booting', false);
            static::booting();
            static::boot();
            static::booted();
            $this->bootConnection();
            $this->fireModelEvent('booted', false);
        }
    }
    public function getConnection()
    {
        $this->bootConnection();
        return $this->connection;
    }
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    public function getTable()
    {
        return $this->table;
    }
    #    private static function instanceQuery()
    #    {
    #			return static::query();
    #    }
    public static function find($id)
    {
        if (is_null($id)) {
            return null;
        }
        $dd = static::query()->find($id);
        if (is_null($dd)) {
            return null;
        }
        $dd = $dd->get();
        if (is_null($dd)) {
            return null;
        }
        return $dd->first();
    }

    public static function where($key, $compare, $value = null)
    {
        if (is_null($value)) {
            $value = $compare;
            $compare = '=';
        }
        return static::query()->where($key, $compare, $value);
    }
    public static function whereNull($key)
    {
        return static::query()->whereNull($key);
    }
    public function hasMany($model, $fk_id)
    {
        return $model::query()->where($fk_id, '=', $this->id);
    }
    public function belongsTo($model, $field_id = null, $fk_id = 'id')
    {
        if (is_null($field_id)) {
            $field_id = (new $model)->getTable() . '_id';
        }
        if (!isset($this->{$field_id}) || empty($this->{$field_id})) {
            return collect([]);
        }
        return $model::query()->where($fk_id, '=', $this->{$field_id});
    }

    public static function create($values)
    {
        $values = (array) $values;
        $model = new static($values);
        return $model->performInsert($model->query());
    }
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists) {
            return false;
        }
        foreach ($attributes as $k => $v) {
            $this->{$k} = $v;
        }
        return $this->save($options);
    }
    public function setExists()
    {
        $this->exists = true;
        return $this;
    }
    public function getExists()
    {
        return $this->exists;
    }
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        if (!empty($attributes[$this->primaryKey])) {
            $this->exists = true;
        }
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                $this->setAttribute($key, $value);
                //                exit('ERRRORR');
            }
        }

        return $this;
    }


    public function delete()
    {
        exit("editar");
    }

    public function save(array $options = [])
    {
        $this->mergeAttributesFromClassCasts();

        $query = static::query();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            return $this->performUpdate($query);
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            return $this->performInsert($query);
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.

        return true;
    }
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        #        if ($this->usesTimestamps()) {
        #            $this->updateTimestamps();
        #        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.

        $res = $this->setKeysForSaveQuery($query)
            ->update($this->getChanges())->first();

        $this->fireModelEvent('updated', false);
        #        }

        return $res;
    }
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        #        if ($this->usesTimestamps()) {
        #            $this->updateTimestamps();
        #        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.

        $attributes = $this->getAttributes();
        #        if ($this->getIncrementing()) {
        #            $this->insertAndSetId($query, $attributes);
        #        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        #        else {
        #            if (empty($attributes)) {
        #                return true;
        #            }

        $res = $query->insert($attributes)->first();
        #        }


        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return $res;
    }
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getPrimaryKey(), '=', $this->getAttribute($this->primaryKey));
        return $query;
    }
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    public function toArray()
    {
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }
    public static function all($columns = ['*'])
    {
        $model = static::instance();
        return (new Collection((static::query()->setColumns($columns)->all())));#->hydrate(static::class);
    }
    public static function hydrate($items)
    {
        if ($items instanceof Collection) {
            return $items->hydrate(static::class);
        } elseif (is_array($items)) {
            return (new Collection($items))->hydrate(static::class);
        }
    }
    public static function hydrateQuery($query, $params = [])
    {
        return (new Collection(static::instance()->getConnection()->get($query, $params)))->hydrate(static::class);
        //        return static::instance()->connection->get($query, $params);
    }
    #    public static function query3($query, $params = [])
    #    {
    #			return static::hydrateQuery($query, $params);
    #		}
    public static function query()
    {
        return (new static)->newQuery();
    }
    public function newQuery()
    {
        return $this->registerGlobalScopes($this->newQueryWithoutScopes());
    }
    public function newQueryWithoutScopes()
    {
        return (new Builder)->setModel(static::instance());
    }
    /**
     * Perform any actions required before the model boots.
     *
     * @return void
     */
    protected static function booting()
    {
        //
    }
    protected static function boot()
    {
        static::bootTraits();
    }
    protected static function booted()
    {
        //
    }
    public static function clearBootedModels()
    {
        static::$booted = [];

        static::$globalScopes = [];
    }
    public function hasNamedScope($scope)
    {
        return method_exists($this, 'scope' . ucfirst($scope));
    }

    public function callNamedScope($scope, array $parameters = [])
    {
        return $this->{'scope' . ucfirst($scope)}(...$parameters);
    }


    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected function bootConnection()
    {
        if (!empty($this->connection) && is_string($this->connection)) {
            if (db($this->connection)->existsConnection()) {
                $this->connection = db($this->connection);
            } else {
                exit('NO EXISTE LA CONEXION: ' . $this->connection);
            }
        }
    }
    protected static function bootTraits()
    {
        $class = static::class;

        $booted = [];

        static::$traitInitializers[$class] = [];
    }
    protected function initializeTraits()
    {
        foreach (static::$traitInitializers[static::class] as $method) {
            $this->{$method}();
        }
    }


    public function registerGlobalScopes($builder)
    {
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * Set the data type for the primary key.
     *
     * @param  string  $type
     * @return $this
     */
    public function setKeyType($type)
    {
        $this->keyType = $type;

        return $this;
    }



    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
        if ($this->isFillable($key)) {
            $this->setChange($key, $value);
        }
    }
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
    public function __toString()
    {
        return "No es posible convertir a objeto: " . static::class;
    }
    public function jsonSerialize(): array
    {
        return $this->attributes;
    }
}
