<?php

namespace Core;

class Clock
{
    protected $moment;
    protected $current;
    protected static $DIAS = ['Lunes', 'Martes', 'Miercoles', 'Juevaes', 'Viernes', 'Sabado', 'Domingo'];
		protected static $MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
		protected $data;

    function __construct($time = -1)
    {
        $this->current = time();
        if($time == -1) {
            $this->moment = time();
        } else {
            if (is_numeric($time)) {
                $this->moment = $time;
            } else {
              if(is_null($time)) {
                $this->moment = false;
              } else {
  							$this->moment = strtotime($time);
  							if(empty($this->moment)) {
  								$this->moment = false;
  							}
              }
            }
        }
    }
    function get($formato = 'd/m/Y H:i:s')
    {
      if($this->moment === false) {
        return null;
      }
        return date($formato, $this->moment);
    }
    function date($relleno = 'Sin Fecha')
		{
			if($this->moment === false) {
        return null;
      }
        $formato = 'd/m/Y';
        return date($formato, $this->moment);
    }
    function time($formato = 'h:i:s A')
    {
        return date($formato, $this->moment);
    }
    public function year()
    {
        return date('Y', $this->moment);
    }
		public function long()
    {
        $fecha = $this->moment;
        $hora = !empty($hora) || true ? ' a las ' . date("h:i A", $fecha) : '';
        return ucfirst(static::$DIAS[date('w', $fecha)]) . ', ' . date('d', $fecha) . ' de ' . ucfirst(static::$MESES[date('n', $fecha) - 1]) . ' del ' . date('Y', $fecha) . $hora;
    }
    function long_date()
    {
        $fecha = $this->moment;
        $hora = !empty($hora) ? ' a las ' . date("h:i A", $fecha) : '';
        return ucfirst(static::$DIAS[date('w', $fecha)]) . ', ' . date('d', $fecha) . ' de ' . ucfirst(static::$MESES[date('n', $fecha) - 1]) . ' del ' . date('Y', $fecha) . $hora;
		}
    function basic() {
      if($this->moment === false) {
        return null;
      }
			$rp = '';
			if(date('Y-m-d', $this->moment) != date('Y-m-d')) {
				$rp .= date('d/m/Y', $this->moment) . ', ';
			}
			return $rp . date('h:i:s A', $this->moment);
    }
    public function short() {
      return $this->basic();
    }
		static function pad($input, $limit,  $text = '0') {
			return str_pad($input, $limit, $text, STR_PAD_LEFT);
		}
		function parse($text, $format = 'd/m/Y H:i A') {
			$parse = date_parse_from_format($format, $text);
			$strtime = $parse['year'] . '-' . static::pad($parse['month'], 2) . '-' . static::pad($parse['day'], 2) . ' ' . static::pad($parse['hour'], 2) . ':' . static::pad($parse['minute'], 2) . ':' . static::pad($parse['second'], 2);
			$this->moment = strtotime($strtime);
			return $this;
		}
		function unix() {
			return $this->moment;
		}
		function iso() {
			if($this->moment === false) {
        return null;
      }
			return date('Y-m-d H:i:s', $this->moment);
		}
    function ago() {
        return $this->age();
    }
    function age()
		{
			if($this->moment === false) {
				return null;
			}
        $ahora = time();
        $fecha = $this->moment;
        $MINUTO = 60;
        $HORA   = $MINUTO * 60;
        $DIA    = $HORA * 24;
        $MES    = $DIA * 30;
        $ANHO   = $MES * 12;

        $diferencia = $fecha - $ahora == 0 ? 1 : $fecha - $ahora;
        $signo      = $diferencia > 0;
        $prefijo    = $signo ? 'En ' : 'Hace ';
        $sufijo     = '';
        $diferencia = $signo ? $diferencia : $diferencia * -1;

        if ($diferencia <= $MINUTO * 1) {
            $txt = 'instantes';
            // } elseif($diferencia <= $MINUTO * 9) {
            //    $txt = 'breve momentos';
        } elseif ($diferencia <= $HORA - 5 * $MINUTO) {
            $txt = round($diferencia / $MINUTO) . ' minutos';
        } elseif ($diferencia <= $HORA + 5 * $MINUTO) {
            $txt = 'Una hora';
        } elseif ($diferencia <= $HORA * 4) {
            $txt = round($diferencia / $HORA) . ' horas';
        } elseif ($diferencia <= $HORA * 12) {
            $prefijo = '';
            $txt     = 'Hoy, ' . date('h:i a', $fecha);
        } elseif ($diferencia <= $DIA + 6 * $HORA) {
            $prefijo = '';
            $sufijo  = ', ' . date('h:i a', $fecha);
            $txt     = $signo ? 'Mañana' : 'Ayer';
        } elseif ($diferencia <= $DIA * 6) {
            $txt     = round($diferencia / $DIA) . ' días';
            #  } elseif($diferencia <= $DIA * 6) {
            #    $prefijo = $signo ? 'Este ' : '';
            #    $sufijo  = $signo ? ''      : ' pasado';
            #    $txt     = $DIAS[date('w', $fecha)];
        } elseif ($diferencia <= $DIA * 8) {
            $txt     = 'una semana';
/*        } elseif ($diferencia <= $MES - 5 * $DIA) {


            $prefijo = $signo ? 'El próximo ' : 'El pasado ';
            $sufijo  = '';
            $txt     = static::$DIAS[date('w', $fecha)] . ' ' . date('d', $fecha);
#        } elseif ($diferencia <= $MES + 5 * $DIA) {
#            $txt = 'un mes';
#        } elseif ($diferencia <= $ANHO - 2 * $MES) {
#            $txt = round($diferencia / $MES) . ' meses';
#        } elseif ($diferencia <= $ANHO + 2 * $MES) {
						#            $txt = 'un año';*/
				} else {

            $prefijo = '';
            $sufijo  = '';
						$txt     = ucfirst(static::$DIAS[date('w', $fecha)]) . ' ' . date('d', $fecha) . ' de ' . static::$MESES[date('n', $fecha) - 1] . ' del ' . date('Y', $fecha);
						$txt = date('d/m/Y', $fecha);
        }
        return $prefijo . $txt . $sufijo;
    }
}
