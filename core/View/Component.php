<?php
namespace Core\View;

class Component {
  protected $dom     = null;
  protected $attrs   = [];
  protected $content = null;

  public function render() {

  }
  public function __toString()
  {
      return $this->render();
  }
  public function setContent($cc) {
    $this->content = $cc;
    return $this;
  }
  public function setInt($name, $value) {
    $this->{$name} = $value;
    return $this;
  }
  public function setAttr($name, $value) {
    $this->attrs[$name] = $value;
    return $this;
  }
  public function attr($name) {
    return $this->attrs[$name];
  }
  public function setDom($name) {
    $this->dom = $name;
    return $this;
  }
}
