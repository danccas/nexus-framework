<?php
namespace Core\Artisan;

class Command {
  protected $attrs = [];

  function __construct() {
  }
  function setArgs($args) {
    $args = array_slice($args, 2);
    foreach($args as $val) {
      preg_match("/^\-\-(?<name>[\w]+)\=(?<valor>[\w\W]+)$/", $val, $match);
      if(!empty($match['valor'])) {
        $this->{$match['name']} = $match['valor'];
      }
    }
  }
  function input($name) {
    if(!isset($this->attrs[$name])) {
      return null;
    }
    return $this->attrs[$name];
  }
  function __set($name, $value) {
    $this->attrs[$name] = $value;
    return $this;
  }
  function __get($name) {
    return $this->attrs[$name];
  }
}
