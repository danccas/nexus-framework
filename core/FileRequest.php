<?php
namespace Core;

class FileRequest
{
  protected $files;
  protected $index;

  public function __construct(array $files)
  {
    $this->index = 0;
    $this->files = $files;
  }
  public function move($dir, $name = null, $terminal = null) {
    foreach($this->files as $ff) {
      if(is_null($name)) {
        $dest = $dir . '/' . $ff['name'];
      } else {
        $dest = $dir . '/' . $name;
      }
      $dest = str_replace('//', '/', $dest);
      if(is_null($terminal)) {
        move_uploaded_file($ff['tmp_name'], $dest);
      } else {
        $terminal->scp_send($ff['tmp_name'], $dest);
        $terminal->exec('exit');
      }
    }
  }
  public function all() {
    $i = -1;
    $ce = $this;
    return array_map(function($n) use(&$i, &$ce) {
      $i++;
      return new static([$ce->files[$i]]);
    }, $this->files);
  }
  public function i($index) {
    $this->index = $index;
    return $this;
  }
  public function extension() {
    return $this->getClientOriginalExtension();
  }
  public function getClientMimeType() {
    return null;
  }
  public function getClientOriginalExtension() {
        $name = $this->files[$this->index]['name'];
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        return $extension;
    }
    public function getRealPath() {
        return $this->files[$this->index]['tmp_name'];
    }
    public function getClientOriginalName() {
        return $this->files[$this->index]['name'];
    }
    public function getSize() {
        return $this->files[$this->index]['size'];
    }


  public function __set($key, $val) {
    $this->files[$this->index][$key] = $val;
    return $this;
  }
  public function __get($key) {
    return $this->files[$this->index][$key];
  }
}
