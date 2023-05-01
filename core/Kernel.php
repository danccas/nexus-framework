<?php

namespace Core;

use Core\Route;
use App\Http\Kernel as KernelHttp;

class Kernel
{

    private $routes = [];
    private $path_uri = '';
    private $request = null;
    private static $instance;
    private $routeMiddleware = [];

    public static function instance()
    {
        if (null === static::$instance) {
            exit('Sin instancia Kernel');
        }
        return static::$instance;
    }
    public function __construct()
    {
        if (null === static::$instance) {
            static::$instance = $this;
        }
        $this->registerMiddlewares();
        return $this;
    }
    private function registerMiddlewares()
    {
        $this->routeMiddleware = (new KernelHttp)->routeMiddleware;
    }
    public function getMiddleware($key)
    {
        return isset($this->routeMiddleware[$key]) ? new $this->routeMiddleware[$key] : null;
    }
    public function setRequest($request)
    {
        $this->request = $request;
    }
    private static function clearURI($query)
    {
        if (empty($query)) {
            return '';
        }
        $query = parse_url($query); // Solo queremos la URL sin parámetros GET
        $query = !empty($query['path']) ? $query['path'] : '';
        $query = preg_replace("/\/+/", '/', $query); // Quitamos todos los slashes repetidos (e.g. "politica/:codigo")
        $query = trim($query, '/') . '/';
        return $query;
    }
    public function getURI()
    {
        return $this->path_uri;
    }
    public function setURI($uri)
    {
        $this->path_uri = static::clearURI($uri);
        return $this;
    }
    public function putURI($part)
    {
        $query =  substr($this->path_uri, strlen($part));
        return static::clearURI($query);
    }
    public function registerRoute(Route $route)
    {
        $this->routes[] = $route;
    }
    public function getRoutes()
    {
        return $this->routes;
    }
		public function getRoutesNames()
    {
        $rp = [];
        foreach($this->routes as $r) {
            if(!empty($r)) {
                $name = $r->getName();
                if(!empty($name)) {
                    $rp[$name] = $name;
                }
            }
        }
        return $rp;
    }
    public function findRoute($name)
    {
        foreach ($this->routes as $k => $e) {
            if ($e->compare($name)) {
                return $e;
            }
        }
        return null;
    }
    public function debug()
    {
        $rp = [];
        $request = new Request;
        echo "<table border=\"1\">";
        echo "<thead><tr><th>Context</th><th>Method</th><th>Route</th><th>Controller</th><th>Name</th><th>Arguments</th><th>V.Method</th><th>V.Route</th><th>V.Controller</th><th>V.Arguments</th></tr></thead>";
        echo "<tbody>";
        foreach ($this->routes as $e) {
            echo "<tr>";
            echo "<td>" . $e->getContext() . "</td>";
            $rp[] = array($e, $e->isMatch($request) ? 'CUMPLE' : 'No');
            echo "<td>" . implode(',', $e->method) . "</td>";
            echo "<td>" . $e->regex . "</td>";
            echo "<td>" . $e->callback . "</td>";
            echo "<td>" . $e->getName() . "</td>";
            echo "<td>" . implode(', ', array_map(function ($n) {
                return trim($n->type . ' $' . $n->parameter);
            }, $e->getArguments())) . "</td>";
            echo "<td>" . ($e->isMatchMethod($request) ? '&#10004;' : '&#10060;') . "</td>";
            echo "<td>" . ($e->isMatchURL($request) ? '&#10004;' : '&#10060;') . "</td>";
            echo "<td>" . ($e->isControllerValid() ? '&#10004;' : '&#10060;') . "</td>";
            echo "<td>" . ($e->isMatchMethod($request) && $e->isMatchURL($request) && $e->isMatchArguments($request) ? '&#10004;' : '&#10060;') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        exit;
    }
    public function terminate()
    {
        if (isset($_GET['internal'])) {
            return $this->debug();
            exit;
        }
        $request = new Request;
        $response = new Response();
        foreach ($this->routes as $e) {
            if ($e->isMatch($request)) {
                $request->route = $e;
                $response->load($e->execute($request, $response));
                echo $response->__toString();
                exit;
            }
        }

        echo "<h2>== 404</h2>";
      exit();  $this->debug();
        exit('== 404');
    }
}
