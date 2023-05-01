<?php

namespace Core;

use Exception;

class Blade
{
    private $id;
    private $cache;
    protected $file;
    protected $fileCached;
    protected $inputs;
    protected $heredados = [];
    
    public static $indexes = 0;
    private static $instance;
    public $partes = [];
    protected $sections = [];
    protected $html_asset = [];
    protected $html_prepend = [];
    protected $html_after   = [];

    public static function instance($view_name = null)
    {
        if (null === static::$instance) {
            return static::$instance = new Blade($view_name, true);
        }
        return static::$instance;
    }
    public function __construct($file, $cache = true)
    {
        $path = static::path($file);
        $this->id = ++static::$indexes;
        $this->cache = $cache;
        $this->file = $path['file'];
        $this->fileCached = $path['cache'];

        $modificado = cache('views')->item($file);
        if(empty($modificado) || $modificado != filemtime($path['file'])) {
            @unlink($this->fileCached);
            if($cache) {
                cache('views')->item($file, filemtime($path['file']));
            }
        }
        if (null === static::$instance) {
            static::$instance = $this;
        }
    }
    public function getId() {
        return $this->id;
    }
    public static function partView($name, $callback) {
        if(!isset(static::instance()->partes[$name])) {
            static::instance()->partes[$name] = [];
        }
        static::instance()->partes[$name][] = $callback;
    }
    public static function partViewExists($name) {
        return isset(static::instance()->partes[$name]);
    }
    public static function partViewCall($name) {
        $rp = '';
        if(!isset(static::instance()->partes[$name])) {
            return '<!-- no ' . $name . ' -->';
        }
        foreach(static::instance()->partes[$name] as $r) {
            $imports = static::instance()->getHeredados();
            $rp .= ($r)($imports);
        }
        return $rp;
    }
    public static function path($name) {
        $name = str_replace('.', '/', $name);
        $file = app()->getPath() . 'resources/views/' . $name . '.php';
        $cache = app()->getPath() . '/cache/views/' . md5($file) . '.php';
        if(!file_exists($file)) {
            echo "File not exists = " . $file;
            exit();
        }
        return [
            'file'  => $file,
            'cache' => $cache,
        ];
    }
    
    public function setHeredados($data) {
        $this->heredados = $data ?? [];
        return $this;
    }
    public function getHeredados() {
        return $this->heredados;
    }
    
