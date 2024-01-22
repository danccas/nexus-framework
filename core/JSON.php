<?php
namespace Core;

class JSON {
  private function utf8ize( $mixed ) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}
  public static function encode($ob) {
    try {
      $rp = json_encode($ob, JSON_THROW_ON_ERROR);
    } catch(\JsonException $exception) {
       return static::encode(static::utf8ize($ob));   
    }
    return $rp;
  }
  public static function decode($str, $to_class = false) {
    return json_decode($str, $to_class);
  }
}
