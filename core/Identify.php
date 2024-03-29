<?php

namespace Core;

use Core\Session;
use Core\Model;

class Identify extends Model
{
  private static $idsession = 'id20230328';
  public static $filtros  = array();

  public static function current() {
    $rr = parent::instance();
      if (Session::instance()->has(self::$idsession) && empty($rr->id)) {
        $rr->is_valid = true;
        $rr->exists = true;
        $res = (array) Session::instance()->read(self::$idsession)['data'];
        foreach($res as $k => $v) {
          $rr->{$k} = $v;
        }
      }
      return $rr;
    }
  static function user() {
    return static::current();
  }
  public function asign($data) {
    static::current()->fill($data);
    return $this;
  }
  public function is_valid()
  {
    return static::current()->is_valid;
  }
  static function check($code_company, $username, $password, &$error = null)
  {
    if ($res = static::current()->handle($code_company, $username, $password)) {
      Session::instance()->write(self::$idsession, array(
        'data' => $res
      ));
      return true;
    } else {
      if (static::current()->isForcing()) {
        /*   $error = "Su cuenta ha sido bloqueada, vuelva a intentarlo m&aacute;s tarde.";
				$db->update('PITS_USUARIO', array('BLOQUEADO' => DB::time(time() + 60 * 60)), 'ID = ' . $dd['ID']);*/
  			 $error = "Los datos son incorrectos(2-force)";
      } else {
         $error = "Los datos son incorrectos(2-no-force)";
        //usuario_log($dd, 'Intento de inicio de sesion', ULOG_INTENTO);
      }
      return false;
    }
  }
  function isForcing() {
    return false;
  }
  static function close(&$usuario = null)
  {
    //$ce = Identify::instance();
    Session::instance()->delete(self::$idsession);
    unset($usuario);
  }
  function __get2($key)
  {
    return $this->data($key);
  }
}
