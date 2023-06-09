<?php
namespace Core;

use Core\Route;

class RouteResource {
    protected $regex;
    protected $params = [];
    protected $prefix = null;
    protected $middleware = null;
    protected $routes = [];

    function __construct($regex, $controller)
    {
        $this->regex = $regex;
        $this->routes = array(
            'index'    => (new Route)->setMethod('GET')->setRegex($regex)->setController($controller . '@index'),
            'tablefy'    => (new Route)->setMethod('POST')->setRegex($regex . '/tablefy')->setController($controller . '@tablefy'),
            'create'   => (new Route)->setMethod('GET')->setRegex($regex . '/create')->setController($controller . '@create'),
            'store'    => (new Route)->setMethod('POST')->setRegex($regex)->setController($controller . '@store'),
            'show'     => (new Route)->setMethod('GET')->setRegex($regex . '/:' . $regex)->setController($controller . '@show'),
            'edit'     => (new Route)->setMethod('GET')->setRegex($regex . '/:' . $regex . '/edit')->setController($controller . '@edit'),
            'update'   => (new Route)->setMethod('put')->setRegex($regex . '/:' . $regex)->setController($controller . '@update'),
            'destroy'  => (new Route)->setMethod('DELETE')->setRegex($regex . '/:' . $regex)->setController($controller . '@destroy'),
        );
        $this->name($regex);
    }
    function name($name) {
        foreach($this->routes as $k => $r) {
            if(in_array($k, ['tablefy'])) {
                $r->name($name . '.' . $k)->permission($name . '.index');
            } else {
                $r->name($name . '.' . $k);
            }
        }
        return $this;
    }

    function middleware($midd) {
        foreach($this->routes as $k => $r) {
            $r->middleware($midd);
        }
        return $this;
    }

    function parameter($name) {
        $reem = ':' . $this->regex;
        foreach($this->routes as $k => $r) {
            $r->setRegex(str_replace($reem, ':' . $name, $r->getRegex()));
        }
        return $this;
    }
}