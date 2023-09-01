<?php
namespace Core;

use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Style\Alignment;
use \PhpOffice\PhpSpreadsheet\Style\Border;
use \PhpOffice\PhpSpreadsheet\Style\Fill;
use \PhpOffice\PhpSpreadsheet\RichText\RichText;
use \PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use \PhpOffice\PhpSpreadsheet\Cell\DataType;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;


//$ls = array_map(function($n) { return 'B' . $n; }, range('A','Z'));
//echo implode("','", $ls);exit;
class Excelity
{
  #private $_version    = 20170831110200;
  private $_version    = 20201114174700;
  private $letras      = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ'];
  private $ExcelBase   = null;
  private $titulo      = '';
  private $subtitulo   = '';
  private $styles      = null;
  private $max         = array('x' => 1, 'y' => 1);
  private $current     = array('x' => 1, 'y' => 1);
  private $current_page = null;
  private $page_save   = false;
  private $has_header  = false;
  private $empresa     = null;
  private $busy_cells  = [];
  private $moving_cells = true;
  const FILTER_HEADER = 1;

  function __construct()
  {
    $this->ExcelBase = new Spreadsheet();
    $this->ExcelBase->getDefaultStyle()->getAlignment()->setWrapText(true);
    $this->ExcelBase->getActiveSheet()
      ->getStyle('B10')
      ->getAlignment()
      ->setWrapText(true);
  }
  function l2n($letra)
  {
    return array_search($letra, $this->letras) + 1;
  }
  function n2l($n)
  {
    if (!isset($this->letras[$n - 1])) {
      exit('range');
    }
    return $this->letras[$n - 1];
  }
  function movingCells($x)
  {
    $this->moving_cells = !empty($x);
  }
  function parseLoad()
  {

    $objPHPExcel = IOFactory::load($this->empresa);
    $objPHPExcel->setActiveSheetIndex(0);
    $size = $objPHPExcel->setActiveSheetIndex(0)->getHighestRowAndColumn(); //[row] => 34 [column] => AA
    $datos = array();
    for ($y = 1; $y <= $size['row']; $y++) {
      for ($x = 1; $x <= $this->l2n($size['column']); $x++) {
        $datos[$y][] = $objPHPExcel->getActiveSheet()->getCell($this->n2l($x) . $y)->getValue();
      }
    }
    return $datos;
  }
  function setTitle($titulo)
  {
    return $this->newPage($titulo);
  }
  function newPage($titulo)
  {
    $titulo = !empty($titulo) ? $titulo : 'SIN TITULO';
    if (is_null($this->current_page)) {
      $this->current_page = 0;
      $this->ExcelBase->setActiveSheetIndex($this->current_page);
      $this->ExcelBase->getActiveSheet()->setTitle(preg_replace("/[^\w\s]/", '', $titulo));
    } else {
      $this->buildHeaderandStyle();
      #        $this->margin_left = 0;
      #        $this->margin_top  = 0;
      $this->max = array('x' => 1, 'y' => 1);
      $this->current = array('x' => 1, 'y' => 1);
      $this->current_page += 1;
      $this->ExcelBase->createSheet($this->current_page);
      $this->ExcelBase->setActiveSheetIndex($this->current_page);
      $this->page_save  = false;
      $this->busy_cells = [];
      $this->has_header = false;
      $this->ExcelBase->getActiveSheet()->setTitle(preg_replace("/[^\w\s]/", '', $titulo));
    }
  }
  function createHeader($titulo, $subtitulo = '')
  {
    $this->titulo    = $titulo;
    $this->subtitulo = $subtitulo;
    $this->has_header = true;
    $this->max['y']  = 2;
    $this->max['x']  = 1;
    $this->current['y']++;
    $this->setStyle('A1:AG100', array('background-color' => 'ffffff'));
  }
  function buildHeaderandStyle()
  {
    global $USUARIO;
    if (!$this->page_save) {
      $this->processStyle();
    }
    if ($this->page_save || !$this->has_header) {
      return;
    }
    $rtd = $this->n2l(1) . (1);
    $rt = $rtd . ':' . $this->n2l($this->max['x']) . (1);
    $this->ExcelBase->getActiveSheet()->mergeCells($rt);
    $objRichText = new RichText();
    $objRichText->createText('');
    $parte1 = $objRichText->createTextRun($this->titulo . "\n");
    $parte1->getFont()->setBold(true);
    $parte1->getFont()->setItalic(false);
    $parte1->getFont()->setSize(20);
    $parte2 = $objRichText->createTextRun($this->subtitulo);
    $parte2->getFont()->setBold(false);
    $parte2->getFont()->setItalic(false);
    $parte2->getFont()->setSize(16);
    $this->ExcelBase->getActiveSheet()->getCell($rtd)->setValue($objRichText);
    $this->ExcelBase->getActiveSheet()->getRowDimension(1)->setRowHeight(100);
    $this->ExcelBase->getActiveSheet()->getStyle($rtd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $this->ExcelBase->getActiveSheet()->getStyle($rtd)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $this->ExcelBase->getActiveSheet()->getStyle($rtd)->getAlignment()->setWrapText(true);

    return;
  }
  function setSizeColumn($celda, $size = null)
  {
    if (!is_array($celda)) {
      $toPx = (float) $size;
      $toPx /= 3.72;
      $this->ExcelBase->getActiveSheet()->getColumnDimension($celda)->setWidth($toPx);
    } else {
      foreach ($celda as $k => $c) {
        $sp = explode(',', $k);
        if (!empty($sp)) {
          foreach ($sp as $k) {
            if (!empty($k)) {
              $toPx = (float) $c;
              $toPx /= 3.72;
              $this->ExcelBase->getActiveSheet()->getColumnDimension($k)->setWidth($toPx);
            }
          }
        }
      }
    }
  }
  function setSizeRow($celda, $size = null)
  {
    if (!is_array($celda)) {
      $this->ExcelBase->getActiveSheet()->getRowDimension($celda)->setRowHeight($size);
    } else {
      foreach ($celda as $k => $c) {
        $sp = explode(',', $k);
        if (!empty($sp)) {
          foreach ($sp as $k) {
            if (!empty($k)) {
              $this->ExcelBase->getActiveSheet()->getRowDimension($k)->setRowHeight($c);
            }
          }
        }
      }
    }
  }
  function setMargin($left = 0, $top = 0)
  {
    #        $this->margin_left = $left;
    #        $this->margin_top  = $top;
    #        $this->max['y']   += $top;
  }
  function setStyle($celda, $style)
  {
    if (strpos($celda, ',') !== false) {
      $separado = explode(',', $celda);
      foreach ($separado as $l) {
        if (!empty($l)) {
          $this->setStyle($l, $style);
        }
      }
      return;
    }
    if (!isset($this->styles[$celda])) {
      $this->styles[$celda] = array(
        'font'      => array(),
        'alignment' => array(),
        'fill'      => array(),
        'borders'   => array(),
        'others'    => array()
      );
    }
    if (!empty($style)) {
      foreach ($style as $kst => $vst) {
        $this->css_to_excel($celda, $kst, $vst);
      }
    }
  }
  function css_to_excel($celda, $key, $value)
  {
    if (!empty($key)) {
      $key = strtolower($key);
      if ($key == 'font-size') {
        $this->styles[$celda]['font']['size'] = $value;
      } elseif ($key == 'font-family') {
        $this->styles[$celda]['font']['name'] = $value;
      } elseif ($key == 'color') {
        $value = str_replace('#', '', $value);
        $this->styles[$celda]['font']['color'] = array('rgb' => $value);
      } elseif ($key == 'text-align') {
        if ($value == 'center') {
          $this->styles[$celda]['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
        } elseif ($value == 'left') {
          $this->styles[$celda]['alignment']['horizontal'] = Alignment::HORIZONTAL_LEFT;
        } elseif ($value == 'right') {
          $this->styles[$celda]['alignment']['horizontal'] = Alignment::HORIZONTAL_RIGHT;
        }
      } elseif ($key == 'vertical-align') {
        if ($value == 'top') {
          $this->styles[$celda]['alignment']['vertical'] = Alignment::VERTICAL_TOP;
        } elseif ($value == 'middle') {
          $this->styles[$celda]['alignment']['vertical'] = Alignment::VERTICAL_CENTER;
        } elseif ($value == 'bottom') {
          $this->styles[$celda]['alignment']['vertical'] = Alignment::VERTICAL_BOTTOM;
        }
      } elseif ($key == 'background-color') {
        $value = str_replace('#', '', $value);
        $this->styles[$celda]['fill'] = array(
          'fillType'  => Fill::FILL_SOLID,
          'startColor' => array('argb' => $value)
        );
      } elseif ($key == 'border') {
        $value = str_replace('#', '', $value);
        $this->styles[$celda]['borders'] = array(
          'outline' => [
            'borderStyle'  => Border::BORDER_THIN,
            'color' => array('rgb' => $value),
          ]
        );
        //debug($this->styles[$celda]['borders']);
      } elseif ($key == 'font-weight') {
        $this->styles[$celda]['font']['bold'] = true;
      } elseif ($key == 'rowspan') {
        $this->styles[$celda]['others']['rowspan'] = (int) $value - 1;
      } elseif ($key == 'colspan') {
        $this->styles[$celda]['others']['colspan'] = (int) $value - 1;
      } elseif ($key == 'filter') {
        $this->styles[$celda]['others']['filter'] = (bool) $value;
      }
    }
  }
  function insertImage($url, $width = null, $height = null, $x = null, $y = null)
  {
    $img = new Drawing();
    $img->setName($this->titulo . ':' . uniqid());
    $img->setDescription(utf8_encode($this->titulo));
    $img->setPath($url);
    if (!is_null($x)) {
      $img->setOffsetX($x);
    }
    if (!is_null($y)) {
      $img->setOffsetY($y);
    }
    if (!is_null($width)) {
      $img->setHeight($width);
    }
    if (!is_null($height)) {
      $img->setHeight($height);
    }
    $img->setWorksheet($this->ExcelBase->getActiveSheet());
    $img->setCoordinates($this->n2l($this->current['x']) . $this->current['y']);
  }
  function stepRow()
  {
    $this->max['y']++;
  }
  function stepCol()
  {
    $this->max['x']++;
  }
  function nextLine()
  {
    return $this->current['y']++;
  }
  function setPoint($point)
  {
    if (preg_match("/^(?<ll>[A-Z]+)(?<nn>\d+)$/i", $point, $salida)) {
      $this->current = array(
        'x' => $this->l2n($salida['ll']),
        'y' => (int) $salida['nn'],
      );
      $this->lastPoint = $point;
    }
  }
  function getLastRow($i = 0)
  {
    return $this->max['y'] + $i;
  }
  function getLastCol($i = 0)
  {
    return $this->n2l($this->max['x'] + $i);
  }
  function getLastPoint($i = 0)
  {
    return $this->getLastCol($i) . $this->getLastRow($i);
  }
  /*    function point($point, $label) {
        $point = $this->__convertPoint($point);
        if(!empty($point)) {
            $points = explode(':', $point);
            if(count($points) > 1) {
                $this->ExcelBase->getActiveSheet()->mergeCells($point);
            }
            $point = $points[0];
            $this->__insertPoint($label, $point);
        }
        
    }*/
  function insertHeader($body)
  {
    if (!empty($body)) {
      $sty = array(
        'font-weight'      => 'bold',
        'text-align'       => 'center',
        'background-color' => '#eff0f1',
        'border'           => '#000000',
      );
      $body = array_map(function ($n) use ($sty) {
        if (is_array($n)) {
          $n[1] = $sty + $n[1];
        } else {
          $n = array($n, $sty);
        }
        return $n;
      }, $body);
      $desde = $this->n2l($this->current['x']) . $this->current['y'];
      $hasta = $this->n2l($this->current['x'] + count($body) - 1) . ($this->current['y'] + 1);
      $body = array($body);
      $e = $this->insertBody($body);
      $this->setStyle($desde . ':' . $hasta, array('filter' => 'true'));
      if ($e === false) {
        //$e(false, null);
      }
    }
  }
  function insertPoint($label, $point = null)
  {
    if (is_null($point)) {
      $point = $this->n2l($this->current['x']) . $this->current['y'];
    }
    return $this->__insertPoint($label, $point);
  }
  function insertLine($fila)
  {
    if (!empty($fila) && is_array($fila)) {
      foreach ($fila as $i => $label) {
        $point = $this->n2l($this->current['x']) . $this->current['y'];
        while (in_array($point, $this->busy_cells) && $this->moving_cells) {
          $point = $this->__addcelltoPoint($point, 1, 0);
          $this->current['x']++;
        }
        $this->__insertPoint($label, $point); /* TODO */
        $this->current['x']++;
      }
    }
    $this->current['y']++;
  }
  function insertBody($body, $border_cell = false, $rotate = false)
  {
    if (!empty($body) && is_array($body)) {
      if ($rotate) {
        $body = $this->__invertir_array($body);
        if ($this->moving_cells) {
          $body = array_map(function ($n) {
            return array_filter($n, function ($n) {
              return !is_null($n);
            });
          }, $body);
        }
      }
      $inicio = $this->current;
      $ancho = $this->obtener_ancho_body($body);
      $this->max['x'] = ($ancho +  $this->current['x'] - 1) > $this->max['x'] ? $ancho +  $this->current['x'] - 1 : $this->max['x'];
      foreach ($body as $i => $fila) {
        $this->current['x'] = $inicio['x'];
        #          print_r($this->current);
        $this->insertLine($fila);
      }
      $this->max['y'] = $this->current['y'] > $this->max['y'] ? $this->current['y'] : $this->max['y'];
      $this->current['x'] = $inicio['x'];
      if (!empty($border_cell)) {
        $this->setStyle($this->n2l($inicio['x']) . $inicio['y'] . ':' . $this->n2l($inicio['x'] + $ancho - 1) . ($this->current['y'] - 1), array('border' => '000000'));
      }
    }
    #      exit;
  }
  function __invertir_array($body)
  {
    $rp = array();
    foreach ($body as $y => $tr) {
      foreach ($tr as $x => $td) {
        if (!isset($rp[$x])) {
          $rp[$x] = array();
        }
        $rp[$x][$y] = $td;
      }
    }
    return $rp;
  }
  function __insertLabel($label, $point)
  {
    $tipo = DataType::TYPE_STRING;
    if (is_numeric($label) && is_int($label)) {
      $tipo = DataType::TYPE_NUMERIC;
    }
    $this->lastPoint = $point;
    $this->ExcelBase->getActiveSheet()->setCellValueExplicit($point, $label, $tipo);
    $this->ExcelBase->getActiveSheet()->getStyle($point)->getAlignment()->setWrapText(true);
  }
  function __insertPoint($label, $point)
  {
    if (!is_array($label) && !is_null($label)) {
      $this->__insertLabel($label, $point);
    } elseif (is_array($label) && in_array(count($label), [1, 2])) {
      $this->__insertLabel($label[0], $point);
      if (!empty($label[1])) {
        if (!empty($label[1]['rotate'])) {
          $this->ExcelBase->getActiveSheet()->getStyle($point)->getAlignment()->setTextRotation($label[1]['rotate']);
        }
        if (!empty($label[1]['colspan'])) {
          $this->current['x'] += $label[1]['colspan'] - 1;
        }
        if (!empty($label[1]['rowspan']) && $label[1]['rowspan'] > 1) {
          $label[1]['colspan'] = !empty($label[1]['colspan']) ? $label[1]['colspan'] : 1;
          for ($y = 1; $y <= $label[1]['rowspan'] - 1; $y++) {
            for ($x = 1; $x <= $label[1]['colspan']; $x++) {
              $this->busy_cells[] = $this->__addcelltoPoint($point, $x - 1, $y);
            }
          }
        }
        $this->setStyle($point, $label[1]);
      }
    }
  }
  function __addcelltoPoint($point, $more_x = 0, $more_y = 0)
  {
    if (!empty($point)) {
      $point = strtoupper($point);
      if (preg_match("/^(?<ll>[A-Z]+)(?<nn>\d+)$/i", $point, $salida)) {
        return $this->n2l($this->l2n($salida['ll']) + $more_x) . '' .  ((int) $salida['nn'] + $more_y);
      }
    }
    return null;
  }
  function processStyle()
  {
    if (!empty($this->styles)) {
      $stylez = array();
      foreach ($this->styles as $cell => $style_array) {
        $cell = $this->__convertPoint($cell);
        $stylez[$cell] = $style_array;
      }
      foreach ($stylez as $cell => $style_array) {
        if (!empty($cell)) {
          $others = $style_array['others'];
          if (!empty($others)) {
            $rowspan = !empty($others['rowspan']) ? $others['rowspan'] : 0;
            $colspan = !empty($others['colspan']) ? $others['colspan'] : 0;
            if ($colspan >= 1 || $rowspan >= 1) {
              $to = $this->__addcelltoPoint($cell, $colspan, $rowspan);
              $this->ExcelBase->getActiveSheet()->mergeCells($cell . ':' . $to);
              $cell = $cell . ':' . $to;
            }
            if (!empty($others['filter'])) {
              $this->ExcelBase->getActiveSheet()->setAutoFilter($cell);
            }
          }
          unset($style_array['others']);
          $this->ExcelBase->getActiveSheet()->getStyle($cell)->applyFromArray($style_array);
        }
      }
      $this->styles = null;
    }
  }
  function __convertPoint($k)
  {
    if (!empty($k)) {
      $k = trim($k);
      if (preg_match("/^[\d]+$/i", $k)) {
        $k = 'A' . $k . ':' . $this->n2l($this->max['x']) . $k;
      } elseif (preg_match("/^[A-Z]+$/i", $k)) {
        $min_y = 2;
        $k = $k . $min_y . ':' . $k . $this->max['y'];
      } elseif (preg_match("/^[\w]+$/i", $k)) {
        // Punto
      } elseif (preg_match("/^([\w]+)\:([\w]+)$/i", $k)) {
        // Range
      } else {
        $k = null;
      }
    }
    return $k;
  }
  function forceDownload($name)
  {
    $filename = $name . '.xlsx';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $this->save();
    exit;
  }
	function attachmentFile($name){
    return $this->save( 'php://output', true );
	}
  function save($save = 'php://output', $attachment = false )
  {
    $this->buildHeaderandStyle();
    $this->ExcelBase->setActiveSheetIndex(0);
    $this->ExcelBase->getProperties()->setCreator("SIMACI")
      ->setLastModifiedBy("SIMACI")
      ->setTitle("Office 2007 XLSX Test Document")
      ->setSubject("Office 2007 XLSX Test Document")
      ->setDescription("SIMACI")
      ->setKeywords("office 2007 openxml php")
      ->setCategory("SIMACI");
    // $objWriter = IOFactory::createWriter($this->ExcelBase, 'Excel2007');
    // $objWriter->save($save);
		if ($attachment ) {
			$writer = new Xlsx($this->ExcelBase);
			ob_start();
			$writer->save($save);
			$data = ob_get_clean(); 
			return $data;
		} else {
			$writer = new Xlsx($this->ExcelBase);
			$writer->save($save);
		}

  }
  private function obtener_ancho_body($body)
  {
    $max = 0;
    if (!empty($body)) {
      foreach ($body as $b) {
        if (is_array($b)) {
          $tmp = count($b);
          foreach ($b as $c) {
            if (is_array($c) && !empty($c[1]) && !empty($c[1]['colspan'])) {
              $tmp += $c[1]['colspan'] - 1;
            }
          }
          $max = $max > $tmp ? $max : $tmp;
        }
      }
    }
    return $max;
  }
}
