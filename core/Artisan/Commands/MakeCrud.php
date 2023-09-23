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
    protected $description = 'Leer Correo';

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
    public function handle() {
      if($this->input('dsn') === null) {
        abort('no dsn');
      }
      if($this->input('table') === null) {
        abort('no table');
      }
      if($this->input('model') === null) {
        abort('no model');
      }

      $db = db($this->input('dsn'));

      $table = $this->input('table');
      $table = explode('.', $table);
      if(empty($table[1])) {
        $table = array('public', $table[0]);
      }
      $columns = $db->get("SELECT * FROM information_schema.columns WHERE table_schema = :schema AND table_name = :name", [
        'schema' => $table[0],
        'name'   => $table[1],
      ]);
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

      $this->createModel(app()->dir() . $model, $this->input('dsn'), $this->input('table'), $this->input('model'), $columns);

      $this->createController(app()->dir() . $controller, $this->input('model'), $columns);

      $this->createViews(app()->dir() . $views, $this->input('model'), $columns);

      $this->createTableView(app()->dir() . $tableview , $this->input('table'), $this->input('model'), $columns);

      $view = str($this->input('model'))->studlyToSnake();
      if(!Route::exists($view . '.index')) {
        echo "Register Route: " . $view . "\n";
        $code = "

## Automatic Code
Route::resource('" . $view.  "es', 'App\\Http\\Controllers\\" . $this->input('model') . "Controller')->parameter('" . $view . "');

";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }
    }

    private function createModel($file, $dsn, $table, $name, $columns) {
      if(file_exists($file)) {
        echo "File exists: app\\Models\\" . $name . ".php\n";
        //return;
      }

      $format = __DIR__ . '/../../Formats/model.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'dsn'   => $dsn,
        'table' => $table,
        'model' => $name,
        'columns' => $columns,
      ])->render();
      file_put_contents($file, "<?php\n" . $code);
      echo "Created: app\\Models\\" . $name . ".php\n";
    }
    private function createController($file, $model) {
      if(file_exists($file)) {
        echo "File exists: app\\Http\\Controllers\\" . $model . "Controller.php\n";
//        return;
      }
      $view = str($model)->studlyToSnake();
      $format = __DIR__ . '/../../Formats/controller.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      file_put_contents($file, $code);
      echo "Created: app\\Http\\Controllers\\" . $model . "Controller.php\n";
    }
    private function createTableView($directory, $table, $model, $columns) {
      @mkdir(app()->dir() . 'app/Http/Nexus');
      @mkdir(app()->dir() . 'app/Http/Nexus/Views');
      @mkdir(app()->dir() . 'app/Http/Nexus/Actions');
      if(!file_exists($directory . '../')) {
        @mkdir($directory . '../');
      }
      $view = str($model)->studlyToSnake();
      $format = __DIR__ . '/../../Formats/tableview.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'table' => $table,
        'model' => $model,
        'view'  => $view,
        'columns' => $columns
      ])->render();
      $file = $directory . $model . 'TableView.php';
      file_put_contents($file, "<?php\n" . $code);
      echo "Created: app\\Http\\Nexus\\Views\\" . $model . "TableView.php\n";
    }
    private function createViews($directory, $model) {
      if(!file_exists($directory)) {
        mkdir($directory);
        echo "Created directory Views: " . $model . "\n";
      }
      $view = str($model)->studlyToSnake();

      $format = __DIR__ . '/../../Formats/view_index.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'index.blade.php';
      file_put_contents($file, $code);

      $format = __DIR__ . '/../../Formats/view_show.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'show.blade.php';
      file_put_contents($file, $code);

      $format = __DIR__ . '/../../Formats/view_edit.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'edit.blade.php';
      file_put_contents($file, $code);

      $format = __DIR__ . '/../../Formats/view_form.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'form.blade.php';
      file_put_contents($file, $code);

      $format = __DIR__ . '/../../Formats/view_create.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'create.blade.php';
      file_put_contents($file, $code);

      echo "Created Views: " . $model . "\n";
    }
}
