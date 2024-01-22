<?php
namespace Core;

class Terminal {

  protected $protocol;
  protected $name;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
		protected $connect;
		protected $auths = [];
    private $result;

    function __construct($protocol, $host, $port = 22) {
      $name = null;
      if(strpos($host, ':') !== false) {
        list($host, $name) = explode(':', $host);
      } else {
        $name = $host;
      }
      $this->name = $name;
        $this->protocol = $protocol;
        $this->host     = $host;
        $this->port     = $port;
        if($host === 'localhost') {
          $this->connect = 'localhost';
        }
    }
    public function hostname() {
      return $this->name;
    }
    public function ipaddress() {
      return $this->host;
    }
    public function googleStatus($localTerminal) {
      $statusServer = $localTerminal->exec('gcloud compute instances describe ' . $this->name . ' --zone=us-central1-a --format="json(status)"');
      if(empty($statusServer)) {
        return false;
      }
      $statusServer = json_decode($statusServer, true);
      if(empty($statusServer)) {
        return false;
      }
      if(empty($statusServer['status'])) {
        return false;
      }
      return $statusServer['status'];
    }
    public function preConnect() {
      if($this->connect === null || $this->connect !== 'localhost') {
        $this->connect = \ssh2_connect($this->ipaddress(), $this->port);
        if(!empty($this->password)) {
            \ssh2_auth_password($this->connect, $this->username, $this->password);
        } else {
          \ssh2_auth_pubkey_file($this->connect, $this->username, '/home/desarrollo/.ssh/id_rsa.pub', '/home/desarrollo/.ssh/id_rsa');
        }
      }
    }
    function connect($username, $password = null) {
        $this->username = $username;
        $this->password = $password;
        if(!function_exists('ssh2_connect')) {
            exit('Debe instalar php-ssh2');
        }
    }
    public function sftp() {
      $this->preConnect();
      if(!($res = \ssh2_sftp($this->connect))) {
        throw new \Exception('Unable to create SFTP connection.');
      }
      return 'ssh2.sftp://' . intval($res);
    }
    public function scp_send($local_file, $remote_file, $permission = 0644) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        @copy($local_file, $remote_file);
        @chmod($remote_file, $permission);
      } else {
        \ssh2_scp_send($this->connect, $local_file, $remote_file, $permission);
      }
      return $this;
    }
    public function scp_recv($remote_file, $local_file, $permission = 0644) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        @copy($remote_file, $local_file);
        @chmod($local_file, $permission);
      } else {
        \ssh2_scp_recv($this->connect, $remote_file, $local_file);
      }
      return $this;
    }
    public function scandir($dir) {
      $this->preConnect();
      $ls = $this->exec('ls ' . $dir);
      return explode("\n", $ls);
    }
    function exec($cmd) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        return shell_exec($cmd);
        exec($cmd, $out);
        return $out;
      } else {
      $stream = \ssh2_exec($this->connect, $cmd);
      if($stream === false) {
        return false;
      }
        \stream_set_blocking($stream, true);
        #$stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $rr = trim(\stream_get_contents($stream));
        fclose($stream);
        return $rr;
        return $this;
      }
    }
    public function shell_exec($cmd) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        return shell_exec($cmd);
      } else {
      $stream = \ssh2_exec($this->connect, $cmd);
      \stream_set_blocking($stream, true);
      $stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
      return trim(\stream_get_contents($stream_out));
      }
    }
    public function file_exists($uri) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        return file_exists($uri);
      } else {
      $cmd = "test -e \"" . $uri . "\" && echo '1' || echo '0'";
      $out = $this->shell_exec($cmd);
      return $out === '1';
      }
    }
    public function close() {
      if($this->connect !== 'localhost' && $this->connect !== null) {
        \ssh2_exec($this->connect, 'exit');
        \ssh2_disconnect($this->connect);
      }
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
      $cmd = "ps -aux | grep '" . $query . "'  | grep -v 'grep'  | awk '{print $2}'";
      $out = $this->shell_exec($cmd);
      return $out;
    }
    public function countProcessByName($query) {
      $cmd = "ps -aux | grep '" . $query . "'  | grep -v 'grep' | wc -l";
      $out = $this->shell_exec($cmd);
      return $out;
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
