<?php

class ExceptionSyntax extends \Exception {
    function obtenerRangoLineasArchivo($archivo, $inicio, $fin) {
        $resultado = '';
        $lineaActual = 1;
        $manejadorArchivo = fopen($archivo, 'r');
        if ($manejadorArchivo) {
            while (!feof($manejadorArchivo)) {
                $linea = fgets($manejadorArchivo);
                if ($lineaActual >= $inicio && $lineaActual <= $fin) {
                    $resultado .= $linea;
                } elseif ($lineaActual > $fin) {
                    break;
                }
                $lineaActual++;
            }
            fclose($manejadorArchivo);
        }
        return $resultado;
    }
}