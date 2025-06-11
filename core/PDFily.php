<?php

namespace Core;

use \Dompdf\Dompdf;
use \Dompdf\Options;

class PDFily {
    private $_instance = null;
    private $is_manual = false;
    public $_blade = null;

    function __construct($empresa = null) {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'helvetica');
        $options->set('isPhpEnabled', true);
        $this->_instance = new Dompdf($options);
        $this->_instance->empresa = $empresa;
    }
    public function showHeader() {
        $this->_instance->showHeader = true;
    }
    private function agregarPaginacion() {
        $script = '
            $font = $fontMetrics->getFont("helvetica");
            $size = 10;
            $text = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
            $pdf->text(250, 800, $text, $font, $size, array(0, 0, 0));';
        $this->_instance->getCanvas()->page_script($script);
    }
    private function agregarPaginacion2() {
        $canvas = $this->_instance->getCanvas();
        $totalPaginas = $canvas->get_page_count();
        for ($i = 1; $i <= $totalPaginas; $i++) {
            $canvas->page_script('
                $font = $fontMetrics->getFont("helvetica");
                $size = 10;
                $text = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
                $pdf->text(250, 800, $text, $font, $size, array(0, 0, 0));
            ');
        }
    }
    public function setAutor($nombre) {
        $this->_instance->autor = $nombre;
    }
    public function view($theme, $params = [], $orientacion = null) {
        $this->is_manual = true;
        $this->_blade = (new Blade($theme))->append($params);
        return $this->addPage($this->_blade, $orientacion);
    }
    public function addPage($html, $orientacion = null) {
      if($orientacion == 'L') {
        $orientacion = 'landscape';
      } elseif($orientacion == 'P') {
        $orientacion = 'portrait';
      }
      $html = strval($html);
      $this->_instance->setPaper('A4', $orientacion);
      $this->_instance->loadHtml(strval($html));
      $this->agregarPaginacion();
      $this->_instance->render();
    }
    public function save($name, $tipo = 'I') {
      if($this->_blade !== null) {
        $this->_blade->append(['pdf' => $this->_instance]);
        if(!$this->is_manual) {
          $this->addPage($this->_blade, $this->_blade->orientacion);
        }
      }
      $name = strpos($name, '.pdf') === false ? $name . '.pdf' : $name;
      if ($tipo === 'S') {
        return $this->_instance->output();
      } else {
        return $this->_instance->stream($name, ['Attachment' => ($tipo === 'I') ? false : true]);
      }
    }
    public function stream($name, $tipo = 'I') {
        return $this->_instance->stream($name, ['Attachment' => ($tipo === 'I') ? false : true]);
    }
    public function forceDownload($name) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        echo $this->_instance->output();
        exit();
    }
}
