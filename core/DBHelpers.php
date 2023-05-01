<?php

namespace Core;

class DBHelpers
{
    protected static $instances = null;
    protected static $listdsn   = array();
    protected $engine = null;

    protected $dsn;
    protected $protocol;
    protected $authentication;
    protected $hosts;
    protected $database;
    protected $parameters = [];

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
    public static function showConnections()
    {
        return array(
            'dsn' => static::$listdsn,
            'cnx' => static::$instances,
        );
    }
    public static function createDSN($cdr, $dsn, $user = null, $pass = null)
    {
        $cdr = strtolower($cdr);
        if (!is_null($user)) {
            $dsn .= '||' . $user;
        }
        if (!is_null($pass)) {
            $dsn .= '||' . $pass;
        }
        if (!empty(static::$listdsn)) {
            foreach (static::$listdsn as $k => $d) {
                if ($dsn == $d) {
                    $dsn = '&' . $k;
                    break;
                }
            }
        }
        static::$listdsn[$cdr] = $dsn;
        static::g($cdr); #TODO
    }
    public static function existsConnection($cdr)
    {
        return isset(static::$listdsn[$cdr]);
    }
    public static function g($cdr = null)
    {
        return static::instance($cdr);
    }
    public static function instance($cdr = null)
    {
        if (static::$instances === null) {
            static::$instances = new \stdClass();
        }
        if ($cdr instanceof DB) {
            return $cdr;
        }
        if (!empty($cdr)) {
            $cdr = strtolower($cdr);
        }
        if (!is_null($cdr) && !property_exists(static::$instances, $cdr)) {
            if (array_key_exists($cdr, static::$listdsn)) {
                if (strpos(static::$listdsn[$cdr], '&') === 0) {
                    $cdr = trim(static::$listdsn[$cdr], '&');
                    if (property_exists(static::$instances, $cdr)) {
                        return static::$instances->$cdr;
                    } else {
                        return static::$instances->$cdr = new static(static::$listdsn[$cdr]);
                    }
                } else {
                    return static::$instances->$cdr = new static(static::$listdsn[$cdr]);
                }
            } else {
                trigger_error('DSN no existe: ' . $cdr);
            }
        }
        if (is_null($cdr)) {
            if (empty(static::$instances)) {
                trigger_error('No se ha iniciado una Instancia');
                exit;
            } else {
                return @current(static::$instances);
            }
        }
        return static::$instances->$cdr;
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
