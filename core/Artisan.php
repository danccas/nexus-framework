<?php

namespace Core;


class Artisan {
  protected $argc = null;
  protected $argv = null;

  static $cmds = [];

  protected $controller = null;

  function __construct($app, $argc, $argv) {
    $this->argc = $argc;
    $this->argv = $argv;
    $this->prepareNatives();
    foreach($app->commands as $cc) {
      $this->addConsole($cc);
    }
    $this->prepareArguments();
  }
  public function addCmd($name, $where) {
    static::$cmds[$name] = $where;
    //echo "registrar: " . $name . "\n";
  }
  public function addConsole($prev) {
    $ins = new ($prev);
    $this->addCmd($ins->getSignature(), $ins);
    return $this;
  }
  private function prepareNatives() {
    foreach([
      \Core\Artisan\Commands\MakeCrud::class,
      \Core\Artisan\Commands\MakeList::class,
    ] as $cc) {
      static::addConsole($cc);
    }
  }
  private function prepareArguments() {
    $section = !empty($this->argv[1]) ? $this->argv[1] : null;
    if(empty($section)) {
      abort(404, 'no command');
    }
    if(!isset(static::$cmds[$section])) {
      abort('no exists command');
    }
    $this->controller = new (static::$cmds[$section]);
    $this->controller->setArgs($this->argv);
  }
  public function output() {
    $this->controller->handle();
  }
}
