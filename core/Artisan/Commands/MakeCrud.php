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
      input_db:
      $dsn = prompt('dsn:', $this->input('dsn'), true);
      if(!db($dsn)->existsConnection()) {
        echo o('No existe conexión', 'RED');
        goto input_db;
      }

      $db = db($dsn);
      $nameContext = prompt('Name Context (Optional)', [$this->input('context')], true);
      $nameContext = trim($nameContext, '/');
      $nameTable = prompt('Name Table', $this->input('table'), false);
      $nameModel = prompt('Name Model', $this->input('model'), false);
      $nameInstance = prompt('Name Instance', [$this->input('instance'), str($nameModel)->snakeCase()], false);
      $nameTableView  = prompt('Name TableView (without TableView)', [$this->input('tableView'), $nameModel], false);

      $nameController   = prompt('Name Controller', [$this->input('controller'), $nameModel . 'Controller'], false);

      $methodAlias = $nameTableView;
      $methodAlias = str_replace($nameModel .'es', '', $methodAlias);
      $methodAlias = str_replace($nameModel .'s', '', $methodAlias);
      $methodAlias = str_replace($nameModel, '', $methodAlias);
      $methodAlias = str($methodAlias)->studlyToSnake();

      $nameViewDir     = prompt('Name Directory Views', [$this->input('dirView'), str($nameModel)->snakeCase()], false);

      $uriRoute        = prompt('URL Main Route', [$this->input('route'), str($nameModel)->snakeCase() . 's'], false);
      $nameRoute       = prompt('Name Main Route', [$this->input('nameRoute'), $nameViewDir], false);

      $confirm = prompt('Confirm? y/n', [], false);

      if(!in_array($confirm, ['y','Y','yes'])) {
        echo "Canceled!";
        exit;
      }
      if(!empty($nameContext)) {
        $file_controller = app()->dir() . 'app/Http/Controllers/' . $nameContext . '/' . $nameController . '.php';
      } else {
        $file_controller = app()->dir() . 'app/Http/Controllers/' . $nameController . '.php';
      }
      if(!file_exists($file_controller)) {
        MakeHelp::createController($file_controller, $nameContext, $nameModel, $nameInstance, $nameViewDir);
      }

      $columns = MakeHelp::getColumns($db, $nameTable);
      if(empty($columns)) {
        return o("No existen columnas");
      }
      $columns = $columns
      ->filter(function($c) {
          return !in_array($c->column_name, ['id']);
      })
      ->map(function($c) {
        return [
          'name' => $c->column_name,
          'type' => $c->data_type,
          'cast' => MakeHelp::castCompare($c->data_type),
        ];
      })
      ->take(8);
      if(!empty($nameContext)) {
        $file_tableview = app()->dir() . 'app/Http/Nexus/Views/' . $nameContext . '/' . $nameTableView . 'TableView.php';
      } else {
        $file_tableview = app()->dir() . 'app/Http/Nexus/Views/' . $nameTableView . 'TableView.php';
      }
      if(!file_exists($file_tableview)) {
        echo "TableView Created: {$file_tableview}\n";
        MakeHelp::createTableView($file_tableview, $nameTableView, $nameContext, $nameTable, $nameModel, $nameInstance, $columns, $nameViewDir);
      }
      if(!empty($nameContext)) {
        $directory = app()->dir() . 'resources/views/' . str($nameContext)->lower() . '/' . $nameViewDir . '/';
      } else {
        $directory = app()->dir() . 'resources/views/' . $nameViewDir . '/';
      }
      MakeHelp::createViews($directory, $nameContext, $nameModel, $columns, $nameViewDir, $nameInstance);


      if(!empty($nameContext)) {
        $file_model = app()->dir() . 'app/Models/' . $nameContext . '/' . $nameModel . '.php';
      } else {
        $file_model = app()->dir() . 'app/Models/' . $nameModel . '.php';
      }

      MakeHelp::createModel($file_model, $dsn, $nameContext, $nameTable, $nameModel, $columns);


      $view = str($this->input('model'))->studlyToSnake();

      if(!empty($nameContext)) {
        $nameRoute2 = str($nameContext)->lower() . '.' . $nameRoute;
        $nameTableView = str_replace('/', '\\', $nameContext) . '\\' . $nameTableView;
        $nameController = str_replace('/', '\\', $nameContext) . '\\' . $nameController;
        $uriRoute = str($nameContext)->lower() . '/' . $uriRoute;
      }
      if(!Route::exists($nameRoute2 . '.repository') || !Route::exists($nameRoute2 . '.index')) {
        $code = "\n\n## Automatic Code:\n";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);

        if(!Route::exists($nameRoute2 . '.repository')) {
          echo "Register Route:\n";
          $code = "Route::post('" . $uriRoute.  "/repository', 'App\\Http\\Nexus\\Views\\" . $nameTableView . "TableView')->name('" . $nameRoute2 . ".repository');\n";
          file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
        }
        if(!Route::exists($nameRoute2 . '.index')) {
          echo "Register RouteResources: " . $view . "\n";
          $code = "Route::resource('" . $uriRoute.  "', 'App\\Http\\Controllers\\" . $nameController . "')->parameter('" . $nameInstance . "')->name('" . $nameRoute2 . "');\n";
          file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
        }
      }
    }
}
