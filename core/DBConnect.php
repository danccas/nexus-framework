<?php

namespace Core;

class DBConnect
{
    public $engine;
    protected $dsn;
    protected $protocol;
    protected $authentication;
    protected $hosts;
    protected $database;
    protected $parameters = [];
    protected $executed = false;
    protected $connection;
    protected $core;

    function __construct($dsn)
    {
        $this->parseDsn($dsn);
        $this->preConnect();
    }
    public function execute($query, $prepare)
    {
        return $this->engine->execute($this->connection, $query, $prepare);
    }
    public function engine()
    {
        return $this->engine;
    }
    public function clearQuery()
    {
        $this->engine->clearQuery();
        return $this;
    }
    function preConnect()
    {
        $ce = $this;
        $this->executed = false;
        if ($this->protocol == 'pgsql') {
            $this->engine = new DBPSQL($this);
        } else {
            exit('TODO DB PROTOCOL');
        }
        $this->connection = function () use (&$ce) {
            $ce->executed = true;
            return $ce->engine->connect();
        };
    }
    public function connect()
    {
        if (!$this->executed) {
            $this->executed = true;
            $this->connection = ($this->connection)();
        }
    }
    public function setCore($core)
    {
        $this->core = $core;
        return $this;
    }
    public function getProtocol()
    {
        return $this->protocol;
    }
    public function getHosts()
    {
        return $this->hosts;
    }
    public function getAuthentication()
    {
        return $this->authentication;
    }
    public function getDatabase()
    {
        return $this->database;
    }
    public function except($message, $e = null)
    {
        $this->core->except($message, $e);
    }
    private function parseProtocol($dsn)
    {
        $regex = '/^(\w+)\:/i';
        preg_match($regex, $dsn, $matches);
        if (isset($matches[1])) {
            $protocol = $matches[1];
            $this->protocol = $protocol;
        }
    }
    protected function parseDsn($dsn)
    {
        $this->parseProtocol($dsn);
        if (null === $this->protocol) {
            return;
        }
        $dsn = str_replace($this->protocol . ':', '', $dsn);
        $dsn = trim($dsn, '//');

        $parts = explode('||', $dsn);
        if (count($parts) > 1) {
            $this->authentication = ['username' => $parts[1], 'password' => $parts[2]];
        }
        if ($this->protocol === 'oci') {
            $this->hosts = $parts[0];
            return;
        }
        if (false === $pos = strrpos($dsn, '@')) {
            $this->authentication = ['username' => null, 'password' => null];
        } else {
            $temp = explode(':', str_replace('\@', '@', substr($dsn, 0, $pos)));
            $dsn = substr($dsn, $pos + 1);
            $auth = [];
            if (2 === count($temp)) {
                $auth['username'] = $temp[0];
                $auth['password'] = $temp[1];
            } else {
                $auth['username'] = $temp[0];
                $auth['password'] = null;
            }
            $this->authentication = $auth;
        }
        if (false !== strpos($dsn, '?')) {
            if (false === strpos($dsn, '/')) {
                $dsn = str_replace('?', '/?', $dsn);
            }
        }
        $temp = explode('/', $dsn);
        $this->parseHosts($temp[0]);
        if (isset($temp[1])) {
            $params = $temp[1];
            $temp = explode('?', $params);
            $this->database = empty($temp[0]) ? null : $temp[0];
            if (isset($temp[1])) {
                $this->parseParameters($temp[1]);
            }
        }
    }
    private function parseHosts($hostString)
    {
        preg_match_all('/(?P<host>[\w\-\.]+)(\:(?P<port>\d+))?/mi', $hostString, $matches);
        $hosts = null;
        foreach ($matches['host'] as $index => $match) {
            $port = !empty($matches['port'][$index]) ? (int) $matches['port'][$index] : null;
            $hosts = ['host' => $match, 'port' => $port];
        }
        $this->hosts = $hosts;
    }
    private function parseParameters($params)
    {
        $parameters = explode('&', $params);
        foreach ($parameters as $parameter) {
            $kv = explode('=', $parameter, 2);
            $this->parameters[$kv[0]] = isset($kv[1]) ? $kv[1] : null;
        }
    }
    static function escape($string)
    {
        return str_replace("'", "\\'", $string);
    }
}
