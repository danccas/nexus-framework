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

    protected $fileLog = '/tmp/terminal.log';

    function __construct($protocol, $host, $port = 22) {
      $this->_log(":: DECLARE: " . implode(",", [$protocol, $host, $port]));
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
    public function _log($text) {
      if(!empty($this->name)) {
        file_put_contents($this->fileLog, "[" . $this->name . "] " . $text . "\n", FILE_APPEND | LOCK_EX);
      } else {
        file_put_contents($this->fileLog, $text . "\n", FILE_APPEND | LOCK_EX);
      }
    }
    public function hostname() {
      return $this->name;
    }
    public function ipaddress() {
      return $this->host;
    }
    public function googleStatus($localTerminal) {
      if($this->connect == 'localhost') {
        return 'RUNNING';
      }
      $statusServer = $localTerminal->exec('gcloud compute instances describe ' . $this->name . ' --zone=us-central1-a --format="json(status)"');
      if(empty($statusServer)) {
        return 'RUNNING';
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
        $this->_log(":: CONNECT: " . implode(",", [$this->name]));
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
    public function isLocalhost() {
      return $this->connect === 'localhost';
    }
    public function scp_send($local_file, $remote_file, $permission = 0644) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        if($local_file != $remote_file) {
          $this->_log("-> CP SEND: " . implode(" to ", [$local_file, $remote_file,
$permission]));
@copy($local_file, $remote_file);
        }
        @chmod($remote_file, $permission);
      } else {
        $this->_log("-> SCP SEND: " . implode(" to ", [$local_file, $remote_file, $permission]));
        \ssh2_scp_send($this->connect, $local_file, $remote_file, $permission);
      }
      return $this;
    }
    public function scp_recv($remote_file, $local_file, $permission = 0644) {
      $this->preConnect();
      if($this->connect === 'localhost') {
        if($local_file != $remote_file) {
          $this->_log("-> CP RECV: " . implode(" to ", [$remote_file, $local_file, $permission]));
          @copy($remote_file, $local_file);
        }
        @chmod($local_file, $permission);
      } else {
        $this->_log("-> SCP RECV: " . implode(" to ", [$remote_file, $local_file, $permission]));
        \ssh2_scp_recv($this->connect, $remote_file, $local_file);
      }
      return $this;
    }
    public function scandir($dir) {
      $this->preConnect();
      $ls = $this->exec('ls ' . $dir);
      return !empty($ls) ? explode("\n", $ls) : [];
    }
    function exec($cmd) {
      $this->preConnect();
      $id = uniqid();
      $this->_log($id . " -> EXEC: " . $cmd);
      if($this->connect === 'localhost') {
        $rp = shell_exec($cmd);
        $this->_log($id . " <- " . $rp);
        return $rp;
      } else {
        $stream = \ssh2_exec($this->connect, $cmd);
        if($stream === false) {
          return false;
        }
        \stream_set_blocking($stream, true);
        #$stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $rr = trim(\stream_get_contents($stream));
        $this->_log($id . " <- " . $rr);
        fclose($stream);
        return $rr;
      }
    }
    public function shell_exec($cmd) {
      $this->preConnect();
      $id = uniqid();
      $this->_log($id . " -> SHELL: " . $cmd);
      if($this->connect === 'localhost') {
        $rp = shell_exec($cmd);
        $this->_log($id . " <- " . $rp);
        return $rp;
      } else {
        $stream = \ssh2_exec($this->connect, $cmd);
        \stream_set_blocking($stream, true);
        $stream_out = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $rp = trim(\stream_get_contents($stream_out));
        $this->_log($id . " <- " . $rp);
        return $rp;
      }
    }
    public function file_exists($uri) {
      $this->preConnect();
      $this->_log("-> File Exists: " . $uri);
      if($this->connect === 'localhost') {
        $rp = file_exists($uri);
        $this->_log("<- File Exists: " . json_encode($rp));
        return $rp;
      } else {
        $cmd = "test -e \"" . $uri . "\" && echo '1' || echo '0'";
        $out = $this->shell_exec($cmd);
        $rp = $out === '1';
        $this->_log("<- File Exists: " . json_encode($rp));
        return $rp;
      }
    }
    public function close() {
      if($this->connect !== 'localhost' && $this->connect !== null) {
        $this->_log("-> Close");
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
      $rr = strpos($out, strval($pid)) !== false;
      $this->_log("-> " . ($rr ? 'True' : 'False'));
      return $rr;
    }
    public function kill($pid) {
      return false;
    }
}
