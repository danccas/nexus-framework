<?php

namespace Core;

use \TCPDF;

//require_once( __DIR__ . '/dist/tcpdf2/tcpdf.php');

class Pdf extends TCPDF {
  public $autor      = null;
  public $showHeader = false;
	public $empresa    = null;
	public $code_header = null;
  public $code_footer = null;

  function __construct() {
      parent::__construct();
  }
  public function header() {
    if(empty($this->code_header)) {
      return '';
    }
    $this->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $this->code_header, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'top', $autopadding = true);
		$this->SetY(5);
		#$img_file = 'https://www.creainter.com.pe/assets/images/logo.png';#/var/www/html/simaci.com.pe/public/assets2/img/logo-light.png';
		#$this->SetAlpha(0.1);
		#$this->Image($img_file, 50, 85, 200, 0);
  }

  public function Footer() {
    if(empty($this->code_footer)) {
      return '';
    }
    $this->SetY(-20);
    $this->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $this->code_footer, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'top', $autopadding = true);
  }
}

class PDFily {
	private $_instance = null;
	private $is_manual = false;
	public $_blade = null;

  function __construct($empresa = null) {
    $this->_instance = new Pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $this->_instance->empresa = $empresa;
    $this->_instance->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH,PDF_HEADER_TITLE,PDF_HEADER_STRING);
    $this->_instance->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $this->_instance->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $this->_instance->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $this->_instance->SetMargins(10, 25, 10);
    $this->_instance->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->_instance->SetFooterMargin(PDF_MARGIN_FOOTER);
    $this->_instance->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $this->_instance->setImageScale(PDF_IMAGE_SCALE_RATIO);
    //$this->_instance->setLanguageArray($l);
    $this->_instance->setFontSubsetting(true);
    $this->_instance->SetFont('helvetica', '', 10, '', true);
    }
    public function showHeader() {
      $this->_instance->showHeader = true;
    }
    public function setAutor($nombre) {
      $this->_instance->autor = $nombre;
		}
		public function view($theme, $params = [], $orientacion = null) {
			$this->is_manual = true;
      $this->_blade = (new Blade($theme))->append($params);
			return $this->addPage($this->_blade, $orientacion);
		}
    public function addPage($html, $tipo = null) {

      $html2 = '';
			$part = explode('<header>', $html);
      $html2 .= $part[0];
      if(!empty($part[1])) {
			  $part = explode('</header>', $part[1]);
			  $html2 .= $part[1];
		  	$this->_instance->code_header = $part[0];
      }

			$html = '';
			$part = explode('<footer>', $html2);
      $html .= $part[0];
      if(!empty($part[1])) {
		  	$part = explode('</footer>', $part[1]);
        $html .= $part[1];
			  $this->_instance->code_footer = $part[0];
      }
			unset($html2);
			if(is_null($tipo)) {
        $this->_instance->AddPage();
      } else {
        $this->_instance->AddPage($tipo, 'A4');
      }
      $this->_instance->writeHTMLCell($w=0, $h=0, $x='', $y=20, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='L', $autopadding = true);
    }
    public function save($name, $tipo = 'I') {
			if($this->_blade !== null) {
				$this->_blade->append(['pdf' => $this->_instance]);
        if(!$this->is_manual) {
					@$this->addPage($this->_blade, $this->_blade->orientacion);
				}
			}
      $name = strpos($name, '.pdf') === false ? $name . '.pdf' : $name;
      return $this->_instance->Output($name, $tipo);
    }
    public function forceDownload($name) {
			  header('Content-Type: application/pdf');
        $this->save($name, 'D');
        exit();
    }
}
