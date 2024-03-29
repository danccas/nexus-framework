<?php
namespace Core;

class Str {
  protected $text;
  function __construct($text = '') {
    $this->text = strval($text);
  }
  function notilde() {
    return iconv('UTF-8', 'ASCII//TRANSLIT', $this->text);
    $noTildes = strtr($this->text,
        'áéíóúüÁÉÍÓÚÜ',
        'aeiouuAEIOUU'
    );
    return $noTildes;
  }
  function elipsis($cant) {
    return mb_strimwidth($this->text, 0, $cant, "...");
  }
  function trunc($cant) {
    return substr($this->text, 0, $cant);
  }
  function studlyToSnake() {
    $snakeText = preg_replace_callback('/[A-Z]/', function ($match) {
        return '_' . strtolower($match[0]);
    }, $this->text);
    return ltrim($snakeText, '_');
  }
  function snakeCase() {
    $text = $this->notilde();
    $text = preg_replace('/[^a-zA-Z0-9]+/', ' ', $text);
    $words = explode(' ', $text);
    $snakeCaseText = strtolower(implode('_', $words));
    return $snakeCaseText;
  }
  function lower() {
    return strtolower($this->text);
  }
  function upper() {
    return strtoupper($this->text);
  }
  function __toString() {
    return $this->text;
  }
}