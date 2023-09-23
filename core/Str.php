<?php
namespace Core;

class Str {
  protected $text;
  function __construct($text = null) {
    $this->text = $text;
  }
  function studlyToSnake() {
    $snakeText = preg_replace_callback('/[A-Z]/', function ($match) {
        return '_' . strtolower($match[0]);
    }, $this->text);
    return ltrim($snakeText, '_');
  }
  function snakeCase() {
    $text = preg_replace('/[^a-zA-Z0-9]+/', ' ', $this->text);
    $words = explode(' ', $text);
    $snakeCaseText = strtolower(implode('_', $words));
    return $snakeCaseText;
  }
}
