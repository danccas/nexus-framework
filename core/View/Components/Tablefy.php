<?php
namespace Core\View\Components;
use Core\View\Component;
use Core\Blade;
use Core\Route;
use Core\Nexus\Tablefy as TablefyCore;
use Core\JSON;

class Tablefy extends Component {

  protected $uniq  = null;
  protected $model = null;
  protected $route = null;
  protected $filter = null;
  protected $enumerate = false;
  protected $selectable =  false;
  protected $contextmenu = true;
  protected $draggable = false;
  protected $sorter = true;
  protected $countSelectable = 5;
  protected $tocallfilter = null;
  protected $fndata = null;
  protected $start = true;
  protected $refresh = true;
  protected $search  = true;
  protected $message;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function mount() {
      $this->uniq = 'tt' . uniqid();
      \Core\Blade::partView('styles', function($params) {
        echo '<link href="/assets/libs/tablefy/tablefy.min.css" rel="stylesheet" type="text/css" />';
      });
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {

      if(!($this->route instanceof Route)) {
        $route = route($this->route);
      } else {
        $route = $this->route;
      }
      if(!($route instanceof Route)) {
        kernel()->exception('<nexus:tablefy> require a valid route.');
      }
      $this->model = ($route->getController()[0]);

      if(!class_exists($this->model)) {
        kernel()->exception('<nexus:tablefy> require a valid route. (class)');
      }

      $this->model = new ($this->model);

      $headers = [];
      if($this->model instanceof TablefyCore) {
        $this->model->setRoute($route);
        $headers = $this->model->getHeaders();
      }
      $params = [
        'dom' => '#' . $this->uniq,
        'request' => [
          'url' => $route->link(),
          'type' => 'POST',
          'data' => $this->fndata ?? 'tablefy_data',
        ],
        'toCallFilter' => $this->tocallfilter,
        'enumerate' => $this->enumerate,
        'selectable' => $this->selectable,
        'contextmenu' => $this->contextmenu,
        'draggable'   => $this->draggable,
        'sorter'      => $this->sorter,
        'countSelectable' => $this->countSelectable,
        'start' => $this->start,
        'refresh' => $this->refresh,
        'search'  => $this->search,
      ];
      if(!empty($headers)) {
        $params['headers'] = $headers;
      }
      $params = array_filter($params);
      foreach($this->attrs() as $key => $val) {
        //if(!isset($params[$key])) {
          $params[$key] = $val;
        //}
      }
      return '<table id="' . $this->uniq . '"></table>'.
        '<script>'
                    . "require(['/assets/libs/tablefy/tablefy.min.js?<?= time() ?>'], function() {"
                    . 'var ' . $this->uniq . " = new Tablefy(" . \Core\JSON::encode($params) . ').init();'
                    . "});"
                    . '</script>';
    }
}
