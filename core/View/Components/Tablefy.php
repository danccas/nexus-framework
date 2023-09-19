<?php
namespace Core\View\Components;
use Core\View\Component;
use Core\Blade;
use Core\Route;
use Core\Nexus\Tablefy as TablefyCore;

class Tablefy extends Component {

  protected $model = null;
  protected $route = null;
  protected $enumerate = false;
  protected $selectable =  false;
  protected $contextmenu = true;
  protected $draggable = false;
  protected $sorter = true;
  protected $countSelectable = 5;
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

    public function mount($attrs) {
      $this->message = $attrs;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
      $route = route($this->route);
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
      $tuq = 't' . uniqid();
      $params = [
        'dom' => '#' . $tuq,
        'request' => [
          'url' => $route->link(),
          'type' => 'POST',
          'data' => 'tablefy_filters',
        ],
        'enumerate' => $this->enumerate,
        'selectable' => $this->selectable,
        'contextmenu' => $this->contextmenu,
        'draggable'   => $this->draggable,
        'sorter'      => $this->sorter,
        'countSelectable' => $this->countSelectable,
      ];
      if(!empty($headers)) {
        $params['headers'] = $headers;
      }
      $params = array_filter($params);
      foreach($this->attrs() as $key => $val) {
        $params[$key] = $val;
      }
      Blade::preCoding('styles', '<link href="/assets/libs/tablefy/tablefy.min.css" rel="stylesheet" type="text/css" />');
      Blade::preCoding('scripts', '<script>'
                    . "require(['/assets/libs/tablefy/tablefy.min.js?<?= time() ?>'], function() {"
                    . 'var ' . $tuq . " = new Tablefy(" . json_encode($params) . ').init(true);'
                    . "});"
                    . '</script>');
      return '<table id="' . $tuq . '"></table>' . $this->message;
    }
}
