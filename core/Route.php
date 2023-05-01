<?php

namespace Core;

use Closure;
use Core\Application;
use Core\Kernel;
use stdClass;

class Route
{
    public $method;
    private $prepared;
    public $regex; ##TODO
    public $callback; ###TODO;
    private $controller;
    private $context;
    private $request;
    private $response;
    private $url;
    private $father;
    private $name;
    private $permission;
    private $parameters = [];
    private $request_params = [];

    protected $middlewares = [];

    function __construct()
    {
        if (Application::instance()->canRegisterRoute()) {
            $this->context = str_replace('.php', '', basename(Application::instance()->currentConfigRoute()));
            Kernel::instance()->registerRoute($this);
        }
    }
    public function getContext()
    {
        return $this->context;
    }
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getLabels() {
        $rp = [];
        $rp[] = $this->name;
        $rp[] = $this->permission;
        $rp = array_filter($rp);
        return $rp;
    }
    public function permission($name)
    {
        $this->permission = $name;
        return $this;
    }
    public function getPermission()
    {
        return $this->permission;
    }
    public function compare($path)
    {
        return $this->name == $path || $this->regex == $path;
    }
    public function link($prepared = null)
    {
        $ce = $this;
        $expresion_regular = preg_replace_callback("/\:(?<id>[\w_]+)\;?/", function ($n) use ($ce, $prepared) {
            if (!is_array($prepared)) {
                exit('Error: debería ser un array');
            }
            if (!isset($prepared[$n['id']])) {
                exit('Error: no existe el indice requerido');
            }
            $res = $prepared[$n['id']];
            if ($res instanceof Model) {
                $res = $res->{$res->getPrimaryKey()};
            }
            return $res;
        }, $this->regex, -1, $cantidad);
        return '/' . $expresion_regular;
    }

