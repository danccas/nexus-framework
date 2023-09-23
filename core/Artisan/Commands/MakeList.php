<?php
namespace Core\Artisan\Commands;

use Core\Artisan\Command;
use Core\Blade;
use Core\Route;

class MakeList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:list';

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

      $methodController = prompt('Method Controller', [$this->input('method'), $methodAlias, 'index'], false);

      $nameViewDir     = prompt('Name Directory Views', [$this->input('dirView'), str($nameModel)->snakeCase()], false);
      $nameView        = prompt('Name File View', [$this->input('view'), $methodAlias, 'index'], false);

      $uriRoute        = prompt('URL Main Route', [$this->input('route'), ($methodController == 'index' ? null : str($nameModel)->snakeCase() . 's/' . $methodController), str($nameModel)->snakeCase() . 's'], false);
      $nameRoute       = prompt('Name Main Route', [$this->input('nameRoute'), str_replace('/', '.', $uriRoute)], false);
      $uriRouteRepo    = prompt('URL Repository Route', [$this->input('routeRepo'), $uriRoute . '/repository'], false);
      $nameRouteRepo   = prompt('Name Repository Route', [$this->input('nameRouteRepo'), str_replace('/', '.', $uriRouteRepo)], false);

      $file_controller = app()->dir() . 'app/Http/Controllers/' . $nameController . '.php';
      if(!file_exists($file_controller)) {
        MakeHelp::createController($file_controller, $nameModel);
      }
      if(method_exists(('App\\Http\\Controllers\\' . $nameController), $methodController)) {
        echo "Contoller Exists: {$nameController}->{$methodController}\n";
      } else {
        echo "Created Method: {$nameController}->{$methodController}\n";
        MakeHelp::createMethod($file_controller, $methodController, '', '', "view('" . $nameViewDir . "." . $nameView . "')");
      }

      $columns = MakeHelp::getColumns($db, $nameTable);
      if(empty($columns)) {
        return o("No existen columnas");
      }
      $columns
      ->filter(function($c) {
          return !in_array($c->column_name, ['id']);
      })
      ->map(function($c) {
        return [
          'name' => $c->column_name,
          'type' => $c->data_type,
          'cast' => MakeHelp::castCompare($c->data_type),
        ];
      });

      $file_tableview = app()->dir() . 'app/Http/Nexus/Views/' . $nameTableView . 'TableView.php';
      if(!file_exists($file_tableview)) {
        echo "TableView Created: {$file_tableview}\n";
        MakeHelp::createTableView($file_tableview, $nameTableView, $nameTable, $nameModel, $columns);
      }
      $format = __DIR__ . '/../../Formats/view_index.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'create'     => false,
        'repository' => $nameRouteRepo,
      ])->render();
      $file = app()->dir() . 'resources/views/' . $nameViewDir . '/' . $nameView . '.blade.php';
      if(!file_exists($file)) {
        echo "Created: {$file}\n";
        file_put_contents($file, $code);
      }

      if(!Route::exists($nameRouteRepo)) {
        echo "Register Route: " . $nameRouteRepo . "\n";
        $code = "
## Automatic Code
Route::post('" . $uriRouteRepo . "', 'App\\Http\\Nexus\\Views\\" . $nameTableView . "TableView')->name('" . $nameRouteRepo . "');
";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }

      if(!Route::exists($nameRoute)) {
        echo "Register Route: " . $nameRoute . "\n";
        $code = "
## Automatic Code
Route::get('" . $uriRoute . "', 'App\\Http\\Controllers\\" . $nameController . "@" . $methodController . "')->name('" . $nameRoute . "');
";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }

      echo "End;\n";
    }
}
