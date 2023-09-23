<?php
namespace Core\Artisan\Commands;

use Core\Artisan\Command;
use Core\Blade;
use Core\Route;

class MakeCrud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Crud';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    static function castCompare($dbcast) {
      $dbcast = strtolower($dbcast);
      if($dbcast == 'integer') {
        return 'integer';
      } elseif($dbcast == 'character varying') {
        return 'string';
      } elseif($dbcast == 'timestamp without time zone') {
        return 'datetime';
      } elseif($dbcast == 'boolean') {
        return 'boolean';
      } elseif($dbcast == 'bigint') {
        return 'integer';
      } else {
        return 'string';
      }
    }
    private function getColumns($db, $schema, $table) {
        return $db->get("SELECT * FROM information_schema.columns WHERE table_schema = :schema AND table_name = :name", [
            'schema' => $schema,
            'name'   => $table,
          ]);
    }
    public function handle() {
    
        echo "Ok";
        exit;
      $db = db($this->input('dsn'));

      $this->getColumns($db, $schema, $table);

      $table = $this->input('table');
      $table = explode('.', $table);
      if(empty($table[1])) {
        $table = array('public', $table[0]);
      }
      
      if(empty($columns)) {
        abort('no columns');
      }

      $columns = $columns
        ->filter(function($c) {
          return !in_array($c->column_name, ['id']);
        })
        ->map(function($c) {
        return [
          'name' => $c->column_name,
          'type' => $c->data_type,
          'cast' => static::castCompare($c->data_type),
        ];
      });

      $model      = 'app/Models/' . $this->input('model') . '.php';
      $controller = 'app/Http/Controllers/' . $this->input('model') . 'Controller.php';
      $views      = 'resources/views/' . str($this->input('model'))->studlyToSnake() . '/';
      $tableview  = 'app/Http/Nexus/Views/';

    }
}
