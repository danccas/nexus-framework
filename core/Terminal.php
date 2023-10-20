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
    public function hostname() {
      return $this->host;
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
#					\ssh2_auth_agent($this->connect, $this->username);
#					$this->auths = \ssh2_auth_none($this->connect, $this->username);
        }
    }
    public function scp_send($local_file, $remote_file, $permission = 0644) {
      \ssh2_scp_send($this->connect, $local_file, $remote_file, $permission);
      return $this;
    }
    public function scp_recv($remote_file, $local_file, $permission = 0644) {
      \ssh2_scp_recv($this->connect, $remote_file, $local_file);
      return $this;
    }
    function exec($cmd) {
        $stream = \ssh2_exec($this->connect, $cmd);
        \stream_set_blocking($stream, true);
        #$stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $rr = trim(\stream_get_contents($stream));
        fclose($stream);
        return $rr;
        return $this;
    }
    public function shell_exec($cmd) {
      $stream = \ssh2_exec($this->connect, $cmd);
      \stream_set_blocking($stream, true);
      $stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
      return trim(\stream_get_contents($stream_out));
    }
    public function file_exists($uri) {
      $cmd = "test -e \"" . $uri . "\" && echo '1' || echo '0'";
      $out = $this->shell_exec($cmd);
      return $out === '1';
    }
    public function close() {
      \ssh2_exec($this->connect, 'exit');
      \ssh2_disconnect($this->connect);
      $this->connect = null;
      return $this;
    }
    public function disconnect() {
      return $this->close();
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
    public function findProcessByName($query) {
      return false;
    }
    public function findProcessById($pid) {
      $cmd = "ps -p " . $pid;
      $out = $this->shell_exec($cmd);
      return strpos($out, strval($pid)) !== false;
    }
    public function kill($pid) {
      return false;
    }
}
