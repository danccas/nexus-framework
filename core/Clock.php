<?php

namespace Core;

class Clock
{
    protected $moment;
    protected $current;
    protected static $DIAS = ['Lunes', 'Martes', 'Miercoles', 'Juevaes', 'Viernes', 'Sabado', 'Domingo'];
    protected static $MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    function __construct($time = null)
    {
        $this->current = time();
        if(empty($time)) {
            $this->moment = time();
        } else {
            if (is_numeric($time)) {
                $this->moment = $time;
            } else {
                $this->moment = strtotime($time);
            }
        }
    }
    function date($relleno = 'Sin Fecha')
    {
        $formato = 'd/m/Y';
        return date($formato, $this->moment);
    }
    function time($formato = 'h:i:s A')
    {
        return date($formato, $this->moment);
    }
    function long_date()
    {
        $fecha = $this->moment;
        $hora = !empty($hora) ? ' a las ' . date("h:i A", $fecha) : '';
        return ucfirst(static::$DIAS[date('w', $fecha)]) . ', ' . date('d', $fecha) . ' de ' . ucfirst(static::$MESES[date('n', $fecha) - 1]) . ' del ' . date('Y', $fecha) . $hora;
    }
    function ago() {
        return $this->age();
    }
    function age()
    {
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
        } elseif ($diferencia <= $MES - 5 * $DIA) {


            $prefijo = $signo ? 'El próximo ' : 'El pasado ';
            $sufijo  = '';
            $txt     = static::$DIAS[date('w', $fecha)] . ' ' . date('d', $fecha);
        } elseif ($diferencia <= $MES + 5 * $DIA) {
            $txt = 'un mes';
        } elseif ($diferencia <= $ANHO - 2 * $MES) {
            $txt = round($diferencia / $MES) . ' meses';
        } elseif ($diferencia <= $ANHO + 2 * $MES) {
            $txt = 'un año';
        } else {
            $prefijo = '';
            $sufijo  = '';
            $txt     = ucfirst(static::$DIAS[date('w', $fecha)]) . ' ' . date('d', $fecha) . ' de ' . static::$MESES[date('n', $fecha) - 1] . ' del ' . date('Y', $fecha);
        }
        return $prefijo . $txt . $sufijo;
    }
}
