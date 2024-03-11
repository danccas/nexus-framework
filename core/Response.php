<?php

namespace Core;

use Core\Nexus\Tablefy;
use Core\Concerns\Collection;
use Core\Blade;
use Core\JSON;

class Response
{
	private $responseCode   = 200;
	private $responseStatus = true;
	private $urlRedirect    = null;

	private $html;
	private $json;
	private $format = 'html';
	private $codec;
	private $data;
	private $theme = null;
	private $headers = [];
	public static $code_http = array(
		100 => 'HTTP/1.1 100 Continue',
		101 => 'HTTP/1.1 101 Switching Protocols',
		200 => 'HTTP/1.1 200 OK',
		201 => 'HTTP/1.1 201 Created',
		202 => 'HTTP/1.1 202 Accepted',
		203 => 'HTTP/1.1 203 Non-Authoritative Information',
		204 => 'HTTP/1.1 204 No Content',
		205 => 'HTTP/1.1 205 Reset Content',
		206 => 'HTTP/1.1 206 Partial Content',
		300 => 'HTTP/1.1 300 Multiple Choices',
		301 => 'HTTP/1.1 301 Moved Permanently',
		302 => 'HTTP/1.1 302 Found',
		303 => 'HTTP/1.1 303 See Other',
		304 => 'HTTP/1.1 304 Not Modified',
		305 => 'HTTP/1.1 305 Use Proxy',
		307 => 'HTTP/1.1 307 Temporary Redirect',
		400 => 'HTTP/1.1 400 Bad Request',
		401 => 'HTTP/1.1 401 Unauthorized',
		402 => 'HTTP/1.1 402 Payment Required',
		403 => 'HTTP/1.1 403 Forbidden',
		404 => 'HTTP/1.1 404 Not Found',
		405 => 'HTTP/1.1 405 Method Not Allowed',
		406 => 'HTTP/1.1 406 Not Acceptable',
		407 => 'HTTP/1.1 407 Proxy Authentication Required',
		408 => 'HTTP/1.1 408 Request Time-out',
		409 => 'HTTP/1.1 409 Conflict',
		410 => 'HTTP/1.1 410 Gone',
		411 => 'HTTP/1.1 411 Length Required',
		412 => 'HTTP/1.1 412 Precondition Failed',
		413 => 'HTTP/1.1 413 Request Entity Too Large',
		414 => 'HTTP/1.1 414 Request-URI Too Large',
		415 => 'HTTP/1.1 415 Unsupported Media Type',
		416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
		417 => 'HTTP/1.1 417 Expectation Failed',
		500 => 'HTTP/1.1 500 Internal Server Error',
		501 => 'HTTP/1.1 501 Not Implemented',
		502 => 'HTTP/1.1 502 Bad Gateway',
		503 => 'HTTP/1.1 503 Service Unavailable',
		504 => 'HTTP/1.1 504 Gateway Time-out',
		505 => 'HTTP/1.1 505 HTTP Version Not Supported',
	);
	private $withsOnlys = [];
	private $withsInput = [];
  private $pathFile = null;
	private static $instance;

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
		$this->booted();
	}
	public function booted()
	{
		$this->theme = Blade::instance();
	}
	public function blade()
	{
		return $this->theme;
	}
	public function load($data)
	{
    if ($data instanceof Response) {
			return $this;#->migrate($data);
		}
		$this->data = $data;
		return $this;
	}
	public function json($data)
	{
    $this->format = 'json';
    $this->theme = null;
		$this->header('Content-Type', 'application/json; charset=utf-8');
		$this->data = $data;
		return $this;
	}
	public function xml()
	{
		$this->format = 'xml';
	}
	public function getPreviousUrl($fallback = null)
	{
		$url = Request::instance()->headers->get('referer') ?: $fallback;
		$url = parse_url($url, PHP_URL_PATH);
		return $url;
	}
	public function status($code = null)
	{
		if ($code === null) {
			return $this->responseCode;
		}
		if (isset(static::$code_http[$code])) {
			$this->responseCode = $code;
		}
		return $this;
	}
	public function header($key, $value)
	{
		$this->headers[$key] = $value;
		return $this;
	}
	public function view($name, $params = [])
	{
		$this->format = 'html';
		$this->theme->load($name)->append($params);
		return $this;
	}
	public function pdf($download, $orientacion = null)
	{
		$this->format = 'pdf';
		$pdf = new \Core\PDFily();
		$pdf->_blade = $this->theme;
		if (!empty($orientacion)) {
			$pdf->_blade->orientacion = $orientacion;
		}
		return $pdf->save($download);
	}
	public function download($file, $alias = null, $headers = [])
  {
    if(empty($headers)) {
      if(!empty($alias)) {
        $this->header('Content-Disposition', 'attachment; filename="' . $alias . '"');
      } else {
        $this->header('Content-Disposition', 'attachment');
      }
    }
    if(!empty($headers)) {
      foreach($headers as $key => $val) {
        $this->header($key, $val);
      }
    }
    $this->format = 'download';
    $this->pathFile = $file;
		return $this;
	}
	public function back()
	{
		$this->format = 'redirect';
		$this->responseStatus = false;
		$this->urlRedirect = $this->getPreviousUrl();
		return $this;
	}
	public function redirect($url = null, $params = [])
	{
		$this->format = 'redirect';
		if (!is_null($url)) {
			$route = kernel()->findRoute($url);
			if (!empty($route)) {
				$this->urlRedirect = $route->link($params);
			} else {
				$this->urlRedirect = $url;
			}
		}
		return $this;
	}
	public function abort($status, $message = null)
	{
		$this->responseStatus = false;
		$this->status($status)->execute();
		exit;
	}
	public function migrate($res)
	{
		$this->format = $res->format;
		$this->data = $res->data;
		$this->theme = $res->theme;
		return $this;
	}
	public function getData()
	{
		if ($this->data instanceof Response) {
			return $this->migrate($this->data);

    } elseif ($this->data instanceof Collection) {
      return $this->data->toArray();

    } elseif ($this->data instanceof Tablefy) {
      return $this->data->getJSON();

    } else {
      return $this->data;
		}
	}
	public function with($key, $value)
	{
		$this->withsOnlys[$key] = $value;
		return $this;
	}
	public function withInputs()
	{
		$this->withsInput = request()->input();
		return $this;
	}
	public function execute()
	{
		if (route()->current() !== null) {
			route()->current()->terminate();
    }
		if (request()->ajax()) {
			if ($this->format == 'redirect') {
				$this->with('success', $this->responseStatus);
				$data = array_merge([
					'data'     => $this->getData(),
					'inputs'   => $this->withsInput,
				], $this->withsOnlys);
				if (!empty($this->urlRedirect) && $this->responseStatus) {
					if ($this->urlRedirect == $this->getPreviousUrl()) {
						$data['close'] = true;
					} else {
						$data['redirect'] = $this->urlRedirect;
					}
				}
				$this->json($data);
			}
    }
    if (!empty($this->withsOnlys)) {
      foreach($this->withsOnlys as $key => $val) {
        session()->write($key, JSON::encode($val));
      }
			//session()->write('with', JSON::encode($this->withsOnlys));
		}
    if (!empty($this->withsInput)) {
      session()->write('withsInput', JSON::encode($this->withsInput));
    }
		if ($this->format == 'redirect' && !empty($this->urlRedirect)) {
			header('location: ' . $this->urlRedirect);
		}
		if (!empty(static::$code_http[$this->responseCode])) {
			header(static::$code_http[$this->responseCode]);
		}
		if (!empty($this->headers)) {
			foreach ($this->headers as $k => $v) {
				$cmd = $k . (!is_null($v) ? ': ' . $v : '');
				header($cmd);
			}
		}
		if (!empty($this->theme) && $this->theme->isLoad()) {
			return $this->theme;
    }
    if($this->format == 'download') {
      readfile($this->pathFile);
      exit;
    }
		if ($this->format == 'json') {
			return JSON::encode($this->getData());
		}
		return $this->getData();
	}
	public function __toString()
	{
		return $this->execute();
	}
}
