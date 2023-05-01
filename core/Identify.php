<?php

namespace Core;

use Core\Session;
use Core\Model;

class Identify extends Model
{
  private static $idsession = 'id20230328';
  public static $filtros  = array();

  public $id        = null;
  public $data      = null;
  private $error    = null;
  public $is_valid  = false;
  private $session  = null;

  function __construct()
  {
    parent::__construct();
    if (Session::instance()->has(self::$idsession)) {
      $this->is_valid = true;
      $this->data = Session::instance()->read(self::$idsession)['data'];
      $this->id   = $this->data->id;
    }
  }
  static function user() {
    return static::instance();
  }
  function data($key = null)
  {
    if ($key == null) {
      return $this->data;
    }
    if(!empty($this->data) && property_exists($this->data, $key)) {
      return $this->data->{$key};
    }
    return null;
  }
  public function is_valid()
  {
    return static::instance()->is_valid;
  }
  static function check($code_company, $username, $password, &$error = null)
  {
    if ($res = static::instance()->handle($code_company, $username, $password)) {
      Session::instance()->write(self::$idsession, array(
        'data' => $res
      ));
      return true;
    } else {
      if (static::instance()->isForcing()) {
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
    $ce = Identify::instance();
    $ce->session->delete(self::$idsession);
    unset($usuario);
  }
  function __get($key)
  {
    return $this->data($key);
  }
}
