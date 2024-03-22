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

      $methodController = prompt('Method Controller', [$this->input('method'), $methodAlias, 'index'], false);

      $nameViewDir     = prompt('Name Directory Views', [$this->input('dirView'), str($nameModel)->snakeCase()], false);
      $nameView        = prompt('Name File View', [$this->input('view'), $methodAlias, 'index'], false);

      $uriRoute        = prompt('URL Main Route', [$this->input('route'), ($methodController == 'index' ? null : str($nameModel)->snakeCase() . 's/' . $methodController), str($nameModel)->snakeCase() . 's'], false);
      $nameRoute       = prompt('Name Main Route', [$this->input('nameRoute'), ($methodController == 'index' ? $nameViewDir : $nameViewDir . '.' . $methodAlias)], false);
      $uriRouteRepo    = prompt('URL Repository Route', [$this->input('routeRepo'), $uriRoute . '/repository'], false);
      $nameRouteRepo   = prompt('Name Repository Route', [$this->input('nameRouteRepo'), $nameRoute . '.repository'], false);

      $confirm = prompt('Confirm? y/n', [], false);

      if(!in_array($confirm, ['y','Y','yes'])) {
        echo "Canceled!";
        exit;
      }
      $nameController2 = $nameController;
      if(!empty($nameContext)) {
        $nameController2 = $nameContext . '\\' . $nameController;
        $file_controller = app()->dir() . 'app/Http/Controllers/' . $nameContext . '/' . $nameController . '.php';
      } else {
        $file_controller = app()->dir() . 'app/Http/Controllers/' . $nameController . '.php';
      }
      if(!file_exists($file_controller)) {
        MakeHelp::createController($file_controller, $nameContext, $nameModel, $nameInstance, $nameViewDir);
      }
      if(method_exists(('App\\Http\\Controllers\\' . $nameController2), $methodController)) {
        echo "Contoller Exists: {$nameController2}->{$methodController}\n";
      } else {
        echo "Created Method: {$nameController2}->{$methodController}\n";
        $nameViewDir2 = $nameViewDir;
        if(!empty($nameViewDir2)) {
          $nameViewDir2 = str($nameContext)->lower() . '.' . $nameViewDir;
        }
        MakeHelp::createMethod($file_controller, $methodController, '', '', "view('" . $nameViewDir2 . "." . $nameView . "')");
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
        $file_model = app()->dir() . 'app/Models/' . $nameContext . '/' . $nameModel . '.php';
      } else {
        $file_model = app()->dir() . 'app/Models/' . $nameModel . '.php';
      }

      MakeHelp::createModel($file_model, $dsn, $nameContext, $nameTable, $nameModel, $columns);

    if(!empty($nameContext)) {
        $file_tableview = app()->dir() . 'app/Http/Nexus/Views/' . $nameContext . '/' . $nameTableView . 'TableView.php';
      } else {
        $file_tableview = app()->dir() . 'app/Http/Nexus/Views/' . $nameTableView . 'TableView.php';
      }
      if(!file_exists($file_tableview)) {
        echo "TableView Created: {$file_tableview}\n";
        MakeHelp::createTableView($file_tableview, $nameTableView, $nameContext, $nameTable, $nameModel, $nameInstance, $columns, $nameViewDir);
      }


      $nameViewDir2 = $nameViewDir;
      $nameRouteRepo2 = $nameRouteRepo;
      if(!empty($nameContext)) {
        $nameViewDir2 = str($nameContext)->lower() . '/' . $nameViewDir;
        $nameRouteRepo2 = str($nameContext)->lower() . '.' . $nameRouteRepo;
      }
      $format = __DIR__ . '/../../Formats/view_index.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'create'     => false,
        'repository' => $nameRouteRepo2,
        'instance'   => $nameInstance,
      ])->render();
      @mkdir(app()->dir() . 'resources/views/' . $nameViewDir2 . '/', 0777, true);
      $file = app()->dir() . 'resources/views/' . $nameViewDir2 . '/' . $nameView . '.blade.php';
      if(!file_exists($file)) {
        echo "Created: {$file}\n";
        file_put_contents($file, $code);
      }

      if(!empty($nameContext)) {
        $nameRoute2 = str($nameContext)->lower() . '.' . $nameRoute;
        $nameRouteRepo = str($nameContext)->lower() . '.' . $nameRouteRepo;
        $uriRoute = str($nameContext)->lower() . '/' . $uriRoute;
        $uriRouteRepo = str($nameContext)->lower() . '/' . $uriRouteRepo;
        $nameController = str_replace('/', '\\', $nameContext) . '\\' . $nameController;
        $nameTableView = str_replace('/', '\\', $nameContext) . '\\' . $nameTableView;
      }
      if(!Route::exists($nameRoute2 . '.repository') || !Route::exists($nameRoute2 . '.index')) {
        $code = "\n\n## Automatic Code:\n";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      if(!Route::exists($nameRouteRepo)) {
        echo "Register Route: " . $nameRouteRepo . "\n";
        $code = "Route::post('" . $uriRouteRepo . "', 'App\\Http\\Nexus\\Views\\" . $nameTableView . "TableView')->name('" . $nameRouteRepo . "');\n";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }

      if(!Route::exists($nameRoute2)) {
        echo "Register Route: " . $nameRoute2 . "\n";
        $code = "Route::get('" . $uriRoute . "', 'App\\Http\\Controllers\\" . $nameController . "@" . $methodController . "')->name('" . $nameRoute2 . "');\n";
        file_put_contents(app()->dir() . 'routes/web.php', $code, FILE_APPEND);
      }
      }
      echo "\n";
      echo o('Link: /' . $uriRoute, 'WHITE');
      echo "\n";
      echo "End;\n";
    }
}
