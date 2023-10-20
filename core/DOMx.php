<?php
namespace Core;

use Core\Concerns\Collection;
use Core\Model;

class DOMx {
  private static $root = null;
  protected $elem;

    public static function document() {
      if(static::$root === null) {
        return static::$root = new \DOMDocument;
      }
      return static::$root;
    }
    public function __construct($element) {
      $this->elem = static::document()->createElement($element);
    }
    public function element() {
      return $this->elem;
    }
		public function attr($key, $value) {
      $this->elem->setAttribute($key, $value);
      return $this;
    }
    public function text($tt) {
      $this->elem->textContent = $tt . '';
      return $this;
    }
    public function append($dom) {
      $this->elem->appendChild($dom->element());
      return $this;
    }
    public function render() {
      return static::document()->saveHTML($this->elem);
    }
    public function __toString()
    {
        return $this->render();
    }
}