    public function isMatch($request)
    {
        if ($this->isMatchMethod($request)) {
            if ($this->isMatchURL($request)) {
                if ($this->isMatchArguments($request)) {
                    return true;
                }
            }
        }
        return false;
    }
    public function isMatchArguments($request)
    {
        if ($this->isControllerValid()) {
            $reflections = $this->reflectionController();
            if ($this->injectionMethodValid($reflections)) {
                return true;
            }
        }
        return false;
    }
    public function isMatchMethod($request)
    {
        foreach ($this->method as $m) {
            if ($request->method() == $m) {
                return true;
            }
        }
        return false;
    }
    public function isMatchURL($request)
    {
        $started = false;
        $query = Kernel::instance()->getURI();
        $route = str_replace('/', '\/', $this->regex);
        $ce = $this;
        $expresion_regular = preg_replace_callback("/\:(?<id>[\w_]+)\;?/", function ($n) use ($ce) {
            $regexp = !empty($ce->prepared[$n['id']]) ? $ce->prepared[$n['id']] : '[^\/]+';
            $regexp = "(?P<" . $n['id'] . ">" . $regexp . ")";
            return $regexp;
        }, $route, -1, $cantidad);
        if ($cantidad != 0) {
            $expresion_regular = '/^' . $expresion_regular . ($started ? '\//' : '$/');
            $e = preg_match($expresion_regular, ($started ? $query : trim($query, '/')), $r) ? array_merge(array('route' => $query), $r) : FALSE;
            if (is_array($e)) {
                $this->parameters = $e;
            }
        } else {
            $route = str_replace('\/', '/', $route);
            $e = $started ? strpos($query, $route) === 0 : $route == trim($query, '/');
            //app()->putURI($sub);
        }
        return $e;
    }
    private function isControllerFormat()
    {
        if (is_string($this->callback)) {
            return preg_match("/^[\w\_\\\]+\@[\w\_]+$/", $this->callback);
        }
        return false;
    }
    public function isControllerValid()
    {
        $this->controller = explode('@', $this->callback);
        return method_exists($this->controller[0], $this->controller[1]);
    }
    private function reflectionController()
    {
        $ReflectionMethod =  new \ReflectionMethod($this->controller[0], $this->controller[1]);
        $params = $ReflectionMethod->getParameters();
        $rp = [];
        foreach ($params as $param) {
            $p = new \stdClass;
            $p->position = $param->getPosition();
            $p->parameter = $param->name;
            $name = $param->getType() && !$param->getType()->isBuiltin() ? new \ReflectionClass($param->getType()->getName()) : null;
            $p->type = empty($name) ? null : $name->name;
            $rp[] = $p;
        }
        return $rp;
    }
    public function getArguments()
    {
        if ($this->isControllerValid()) {
            return $this->reflectionController();
        } else {
            return [];
        }
    }
    private function injectionMethodValid($reflections)
    {
        $parameters = $this->parameters;
        foreach ($reflections as $r) {
            $r->result = isset($parameters[$r->parameter]) ? $parameters[$r->parameter] : null;
        }
        $rp = [];
        foreach ($reflections as $n) {
            if (is_subclass_of($n->type, 'Core\Model')) {
                if ($res = ((new $n->type)->find($n->result)->first())) {
                    if ($res->getExists()) {
                        $rp[] = $res;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                $rp[] = $n->result;
            }
        }
        $this->request_params = $rp;
        return true;
    }
    private function injectionMethod($reflections)
    {
        $parameters = $this->parameters;
        foreach ($reflections as $r) {
            $r->result = isset($parameters[$r->parameter]) ? $parameters[$r->parameter] : null;
        }
        $params = array_map(function ($n) {
            if (is_subclass_of($n->type, 'Core\Model')) {
                return (new $n->type)->find($n->result)->first();
            }
            return $n->result;
        }, $reflections);
        return $this->request_params = $params;
    }
    private function injectionController($reflections, $request, $response)
    {
        $control = new $this->controller[0];
        if (!empty($this->request_params)) {
            $params = $this->request_params;
        } else {
            $params = $this->injectionMethod($reflections);
        }
        $this->injectionDefaultArgms($reflections, $params, $request, $response);
        return call_user_func_array([$control, $this->controller[1]], $params);
    }
    private function injectionDefaultArgms($reflections, &$params, $request, $response)
    {
        foreach ($reflections as $r) {
            if ($r->parameter == 'request') {
                $params[$r->position] = $request;
            } elseif ($r->parameter == 'response') {
                $params[$r->position] = $response;
            }
        }
    }
    private function hasMiddleware()
    {
        $this->relationalMiddleware();
        return !empty($this->middlewares);
    }
    private function relationalMiddleware()
    {
        $ce = $this;
        $this->middlewares = array_map(function ($key) use ($ce) {
            return Kernel::instance()->getMiddleware($key);
        }, $this->middlewares);
    }
    public function execute($request, $response)
    {
        if ($this->hasMiddleware()) {
            $next = true;
            $res = null;
            foreach ($this->middlewares as $m) {
                if ($next) {
                    $next = false;
                    $res = $m->handle($request, function ($request) use (&$next) {
                        $next = true;
                    });
                }
            }
            if (!$next && $res !== null) {
                return $res;
            }
        }
        if ($this->isControllerFormat()) {
            if ($this->isControllerValid()) {
                $reflection = $this->reflectionController();
                $rp = $this->injectionController($reflection, $request, $response);
                return $rp;
            } else {
                exit('Invalid Method = ' . $this->callback);
            }
        } else {
            exit('Formato Controller invalido');
        }
    }
    public function setMethod($method)
    {
        if (is_string($method)) {
            $method = strtoupper($method);
            if ($method == 'ANY') {
                $method = ['GET', 'POST', 'PUT', 'DELETE'];
            } else {
                $method = explode('|', $method);
            }
        }
        $this->method = $method;
        return $this;
    }
    public function getRegex()
    {
        return $this->regex;
    }
    public function setRegex($regex)
    {
        $this->regex = $regex;
        return $this;
    }
    public function setPrepared($prepared)
    {
        $this->prepared = $prepared;
        return $this;
    }
    public function setController($controller)
    {
        $this->callback = $controller;
        return $this;
    }

    public function middleware($cb)
    {
        $this->middlewares[] = $cb;
    }
    public static function find($name) {
        return kernel()->findRoute($name);
    }
    public static function exists($name) {
        return kernel()->findRoute($name) !== null;
    }
    private static function __request($method, $a1, $a2, $a3 = null)
    {
        $regex    = $a1;
        $prepared = null;
        $controller = null;
        if ($a3 === null) {
            $controller = $a2;
        } else {
            $prepared = $a2;
            $controller = $a3;
        }
        if($method == 'resource') {
            return (new RouteResource($regex, $controller));
        }
        return (new Route)
            ->setMethod($method)
            ->setRegex($regex)
            ->setPrepared($prepared)
            ->setController($controller);
    }
    
    public static function resource($a1, $a2, $a3 = null)
    {
        return static::__request('resource', $a1, $a2, $a3);
    }
    public static function any($a1, $a2, $a3 = null)
    {
        return static::__request('any', $a1, $a2, $a3);
    }
    public static function post($a1, $a2, $a3 = null)
    {
        return static::__request('post', $a1, $a2, $a3);
    }
    public static function get($a1, $a2, $a3 = null)
    {
        return static::__request('get', $a1, $a2, $a3);
    }
    public static function put($a1, $a2, $a3 = null)
    {
        return static::__request('put', $a1, $a2, $a3);
    }
    public static function delete($a1, $a2, $a3 = null)
    {
        return static::__request('delete', $a1, $a2, $a3);
    }
    public static function path($a1, $a2, $a3 = null)
    {
        return static::__request('path', $a1, $a2, $a3);
    }
}
