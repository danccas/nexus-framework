<?php

namespace Core;

use Core\Concerns\Collection;

class Model implements  \JsonSerializable 
{
    use Concerns\HasAttributes;
    use Concerns\GuardsAttributes;

    protected $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $exists = false;
    protected static $booted = [];
    protected static $traitInitializers = [];
    protected $fireEvents = [];


    protected static $instance = [];
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
            static::boot();
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
    private static function instanceQuery()
    {
        return (new Query)->setModel(static::instance());
    }
    public static function find($id)
    {
        return (new Query)->setModel(static::instance())->find($id)->get();
    }
    public static function where($key, $compare, $value)
    {
        return (new Query)->setModel(static::instance())->where($key, $compare, $value);
    }
    public static function create($values) {
        return (new Query)->setModel(static::instance())->insert($values)->first();
    }
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists) {
            return false;
        }

        return $this->fill($attributes)->save($options);
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
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                exit('ERRRORR');
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
            exit('EDITAR');
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            exit('REGISTRAR');
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.

        return true;
    }
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    public function toArray()
    {
        return [123];
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }
    public static function all($columns = ['*'])
    {
        $model = static::instance();
        return (new Collection(((new Query)->setModel($model)->setColumns($columns)->all())))->hydrate(static::class);
    }
    public static function hydrate($items)
    {
        if($items instanceof Collection) {
            return $items->hydrate(static::class);
        } elseif(is_array($items)) {
            return (new Collection($items))->hydrate(static::class);
        }
    }
    public static function hydrateQuery($query, $params = [])
    {
        return static::instance()->connection->get($query, $params);
    }

    public function fireModelEvent($name, $value = null)
    {
        if ($value === null) {
            if (isset($this->fireEvents[$name])) {
                return $this->fireEvents[$name];
            }
            return null;
        }
        $this->fireEvents[$name] = $value;
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
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
    public function __toString() {
        return "No es posible convertir a objeto: " . static::class;
    }
    public function jsonSerialize() : array {
        return $this->attributes;
    }
}
