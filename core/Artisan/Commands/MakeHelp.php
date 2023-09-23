<?php
namespace Core\Artisan\Commands;

use Core\Artisan\Command;
use Core\Blade;
use Core\Route;

class MakeHelp
{
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
    public static function getColumns($db, $table) {
      $table = explode('.', $table);
      if(empty($table[1])) {
        $table = array('public', $table[0]);
      }
      return $db->get("SELECT * FROM information_schema.columns WHERE table_schema = :schema AND table_name = :name", [
          'schema' => $table[0],
          'name'   => $table[1],
      ]);
    }
    public static function createMethod($file, $name, $arguments, $code, $return) {
      if(!file_exists($file)) {
        return false;
      }
      $format = __DIR__ . '/../../Formats/method.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'name'   => $name,
        'arguments' => $arguments,
        'code'   => $code,
        'return' => $return,
      ])->render();

      $realCode = file_get_contents($file);
      $realCode = trim($realCode);
      $realCode = trim($realCode, '}');

      $realCode .= "\n" . $code;

      file_put_contents($file, $realCode . "\n}");
      echo "Append: {$file} \n";
    }
    public static function createModel($file, $dsn, $table, $name, $columns) {
      if(file_exists($file)) {
        echo "File exists: app\\Models\\" . $name . ".php\n";
        return;
      }
      $view = str($name)->studlyToSnake();
      $format = __DIR__ . '/../../Formats/model.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'dsn'   => $dsn,
        'table' => $table,
        'model' => $name,
        'view'  => $view,
        'columns' => $columns,
      ])->render();

      file_put_contents($file, "<?php\n" . $code);
      echo "Created: app\\Models\\" . $name . ".php\n";
    }
    public static function createController($file, $model) {
      if(file_exists($file)) {
        echo "File exists: app\\Http\\Controllers\\" . $model . "Controller.php\n";
        return;
      }
      $view = str($model)->studlyToSnake();
      $format = __DIR__ . '/../../Formats/controller.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      file_put_contents($file, "<?php\n" . $code);
      echo "Created: app\\Http\\Controllers\\" . $model . "Controller.php\n";
    }
    public static function createTableView($file, $name, $table, $model, $columns) {
      @mkdir(app()->dir() . 'app/Http/Nexus');
      @mkdir(app()->dir() . 'app/Http/Nexus/Views');
      @mkdir(app()->dir() . 'app/Http/Nexus/Actions');
      $view = str($model)->studlyToSnake();
      $format = __DIR__ . '/../../Formats/tableview.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'name'  => $name,
        'table' => $table,
        'model' => $model,
        'view'  => $view,
        'columns' => $columns
      ])->render();
      if(!file_exists($file)) {
        file_put_contents($file, "<?php\n" . $code);
        echo "Created: {$file}\n";
      }
    }
    public static function createViews($directory, $model, $columns) {
      if(!file_exists($directory)) {
        mkdir($directory);
        echo "Created directory Views: " . $model . "\n";
      }
      $view = str($model)->studlyToSnake();

      $format = __DIR__ . '/../../Formats/view_index.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'create' => $view . '.create',
        'repository'  => $view . '.repository',
      ])->render();
      $file = $directory . 'index.blade.php';
      if(!file_exists($file)) {
        file_put_contents($file, $code);
      }

      $format = __DIR__ . '/../../Formats/view_show.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
        'columns' => $columns,
      ])->render();
      $file = $directory . 'show.blade.php';
      if(!file_exists($file)) {
        file_put_contents($file, $code);
      }

      $format = __DIR__ . '/../../Formats/view_edit.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'edit.blade.php';
      if(!file_exists($file)) {
        file_put_contents($file, $code);
      }

      $format = __DIR__ . '/../../Formats/view_form.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'form.blade.php';
      if(!file_exists($file)) {
        file_put_contents($file, $code);
      }

      $format = __DIR__ . '/../../Formats/view_create.blade.php';
      $code = (new Blade($format))->verbose(false)->append([
        'model' => $model,
        'view'  => $view,
      ])->render();
      $file = $directory . 'create.blade.php';
      if(!file_exists($file)) {
        file_put_contents($file, $code);
      }
      echo "Created Views: " . $model . "\n";
    }
}
