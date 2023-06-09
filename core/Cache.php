<?php

namespace Core;

use stdClass;

class Cache
{
    protected $id;
    protected $file;
    protected $data;
    protected $min_expire;
    protected $exec;

    function __construct($id)
    {
        $this->id = $id;
        $this->createPath();
    }
    private function createPath()
    {
        #$hash = md5($this->id);
        $hash = 'cache_' . $this->id;
        //$this->file = __DIR__ . '/../cache/data/' . $hash . '.json';
        $this->file = app()->getPath() . 'cache/data/' . $hash . '.json';

    }
    public function set($data)
    {
        $this->update($data);
        return $this;
    }
    public function update($data)
    {
        if (is_callable($data)) {
            if (!$this->hasExpired()) {
                return false;
            }
            $data = $data($this);
        }
        $this->data = $data;
        $data = [
            'time' => time(),
            'data' => $data,
        ];
        $data = json_encode($data);
        file_put_contents($this->file, $data);
        return true;
    }
    public function expire($minutes = 0)
    {
        $this->min_expire = $minutes;
        return $this;
    }
    public function hasExpired()
    {
        if (!file_exists($this->file)) {
            return true;
        }
        $modif = filemtime($this->file);
        return time() - $modif >= $this->min_expire * 60;
    }
    public function file($path)
    {
        return $this->file;
    }
    public function dump()
    {
        if (!file_exists($this->file)) {
            return null;
        }
        $data = file_get_contents($this->file);
        return json_decode($data);
    }
    public function data()
    {
        $data = $this->dump();
        if(empty($data)) {
            return null;
        }
        return $data->data;
    }
    public function get()
    {
        return $this->data();
    }
    public function item($key, $value = null) {
        $data = $this->data();
        if($value === null) {
            return !empty($data) && property_exists($data, $key) ? $data->$key : null;
        }
        if(empty($data)) {
            $data = new stdClass;
        }
        $data->$key = $value;
        $this->set($data);
        return $this;
    }
    public function print()
    {
        echo "Name: " . $this->id . "\n";
        print_r($this->dump());
        echo "\n";
    }
    public function has($callback = null)
    {
        if ($callback === null) {
            return file_exists($this->file);
        }
        if (!$this->hasExpired()) {
            $callback($this->data());
        }
        return $this;
    }
    public function else($callback)
    {
        if ($this->hasExpired()) {
            $data = $callback($this);
            $this->update($data);
        }
        return $this;
    }
}
