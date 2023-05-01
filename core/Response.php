<?php
namespace Core;

use Core\Concerns\Collection;

class Response {
    private $html;
    private $json;
    private $format = 'html';
    private $codec;
    private $data;

    function __construct()
    {

    }
    public function load($data) {
        if($data instanceof Response) {
            return $this->migrate($data);
        }
        $this->data = $data;
        return $this;
    }
    public function json($data) {
        $this->format = 'json';
        $this->data = $data;
        return $this;
    }
    public function xml() {

    }
    public function header($key, $value) {

    }
    public function download($file) {

    }
    public function redirect($url, $params = []) {
        $route = kernel()->findRoute($url);
        if(!empty($route)) {
            header('location: ' . $route->link($params));
            exit;
        }
        header('location: ' . $url);
        exit;
    }
    public function migrate($res) {
        $this->format = $res->format;
        $this->data = $res->data;
        return $this;
    }
    public function getData() {
        
        if($this->data instanceof Response) {
            return $this->migrate($this->data);

        } elseif($this->data instanceof Collection) {
            return $this->data->toArray();

        } else {
            return $this->data;
        }
    }
    public function __toString() {
        if($this->format == 'json') {
            return json_encode($this->getData());
        }
        return $this->getData();
    }
}
