<?php
namespace Core;

class Controller {
  protected $route;

  public function library($name) {
    ##
  }
  public function middleware($name) {
    ##
  }
  public function setRoute(Route $route) {
    $this->route = $route;
    return $this;
  }
}