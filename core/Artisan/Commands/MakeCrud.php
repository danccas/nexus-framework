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

      $nameTable = prompt('Name Table', $this->input('table'), false);
      $nameModel = prompt('Name Model', $this->input('model'), false);
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


      $file_controller = app()->dir() . 'app/Http/Controllers/' . $nameController . '.php';
      if(!file_exists($file_controller)) {
        MakeHelp::createController($file_controller, $nameModel);
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

      $file_tableview = app()->dir() . 'app/Http/Nexus/Views/' . $nameTableView . 'TableView.php';
      if(!file_exists($file_tableview)) {
        echo "TableView Created: {$file_tableview}\n";
        MakeHelp::createTableView($file_tableview, $nameTableView, $nameTable, $nameModel, $columns);
      }

      $directory = app()->dir() . 'resources/views/' . $nameViewDir . '/';
      MakeHelp::createViews($directory, $nameModel, $columns);


      MakeHelp::createModel(app()->dir() . 'app/Models/' . $nameModel . '.php', $dsn, $nameTable, $nameModel, $columns);


      $view = str($this->input('model'))->studlyToSnake();
      if(!Route::exists($nameRoute . '.repository')) {
        echo "Register Route: " . $view . "\n";
        $code = "

## Automatic Code
Route::post('" . $uriRoute.  "/repository', 'App\\Http\\Nexus\\Views\\" . $nameTableView . "TableView')->name('" . $nameRoute . ".repository');

";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }

      if(!Route::exists($nameRoute . '.index')) {
        echo "Register RouteResources: " . $view . "\n";
        $code = "

## Automatic Code
Route::resource('" . $uriRoute.  "', 'App\\Http\\Controllers\\" . $nameController . "')->parameter('" . $nameRoute . "');

";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }
    }
}
