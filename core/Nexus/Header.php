<?php

namespace Core\Nexus;

class Header implements \JsonSerializable {
  protected $name        = null;
  protected $width        = null;
  protected $description = null;
  protected $sortable    = null;
  protected $filtable    = null;

  public function __construct($name) {
    $this->name = $name;
  }
  public static function name($name) {
    return new static($name);
  }
  public function width($px) {
    $this->width = $px;
    return $this;
  }
  public function description($px) {
    $this->description = $px;
    return $this;
  }
  public function sortable($px) {
    $this->sortable = $px;
    return $this;
  }
  public function filtable($px) {
    $this->filtable = $px;
    return $this;
  }
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    $params = [
      'name'        => $this->name,
      'width'       => $this->width,
      'descripcion' => $this->description,
      'sortable'    => $this->sortable,
      'filtable'    => $this->filtable,
    ];
    return array_filter($params);
  }
  public function __toString() {
    return $this->name;
  }
}
