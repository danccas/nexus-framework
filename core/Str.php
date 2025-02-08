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
  function slug() {
    $slug = strtolower($this->text);

    // Reemplaza caracteres acentuados y especiales
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

    // Elimina caracteres no alfanuméricos (excepto espacios y guiones)
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

    // Reemplaza espacios y guiones consecutivos por un único espacio
    $slug = preg_replace('/[\s-]+/', ' ', $slug);

    // Reemplaza espacios por guiones
    $slug = str_replace(' ', '-', $slug);

    // Elimina guiones al principio y al final
    $slug = trim($slug, '-');

    return $slug; 
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
  function money() {
    if(is_null($this->text)) {
      return null;
    }
    $num = (float) $this->text;
    return number_format($num, 2, '.', ', ');
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
