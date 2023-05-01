<?php


/*


Actualizado 24-07-2021

*/

class Chartjs
{
  static function scatter($listado, $labels = null, $type = 'scatter')
  {
    $estadisticas = array();
    $_est = array();
    if (empty($labels)) {
      $labels = [
        [
          'COLLECTION' => 'DEFAULT',
          'NAME'       => 'DEFAULT',
        ]
      ];
    }
    if (!empty($listado) && is_array($listado)) {
      foreach ($listado as $m) {
        $m['COLLECTION'] = isset($m['COLLECTION']) ? $m['COLLECTION'] : 'DEFAULT';
        if (!isset($estadisticas[$m['COLLECTION']])) {
          $estadisticas[$m['COLLECTION']] = [];
        }
        $estadisticas[$m['COLLECTION']][] = array('x' => $m['AXIS_X'], 'y' => $m['AXIS_Y']);
      }
    }

    $labels = array_map(function ($n) use ($estadisticas) {
      $existe = empty($estadisticas[$n['COLLECTION']]) ? [] : $estadisticas[$n['COLLECTION']];
      $existe = array_filter($existe);
      $existe = !empty($existe);
      return array(
        'label'           => $n['NAME'],
        'fill'            => false,
        'backgroundColor' => $n['COLOR'],
        'borderColor'     => $n['COLOR'],
        'show'            => $existe,
        'borderWidth'     => isset($n['BORDER']) ? $n['BORDER'] : 2,
        'data'            => !empty($estadisticas[$n['COLLECTION']]) ? $estadisticas[$n['COLLECTION']] : array(),
      );
    }, $labels);
    $labels = array_filter($labels, function ($n) {
      return $n['show'];
    });
    $labels = array_values($labels);
    return array(
      'type'     => $type,
      'data'     => array(
        'label'    => '# Velocidad',
        'datasets' => $labels,
      ),
    );
  }
  static function line($listado, $labels = null, $type = 'line', $foreign = 'COLLECTION')
  {
    #debug($listado);
    $estadisticas = array();
    $_est = array();
    if (empty($labels)) {
      $labels = [
        [
          'COLLECTION' => 'DEFAULT',
          'AXIS_X'     => 'DEFAULT',
          'AXIS_Y'     => 'DEFAULT',
          'NAME'       => 'DEFAULT',
        ]
      ];
    }
    $_labels = [];
    foreach ($labels as $v) {
      $_labels[$v[$foreign]] = $v;
    }
    $labels = $_labels;
    //array_walk($labels, function(&$v, $k) use($foreign) { $v[$foreign] = $k; });
    $default = array_map(function ($n) {
      return null;
    }, $labels);
    if (!empty($listado) && is_array($listado)) {
      foreach ($listado as $m) {

        $m['COLLECTION'] = empty($m['COLLECTION']) ? $m['collection'] : $m['COLLECTION'];
        $m['AXIS_Y'] = empty($m['AXIS_Y']) ? $m['axis_y'] : $m['AXIS_Y'];
        $m['AXIS_X'] = empty($m['AXIS_X']) ? $m['axis_x'] : $m['AXIS_X'];

        if (!isset($estadisticas[$m['AXIS_X']])) {
          $estadisticas[$m['AXIS_X']] = $default;
        }
        if (isset($m[$foreign])) {
          #echo "x:" . $m['AXIS_X'] . "\n";
          #echo "y:" . $m['AXIS_Y'] . "\n";
          #echo "z:" . $m[$foreign] . "\n\n";
          $estadisticas[$m['AXIS_X']][$m[$foreign]] = $m['AXIS_Y'];
        } else {
          $estadisticas[$m['AXIS_X']]['DEFAULT'] = $m['AXIS_Y'];
        }
      }
    }
    #debug($estadisticas);
    foreach ($estadisticas as $f => $j) {
      foreach ($j as $t => $m) {
        $_est[$t][] = $m;
      }
    }
    #debug($_est);
    #debug($labels);
    $tiempos = array_keys($estadisticas);
    #debug($tiempos);
    $labels = array_map(function ($n) use ($_est, $foreign) {

      $existe = empty($_est[$n[$foreign]]) ? "" : array_filter($_est[$n[$foreign]]);
      $n['COLOR'] = empty($n['COLOR']) ? "" : $n['COLOR'];
      $existe = !empty($existe);
      $n['NAME'] = empty($n['NAME']) ? "" : $n['NAME'];
      return array(
        'label'           => $n['NAME'],
        'fill'            => false,
        'backgroundColor' => $n['COLOR'],
        'borderColor'     => $n['COLOR'],
        'borderWidth'     => isset($n['BORDER']) ? $n['BORDER'] : 2,
        'barThickness'    => isset($n['BORDER']) ? $n['BORDER'] : 2,
        'show'            => $existe,
        'data'            => !empty($_est[$n[$foreign]]) ? $_est[$n[$foreign]] : array(),
      );
    }, $labels);

    #debug($labels);
    $labels = array_filter($labels, function ($n) {
      return $n['show'];
    });

    $labels = array_values($labels);
    return array(
      'type'     => $type,
      'data'     => array(
        'labels'   => $tiempos,
        'datasets' => $labels,
      ),
    );
  }
  static function pie($listado, $labels, $type = 'pie')
  {
    $estadisticas = array();
    #    array_walk($labels, function(&$v, $k) { $v['COLLECTION'] = $k; });
    #debug($labels);
    foreach ($labels as $l) {
      foreach ($listado as $m) {
        $m['COLLECTION'] = empty($m['COLLECTION']) ? $m['collection'] : $m['COLLECTION'];
        $m['AXIS_Y'] = empty($m['AXIS_Y']) ? $m['axis_y'] : $m['AXIS_Y'];
        if ($l['COLLECTION'] == $m['COLLECTION']) {
          $estadisticas[$l['COLLECTION']] = $m['AXIS_Y'];
        }
      }
      if (!isset($estadisticas[$l['COLLECTION']])) {
        $estadisticas[$l['COLLECTION']] = 0;
      }
    }
    $datasets = array(
      'label'           => 'Main Dataset',
      'data'            => array_values($estadisticas),
      'backgroundColor' => array_values(array_map(function ($n) {
        return $n['COLOR'];
      }, $labels)),
    );
    return array(
      'type'     => $type,
      'data'     => array(
        'labels'   => array_values(array_map(function ($n) {
          return !empty($n['nombre']) ? $n['nombre'] : $n['COLLECTION'];
        }, $labels)),
        'datasets' => array($datasets),
      )
    );
  }
}
