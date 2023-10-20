<?php
namespace Core;

use Core\HeaderBag;
use Core\FilesBag;

class Request {

    private static $instance;
		private $attrs = [];
		public $headers = null;
		private $_inputs = [];
		private $_method = null;
    private $_ajax = false;
    public $files = [];

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
				$this->capture();
    }
    public function file($name) {
      return $this->files->{$name};
    }
    public function capture() {
      $this->files = new FilesBag($_FILES);
			$this->_ajax   = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
			if(!empty($_SERVER['REQUEST_METHOD'])) {
				$this->_method = strtoupper($_SERVER['REQUEST_METHOD']);
			  }
			if(!empty($_REQUEST['_method'])) {
				$method_static = strtoupper($_REQUEST['_method']);
				if(in_array($method_static, ['PUT'])) {
					if($this->_method != $method_static) {
						$this->_method = $method_static;
					}
				}
			}
			$this->headers = new HeaderBag(getallheaders());
			$with = session()->read('withInputs');
			session()->delete('withInputs');
      $this->_inputs = !empty($_REQUEST) ? $_REQUEST : [];

			if(in_array($this->_method, ['PUT']) && empty($_POST)) {
				parse_str(file_get_contents('php://input'), $_PUT);
				$this->_inputs = $_PUT;
      }

      if(!empty($_GET)) {
        foreach($_GET as $idx => $val) {
          $this->_inputs[$idx] = $val;
        }
      }
      if(!empty($_POST)) {
        foreach($_POST as $idx => $val) {
          $this->_inputs[$idx] = $val;
        }
      }

			if(!empty($with)) {
				$with = json_decode($with, true);
				if(!empty($with)) {
					$this->_inputs = array_merge($this->_inputs, $with);
				}
			}
    }
		public function link() {
			return $_SERVER['REQUEST_URI'];
		}
		public function ajax() {
			return $this->_ajax;
		}
    public function headers2() {
        return getallheaders();
    }
    public function header($key) {
        $hs = $this->headers2();
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
		public function inputs() {
			return $this->_inputs;
    }
    public function all() {
      return $this->_inputs;
    }
    public function input($name = null, $coalesce = null) {
			if(is_null($name)) {
				return $this->inputs();
      }
      return $this->_inputs[$name] ?? $coalesce;
    }
    public function get($name) {
      return $this->input($name);
    }
    public function post($name) {
      return $this->input($name);
    }
    public function method() {
			return $this->_method;
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
