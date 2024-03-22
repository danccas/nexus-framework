<?php
namespace Core;

class JSON {
  private static function utf8ize( $mixed ) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = static::utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
  }
  public static function stringify($ob) {
    return static::encode($ob);
  }
  public static function parse($ob, $to_array = false) {
    return static::decode($ob, $to_array);
  }
  public static function encode($ob) {
    try {
      $rp = json_encode($ob, JSON_THROW_ON_ERROR);
    } catch(\JsonException $exception) {
       return static::encode(static::utf8ize($ob));   
    }
    return $rp;
  }
  public static function decode($str, $to_array = false) {
    // Primero, intenta decodificar el string tal cual está.
    $decoded = json_decode($str, $to_array);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Si el string no es un JSON válido, intenta corregirlo.
    // Detecta y transforma patrones no válidos en JSON válido.
    // Asume que cualquier cosa que no esté encerrada en comillas pero esté dentro de {} o [] debe ser tratada como un string.
    $str = trim($str);
    if (preg_match('/^\{.*\}$/', $str) || preg_match('/^\[.*\]$/', $str)) {
        // Reemplaza { por [ y } por ] para unificar el manejo como array
        $str = rtrim(ltrim($str, '{'), '}');
        $str = '[' . $str . ']';
        
        // Asegura que todos los elementos estén correctamente entrecomillados
        $str = preg_replace('/([^",\[\]]+)(,|\])/', '"$1"$2', $str);
        $str = preg_replace('/(\[|"|,)([^",\[\]]+)(,|\])/', '$1"$2"$3', $str);

        // Intenta decodificar nuevamente
        $decoded = json_decode($str, $to_array);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }

    // Si aún no es válido o no se pudo corregir, retorna null o maneja el error.
    return null;
  }
}
