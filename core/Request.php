<?php
namespace Core;

class Request {

    private static $instance;
    private $attrs = [];

    public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    function __construct()
    {
        if (null === static::$instance) {
            static::$instance = $this;
        }
    }
    public function headers() {
        return getallheaders();
    }
    public function header($key) {
        $hs = $this->headers();
        return isset($hs[$key]) ? $hs[$key] : null;
    }
    public function raw() {
        $raw = file_get_contents('php://input');
        return $raw;
    }
    public function json() {
        $raw = $this->raw();
        return json_decode($raw);
    }
    public function input($name = null, $coalesce = null) {
        if(is_null($name)) {
            return $_REQUEST;
        }
        return $_REQUEST[$name] ?? $coalesce;
    }
    public function method() {
        return $_SERVER['REQUEST_METHOD'];
    }
    public function by($zone) {
        return false;
    }
    public function __get($key)
    {
        return $this->attrs[$key] ?? null;
    }

    public function __set($key, $value)
    {
        return $this->attrs[$key] = $value;
    }
}