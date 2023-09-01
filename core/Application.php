<?php

namespace Core;

use Core\Kernel;
use Core\Route;
use Dotenv\Dotenv;

class Application
{

    const VERSION = '1.0.0';
    protected $basePath;
    private $can_register_route = false;
    private $can_register_configs = false;
    private $kernel = null;
    private $attrs = [];
    private static $instance;
    protected $current_config_route = null;
    protected $config = [];

    public static function instance($container = null)
    {
        if (null === static::$instance) {
            exit('Sin App');
        }
        if ($container === null) {
            return static::$instance;
        }
        exit('No especificaco en APP');
    }
    public function __construct($basePath = null)
    {
        if (null === static::$instance) {
            static::$instance = $this;
        }

        $this->basePath = $basePath;
        #require_once $this->basePath . 'core/misc.php';
        require_once __DIR__ . '/misc.php';
        $this->initializeKernel();
        $this->registerConfiguresAvailable();
        $this->registerAliasesAvailable();
        $this->registerRoutesAvailable();
        return $this;
    }
    public function version()
    {
        return static::VERSION;
    }
    public function initializeKernel()
    {
        $this->kernel = new Kernel;
    }
    public function kernel()
    {
        return $this->kernel;
    }
    public function canRegisterRoute()
    {
        return $this->can_register_route;
    }
    public function registerURI($uri)
    {
        $this->kernel->setURI($uri);
        return $this;
    }
    public function registerConfiguresAvailable()
    {
        $this->can_register_configs = true;
        $dotenv = Dotenv::createUnsafeImmutable($this->basePath);
        $dotenv->safeLoad();

        $files_apps = ['app'];
        foreach ($files_apps as $a) {
            $url = $this->basePath . 'config/' . $a . '.php';
            if (file_exists($url)) {
                $this->config[$a] = (function () use ($url) {
                    return require_once($url);
                })();
            }
        }
        $this->can_register_configs = false;
    }
    public function registerAliasesAvailable()
    {
        $ce = $this;
        spl_autoload_register(function ($class) use (&$ce) {
            foreach ($ce->config as $config) {
                foreach ($config['aliases'] as $key => $val) {
                    if ($class == $key) {
                        $file = $this->basePath . lcfirst(str_replace('\\', '/', $val)) . '.php';
                        if (file_exists($file)) {
                            class_alias($val, $key, true);
                        } else {
                            echo "FF = " . $file;
                            exit;
                        }
                    }
                }
            }
        });
    }
    public function currentConfigRoute()
    {
        return $this->current_config_route;
    }
    public function registerRouteFile($filename)
    {
        if (file_exists($filename)) {
            $this->current_config_route = $filename;
            require_once $filename;
        }
    }
    public function registerRoutesAvailable()
    {
        $this->can_register_route = true;
        foreach (glob($this->basePath . 'routes/*.php') as $filename) {
            $this->registerRouteFile($filename);
        }
        $this->can_register_route = false;
    }
    public function library($name)
    {
        return true;
    }
    public function attr($key, $value = null)
    {
        if ($value === null) {
            return $this->attrs[$key];
        }
        return $this->attrs[$key] = $value;
    }
    public function getPath()
    {
        return $this->basePath;
    }
}
