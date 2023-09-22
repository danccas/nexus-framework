<?php

namespace Core;

use Exception;

class Blade
{
    private $id;
    private $_name;
    private $cache;
    protected $file;
    protected $fileCached;
    protected $inputs = [];
    protected $heredados = [];

    protected $components = [];

    public static $indexes = 0;
    private static $instance;
    public $partes = [];
    protected $sections = [];
    protected $html_asset = [];
    protected $html_prepend = [];
    protected $html_prepend_end = false;
    protected $html_after   = [];
    protected $is_load = false;

    public static $params = [];
    public static function instance($view_name = null)
    {
        if (null === static::$instance) {
            return static::$instance = new Blade($view_name, true);
        }
        return static::$instance;
    }
    public function __construct($file = null, $cache = true)
    {
        if (!is_null($file)) {
            $this->load($file, $cache);
        }
    }
    public static function component($dom, $classComponent) {
        static::instance()->components[$dom] = $classComponent;
        return static::instance();
    }
    public function name()
    {
        return $this->_name;
    }
    public function isLoad()
    {
        return $this->is_load;
    }
    public function asset($file, $name = null)
    {
        if (is_null($name)) {
            $name = $file;
        }
        if (isset($this->html_asset[$name])) {
            return false;
        }
        $this->html_asset[$name] = $file;
        return $this;
    }
    public function load($file, $cache = true)
    {
        $this->is_load = true;
        $this->_name = $file;
        $path = static::path($file);
        $this->id = ++static::$indexes;
        $this->cache = $cache;
        $this->file = $path['file'];
        $this->fileCached = $path['cache'];

        $modificado = cache('views')->item($file);
        if (empty($modificado) || $modificado != filemtime($path['file']) || true) {
            @unlink($this->fileCached);
            if ($cache) {
                cache('views')->item($file, filemtime($path['file']));
            }
        }
        if (null === static::$instance) {
            static::$instance = $this;
        }
        if ($this->getId() > 1) {
            //echo "Error = No se puede usar view() en views";
            // exit;
        }
        return $this;
    }
    public function getId()
    {
        return $this->id;
    }
    public static function partComponent($component, $attributes) {
      $component->setAttributes($attributes);
      echo $component->render();
    }
    public static function partView($name, $callback)
    {
        if (!isset(static::instance()->partes[$name])) {
            static::instance()->partes[$name] = [];
        }
        static::instance()->partes[$name][] = $callback;
    }
    public static function partViewExists($name)
    {
        return isset(static::instance()->partes[$name]);
    }
    public static function preCoding($section, $code) {
      if(is_null($section)) {
        static::instance()->html_prepend[] = $code;
      } else {
        static::instance()->html_prepend[] = "<?php \Core\Blade::partView('" . $section . "', function(\$params) { extract(\$params); ?>" . $code . "<?php }); ?>";
      }
    }
    public static function partViewCall($name)
    {
        $rp = '';
        if (!isset(static::instance()->partes[$name])) {
            return '';
            return '<!-- no ' . $name . " -->\n";
        }
        foreach (static::instance()->partes[$name] as $r) {
            $imports = static::instance()->getHeredados();
            $rp .= ($r)($imports);
        }
        return $rp;
    }
    public static function path($name)
    {
        $name = str_replace('.', '/', $name);
        $file = app()->getPath() . 'resources/views/' . $name . '.php';
        if (!file_exists($file)) {
            $file = app()->getPath() . 'resources/views/' . $name . '.blade.php';
            if (!file_exists($file)) {
                echo "File not exists = " . $file;
                exit();
            }
        }
        $cache = app()->getPath() . '/cache/views/' . md5($file) . '.php';
        return [
            'file'  => $file,
            'cache' => $cache,
        ];
    }

    public function setHeredados($data)
    {
        $this->heredados = $data ?? [];
        return $this;
    }
    public function getHeredados()
    {
        return $this->heredados;
    }

