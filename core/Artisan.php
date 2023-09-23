<?php

namespace Core;


class Artisan {
  protected $argc = null;
  protected $argv = null;

  static $cmds = [];

  protected $controller = null;

  function __construct($argc, $argv) {
    $this->argc = $argc;
    $this->argv = $argv;
    $this->prepareNatives();
    $this->prepareArguments();
  }
  public function addCmd($name, $where) {
    static::$cmds[$name] = $where;
  }
  private function prepareNatives() {
    foreach([
      'make:crud' => \Core\Artisan\Commands\MakeCrud::class,
    ] as $key => $cc) {
      static::addCmd($key, $cc);
    }
  }
  private function prepareArguments() {
    $section = $this->argv[1];
    if(empty($section)) {
      abort(404, 'no command');
    }
    $this->controller = new (static::$cmds[$section]);
    $this->controller->setArgs($this->argv);
  }
  public function output() {
    $this->controller->handle();
  }
}