    public function append($inputs)
    {
        $this->inputs = $inputs;
        return $this;
    }
    private static function preCompileBasic($html) {
        $html = preg_replace_callback("/@(?<type>(include|tablefy))\([\"'](?<name>[^\"']+)[\"'](?:\s*,\s*(?<params>[^\)]+(\)?)))?\)/", function($res) {
            if($res['type'] == 'include') {
                $th = (new Blade($res['name'], false));
                $rp = '';
                if(!empty($res['params'])) {
                    $rp = "<!--- vars:" . $res['params'] . " ---><?php extract(" . $res['params'] . "); ?>";
                }
                $rp .= $th->precompile();
                return $rp;
            } elseif($res['type'] == 'tablefy') {
                //dd( serialize($res['params']));
                //exit();
                $tuq = 't' . uniqid();
                static::instance()->html_prepend[] = "<?php \Core\Blade::partView('styles', function(\$params) {?>"
                    . '<link href="/assets/css/tablefy.css" rel="stylesheet" type="text/css" /><?php }); ?>';
                static::instance()->html_prepend[] = "<?php \Core\Blade::partView('scripts', function(\$params) {?>"
                    . '<script>'
                    . "require(['/assets/js/tablefy2.js?<?= time() ?>'], function() {"
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
                    . '</script><?php }); ?>';
                return '<table id="' . $tuq . '"></table>';
            }
        }, $html);

        $html = preg_replace_callback('/@extends\(\'(?<name>[^\']+)\'\)\s*/', function($res) {
            static::instance()->html_after[] = (new Blade($res['name'], false))->precompile();
            return '';
        }, $html);

        $html = preg_replace_callback('/@method\(\'(?<name>[^\']+)\'\)\s*/', function($res) {
            return "<!-- METHOD: " . $res['name'] . "-->";
        }, $html);

        $html = preg_replace_callback('/@section\([\'"](?<name>[^\'"]+)[\'"]\)(\s*)(?<body>[\s\S]*?)(\s*)@endsection/', function($res) {
            static::instance()->sections[] = $res['name'];
            static::instance()->html_prepend[] = "<?php \Core\Blade::partView('" . $res['name'] . "', function(\$params) { extract(\$params); ?>"
                . static::preCompileBasic($res['body'])
                . '<?php }); ?>';
            return '';
        }, $html);

        $html = preg_replace_callback('/@yield\(\'(?<name>[^\']+)\'\)\s*/', function($res){
            return  "<?= \Core\Blade::partViewCall('" . $res['name'] . "'); ?>";
        }, $html);

        $html = preg_replace_callback('/@foreach\s*\(\s*(?<for>[^\)]+)\)/', function($res) {
            return  "<?php foreach(" . $res['for'] . ") { ?>";
        }, $html);

        $html = preg_replace_callback('/@if\s*\(\s*(?<for>[^\)]+)\)/', function($res) {
            return  "<?php if(" . $res['for'] . ") { ?>";
        }, $html);

        $html = preg_replace_callback('/@end(foreach|if)/m', function($res) {
            return  "<?php } ?>";
        }, $html);

        $html = preg_replace_callback('/@js\(\'(?<name>[^\']+)\'\)\s*/', function($res) {
            static::instance()->html_asset[] = $res['name'];
            return '';
        }, $html);

        $html = preg_replace_callback('/@css\(\'(?<name>[^\']+)\'\)\s*/', function($res) {
            static::instance()->html_asset[] = $res['name'];
            return '';
        }, $html);

        return trim($html);
    }
    public function precompile() {
        $html = file_get_contents($this->file);
        $html = "<!---- pre: " . $this->file . "--->\n" . $html;
        
        $html = static::preCompileBasic($html);

        if($this->id === static::instance()->id) {
            foreach(static::instance()->html_prepend as $p) {
                $html = $p . $html;
            }
            foreach(static::instance()->html_after as $p) {
                $html .= $p;
            }
            if(in_array('scripts', static::instance()->sections)) {
                foreach(static::instance()->html_asset as $file) {
                    $html = "<?php \Core\Blade::partView('scripts', function(\$params) { extract(\$params); ?>"
                        . "<script>require('" . $file . "');</script>"
                        . '<?php }); ?>' . $html;
                    }
            } else {
                foreach(static::instance()->html_asset as $file) {
                    $html .= "<script>require('" . $file . "');</script>";
                }
            }
        }
        $html = "<!--- theme: #" . $this->id . "-->" . $html . "<!--- end theme: #" . $this->id . "-->";
        return $html;
    }
    public function compile()
    {
        $html = $this->precompile();
        $html = str_replace('{{', '<?=', $html);
        $html = str_replace('}}', '?>', $html);

        $html = str_replace('@php', '<?php', $html);
        $html = str_replace('@endphp', '?>', $html);
        file_put_contents($this->fileCached, $html);
        return $this->html();
    }
    public function html()
    {
        if (is_array($this->inputs) && !empty($this->inputs)) {
            static::instance()->setHeredados($this->inputs);
        }
        $imports = static::instance()->getHeredados();
        if(!empty($imports)) {
            extract($imports);
        }
        ob_start();
        include($this->fileCached);
        $html = ob_get_clean();
        if(!$this->cache) {
            @unlink($this->fileCached);
        }
        return $html;
    }
    public function render() {
        $time_start = microtime(true);
        if (file_exists($this->fileCached)) {
            $html = $this->html();
            $diff = microtime(true) - $time_start;
            $sec = intval($diff);
            $micro = $diff - $sec;
            $html  = $html . "\n<!--- Cache: " . date('H:i:s', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro)) . "-->";
            return $html;
        } else {
            try {
                $html = $this->compile();
                $diff = microtime(true) - $time_start;
                $sec = intval($diff);
                $micro = $diff - $sec;
                $html  = $html . "\n<!--- Compile: " . date('H:i:s', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro)) . "-->";
                return $html;
            } catch(\Throwable $e) {
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
    static function obtenerRangoLineasArchivo($archivo, $inicio, $fin) {
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
