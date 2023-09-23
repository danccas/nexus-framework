<?php
namespace Core\Artisan\Commands;

use Core\Artisan\Command;
use Core\Blade;
use Core\Route;
use Core\Artisan\Commands\MakeHelp;

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
      $columns = MakeHelp::getColumns($db, $table);
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
          'cast' => MakeHelp:castCompare($c->data_type),
        ];
      });

      $model      = 'app/Models/' . $this->input('model') . '.php';
      $controller = 'app/Http/Controllers/' . $this->input('model') . 'Controller.php';
      $views      = 'resources/views/' . str($this->input('model'))->studlyToSnake() . '/';
      $tableview  = 'app/Http/Nexus/Views/';

      MakeHelp::createModel(app()->dir() . $model, $this->input('dsn'), $this->input('table'), $this->input('model'), $columns);

      MakeHelp::createController(app()->dir() . $controller, $this->input('model'), $columns);

      MakeHelp::createViews(app()->dir() . $views, $this->input('model'), $columns);

      MakeHelp::createTableView(app()->dir() . $tableview . $this->input('model') . 'TableView.php', $this->input('model'), $this->input('table'), $this->input('model'), $columns);

      $view = str($this->input('model'))->studlyToSnake();
      if(!Route::exists($view . '.index')) {
        echo "Register Route: " . $view . "\n";
        $code = "

## Automatic Code
Route::post('" . $view.  "s/repository', 'App\\Http\\Nexus\\Views\\" . $this->input('model') . "TableView')->name('" . $view . ".repository');
Route::resource('" . $view.  "s', 'App\\Http\\Controllers\\" . $this->input('model') . "Controller')->parameter('" . $view . "');

";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }
    }
}
