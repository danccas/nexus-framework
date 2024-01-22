<?php

namespace Core\Nexus;

use Core\Nexus\Tablefy;
use Core\View;

class Action implements \JsonSerializable {
  protected $uid   = null;
  protected $index = null;
  protected $title = null;
  protected $icon  = null;
  protected $link  = null;
  protected $model = null;
  protected $cb    = null;
  protected $response = null;
  protected $ajax  = false;
  protected $attrs = [];

  function __construct($title = null) {
    if(!is_null($title)) {
      $this->title = $title;
    }
  }
  public function click($callback) {
    $this->cb = $callback;
    return $this;
  }
  public function attr($key, $value) {
    $this->attrs[$key] = $value;
    return $this;
  }
  public function handle($model) {
    return ($this->cb)($model);
  }
  public function setIndex($idx) {
    $this->index = $idx;
    return $this;
  }
  public function index() {
    return $this->index;
  }
  public function uid() {
    return $this->uid;
  }
  public static function title($title) {
    return new static($title);
  }
  public function icon($val) {
    $this->icon = $val;
    return $this;
  }
  public function ajax($tt) {
    $this->ajax = $tt;
    return $this;
  }
  public function link($val) {
    $this->link = $val;
    return $this;
  }
  public function route($name, $params = []) {
    $this->link = route($name, $params)->link();
    return $this;
  }
  public function prepare(Tablefy $tableView) {
    $this->uid   = md5($this->index . '-' . $this->title . '-' . __FILE__);
    $this->model = $tableView;
    $this->response = array_filter([
      'uid'   => $this->uid,
      'title' => $this->title,
      'icon'  => $this->icon,
      'link'  => $this->link,
      'ajax'  => $this->ajax,
      'attrs' => $this->attrs,
    ]);
  }

  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    if(!empty($this->model)) {
      $this->response['base'] = $this->model->route()->link();
    }
    return $this->response;
  } 
  public function __toString() {
    return $this->name;
  }
}
