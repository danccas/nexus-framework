<?php
namespace Core;

class Terminal {
    protected $protocol;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
		protected $connect;
		protected $auths = [];
        private $result;

    function __construct($protocol, $host, $port = 22) {
        $this->protocol = $protocol;
        $this->host     = $host;
        $this->port     = $port;
    }
    function connect($username, $password = null) {
        $this->username = $username;
        $this->password = $password;
        if(!function_exists('ssh2_connect')) {
            exit('Debe instalar php-ssh2');
        }
        $this->connect = \ssh2_connect($this->host, $this->port);
        if(!empty($this->password)) {
            \ssh2_auth_password($this->connect, $this->username, $this->password);
				} else {
					\ssh2_auth_pubkey_file($this->connect, $this->username, '~/.ssh/id_rsa.pub', '~/.ssh/id_rsa');
#						\ssh2_auth_agent($this->connect, $this->username);
#					$this->auths = \ssh2_auth_none($this->connect, $this->username);
        }
    }
    function exec($cmd) {
        $stream = \ssh2_exec($this->connect, $cmd);
        \stream_set_blocking($stream, true);
        $stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $this->result = trim(\stream_get_contents($stream_out));
        return $this;
    }
    function stream($callback) {
        return $this;
    }
    public function data() {
        return $this->result;
    }
    public function parse($tipo) {
			if($tipo == 'console') {
            $lines = explode("\n", $this->result);
            $headers = null;
            $data = [];
            foreach ($lines as $line) {
                $values = preg_split('/\s+/', $line);
                if (!$headers) {
                    $headers = $values;
                } elseif (count($values) == count($headers)) {
                    $data[] = (object)array_combine($headers, $values);
                }
            }
            $this->result = $data;
        }
        return $this;
    }
}