    public function append($inputs)
    {
        if (!empty($inputs)) {
            $this->inputs = array_merge($this->inputs, $inputs);
        }
        return $this;
    }
    private static function preCompileBasic($html)
    {
        $html = preg_replace_callback("/@(?<type>(include|tablefy))\([\"'](?<name>[^\"']+)[\"'](?:\s*,\s*(?<params>[\w\[\]\$\=\>\'\.\s\á\é\í\ó\ú\Á\É\Í\Ó\Ú\!\,\"\-\_\)\(]+((\)|\]|\]\)))))?\)\n/", function ($res) {
            if ($res['type'] == 'include') {
                $th = (new Blade($res['name'], false));
                $rp = '';
                if (!empty($res['params'])) {
                    $rp = "<!--- vars:" . $res['params'] . " --->\n<?php extract(" . $res['params'] . "); ?>";
                }
                $rp .= $th->precompile();
                return $rp;
            } elseif ($res['type'] == 'tablefy') {
                $tuq = 't' . uniqid();
                static::preCoding('styles', '<link href="/assets/libs/tablefy/tablefy.min.css" rel="stylesheet" type="text/css" />');
                static::preCoding('scripts', '<script>'
                    . "require(['/assets/libs/tablefy/tablefy.min.js?<?= time() ?>'], function() {"
                    . 'var ' . $tuq . " = new Tablefy(<?= json_encode(array_merge(" . $res['params'] . ", [
                        'dom' => '#" . $tuq . "',
                        'request' => array(
                            'url' => '" . route($res['name']) . "',
                            'type' => 'POST',
                            'data' => 'tablefy_filters',
                        ),
                        'enumerate' => false,
                        'selectable' => false,
                        'contextmenu' => true,
                        'draggable' => false,
                        'sorter' => true,
                        'countSelectable' => 5,
                    ])) ?>).init(true);"
                    . "});"
                    . '</script>');
                return '<table id="' . $tuq . '"></table>';
            }
        }, $html);

        $html = preg_replace_callback('/@extends\(\'(?<name>[^\']+)\'\)\s*/', function ($res) {
            static::instance()->html_after[] = (new Blade($res['name'], false))->precompile();
            return '';
        }, $html);

        $html = preg_replace_callback('/@method\(\'(?<name>[^\']+)\'\)\s*/', function ($res) {
            return "<input type=\"hidden\" name=\"_method\" value=\"" . $res['name'] . "\">\n<!-- METHOD: " . $res['name'] . "-->\n";
        }, $html);

        $html = preg_replace_callback('/@section\([\'"](?<name>[^\'"]+)[\'"]\,(\s*)[\'"](?<body>[\s\S]*?)(\s*)[\'"]\)/', function ($res) {
            static::instance()->sections[] = $res['name'];
            static::preCoding($res['name'], static::preCompileBasic($res['body']));
            return '';
        }, $html);

        $html = preg_replace_callback('/@section\([\'"](?<name>[^\'"]+)[\'"]\)(\s*)(?<body>[\s\S]*?)(\s*)@endsection/', function ($res) {
            static::instance()->sections[] = $res['name'];
            static::preCoding($res['name'], static::preCompileBasic($res['body']));
            return '';
        }, $html);

        $html = preg_replace_callback('/@yield\(\'(?<name>[^\']+)\'\)\n*/', function ($res) {
            return  "<?= \Core\Blade::partViewCall('" . $res['name'] . "'); ?>";
        }, $html);

        $html = preg_replace_callback("/@foreach\s*\(\s*(?<for>[\s\&\$\=\,\/\w\\\:\(\)\_\>\-\"\'\[\]]+)\)\n/", function($res) {
            return  "<?php foreach(" . $res['for'] . ") { ?>\n";
        }, $html);

        $html = preg_replace_callback("/@if\s*\(\s*(?<for>[\!\=\,\s\&\$\w\\\:\(\)\_\>\-\"\'\[\]]+)\)\n/m", function ($res) {
            return  "<?php if(" . $res['for'] . ") { ?>\n";
        }, $html);

        $html = preg_replace_callback('/@elseif\s*\(\s*(?<for>[\!\=\,\s\&\$\w\\\:\(\)\_\<\>\-\"\'\[\]]+)\)/', function ($res) {
            return  "<?php } elseif(" . $res['for'] . ") { ?>\n";
        }, $html);

        $html = preg_replace_callback('/@hasSection\(\'(?<name>[^\']+)\'\)/', function ($res) {
            return  "<?php if(\Core\Blade::partViewExists('" . $res['name'] . "')) { ?>";
        }, $html);

        $html = preg_replace_callback('/@end(foreach|if)/m', function ($res) {
            return  "<?php } ?>";
        }, $html);

        $html = preg_replace_callback('/@js\(\'(?<name>[^\']+)\'\)\s*/', function ($res) {
            static::instance()->html_asset[] = $res['name'];
            return '';
        }, $html);

        $html = preg_replace_callback('/@css\(\'(?<name>[^\']+)\'\)\s*/', function ($res) {
            static::instance()->html_asset[] = $res['name'];
            return '';
        }, $html);


        if(!empty(static::instance()->components)) {
          $html = preg_replace_callback('/<nexus:([^\s>]+)([^>]*)>(.*?)<\/nexus:\1>/s', function ($res) {
                $dom     = $res[1];
                $attrs   = $res[2];
                $content = $res[3];

                if(isset(static::instance()->components[$dom])) {
                  $subClass = static::instance()->components[$dom];
                  if(!class_exists($subClass, true)) {
                    kernel()->exception('Component Class no exists: ' . $subClass);
                  }
                  preg_match_all('/\s+([^=]+)="([^"]*)"/', $attrs, $attrMatches, PREG_SET_ORDER);
                  if(!empty($attrMatches)) {
                    $attrMatches = array_map(function($n) {
                      return [
                        'key' => $n[1],
                        'val' => $n[2],
                      ];
                    }, $attrMatches);
                  }
                  $entrada = var_export($attrMatches, true);
                  $entrada = preg_replace('/\'(?<name>\$[\w\_]+)\'/', '$1', $entrada);

                  $uniq = uniqid();

                  $html = "<?php \Core\Blade::\$params['" . $uniq . "'] = new " . $subClass . ";\n";
                  $html .= "if(method_exists(\Core\Blade::\$params['" . $uniq . "'], 'mount')) { \n";
                  $html .= "\Core\Blade::\$params['" . $uniq . "']->mount();\n";
                  $html .= "}\n";
                  $html .= "?>\n";
                  static::instance()->preCoding(null, $html);

                  $html = "<?php \Core\Blade::partComponent(\Core\Blade::\$params['" . $uniq . "'], " . $entrada . "); ?>";
                  return $html;
                } else {
                    return '<!-- DOM-NO-RECONOCIDO -->';
                }
            }, $html);
        }


        return trim($html);
    }
    public function precompile()
    {
        $html = file_get_contents($this->file);
        $html = "\n<!---- pre: " . $this->file . "--->\n" . $html;

        $html = static::preCompileBasic($html);

        if ($this->id === static::instance()->id) {
          static::instance()->html_prepend = array_reverse(static::instance()->html_prepend);
            foreach (static::instance()->html_prepend as $p) {
                $html = $p . $html;
            }
            foreach (static::instance()->html_after as $p) {
                $html .= $p;
            }
            foreach (static::instance()->html_asset as $name => $file) {
                if (str_contains($file, '.js')) {
                    if (in_array('scripts', static::instance()->sections) || true) {
                        $html = "<?php \Core\Blade::partView('scripts', function(\$params) { extract(\$params); ?>"
                            . $this->html_asset_js($file, $name)
                            . '<?php }); ?>' . $html;
                    } else {
                        $html .= $this->html_asset_js($file, $name);
                    }
                } else {
                    if (in_array('styles', static::instance()->sections) || true) {
                        $html = "<?php \Core\Blade::partView('styles', function(\$params) { extract(\$params); ?>"
                            . $this->html_asset_css($file, $name)
                            . '<?php }); ?>' . $html;
                    } else {
                        $html .= $this->html_asset_css($file, $name);
                    }
                }
            }
        }
        static::instance()->html_prepend_end = true;
        $html = "\n<!--- theme: #" . $this->id . "-->\n" . $html . "\n<!--- end theme: #" . $this->id . "-->\n";
        return trim($html);
    }
    private function html_asset_css($file, $name)
    {
        return '<link href="' . $file . '" rel="stylesheet" type="text/css" />';
    }
    private function html_asset_js($file, $name = null)
    {
        return "<script>require('" . $file . "');</script>";
    }
    public function compile()
    {

        $html = $this->precompile();
        $html = str_replace("{{--", "<!--", $html);
        $html = str_replace("--}}", "-->", $html);
        $html = str_replace('{{', '<?=', $html);
        $html = str_replace('}}', '?>', $html);
        $html = str_replace('{!!', '<?=', $html);
        $html = str_replace('!!}', '?>', $html);

        $html = str_replace('@parent', '', $html);
        $html = str_replace('@csrf', '', $html);
        $html = str_replace('@php', '<?php', $html);
        $html = str_replace('@endphp', '?>', $html);
        $html = str_replace('@else', '<?php } else { ?>', $html);
        $html = str_replace('@endif', '<?php } ?>', $html);

        file_put_contents($this->fileCached, $html);
        return $this->html();
    }
    public function html()
    {
        if (is_array($this->inputs) && !empty($this->inputs)) {
            static::instance()->setHeredados($this->inputs);
        }
        $imports = static::instance()->getHeredados();
        if (!empty($imports)) {
            extract($imports);
        }
        try {
            ob_start();
            include($this->fileCached);
            $html = ob_get_clean();
        } catch (\Exception $e) {
            kernel()->exception($e->getMessage(), $e);
        }
        if (!$this->cache) {
            @unlink($this->fileCached);
        }
        return $html;
    }
    public function render()
    {
        $time_start = microtime(true);
        if (file_exists($this->fileCached)) {
            $html = $this->html();
            $diff = microtime(true) - $time_start;
            $sec = intval($diff);
            $micro = $diff - $sec;
            $html  = $html . "\n<!--- Cache: " . date('H:i:s', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro)) . "-->\n";
            return $html;
        } else {
            try {
                $html = $this->compile();
                $diff = microtime(true) - $time_start;
                $sec = intval($diff);
                $micro = $diff - $sec;
                $html  = $html . "\n<!--- Compile: " . date('H:i:s', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro)) . " = " . $this->fileCached . "-->\n";
                return $html;
            } catch (\Throwable $e) {
                $html = static::obtenerRangoLineasArchivo($this->fileCached, $e->getLine() - 5, $e->getLine() + 5);
                kernel()->exception($e->getMessage() . '<br>' . $this->file . "<br><pre>" . htmlspecialchars($html) . '</pre>', $e);

                echo "<h3>File: " . $this->file . ":" . $e->getLine() . "</h3>";
                $html = static::obtenerRangoLineasArchivo($this->fileCached, $e->getLine() - 5, $e->getLine() + 5);
                echo "<pre>";
                echo $html;
                echo "</pre>";
                dd($e);
            }
        }
    }
    public function __toString()
    {
        return $this->render();
    }
    static function obtenerRangoLineasArchivo($archivo, $inicio, $fin)
    {
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
