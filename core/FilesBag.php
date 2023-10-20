<?php
namespace Core;
use Core\FileRequest;

class FilesBag implements \IteratorAggregate, \Countable
{
    protected $files;

    /**
     * Constructor.
     *
     * @param array $files An array of HTTP files
     */
    public function __construct(array $files = [])
    {
        $this->files = [];
        foreach ($files as $key => $values) {
          if(is_array($values['name'])) {
            $list = [];
            $total = count($values['name']);
            for( $i=0 ; $i < $total ; $i++ ) {
              $primero = array_map(function($n) use($i) {
                return $n[$i];
              }, $values);
              $list[] = $primero;
            }
            $this->set($key, new FileRequest($list));
          } else {
            $this->set($key, new FileRequest([$values]));
          }
        }
    }

    /**
     * Sets a header by name.
     *
     * @param string          $key     The key
     * @param string|string[] $values  The value or an array of values
     * @param bool            $replace Whether to replace the actual value or not (true by default)
     */
    public function set($key, $values, $replace = true)
    {
      $key = strtr(strtolower($key), '_', '-');
        $this->files[$key] = $values;
    }

    /**
     * Gets a header value by name.
     *
     * @param string          $key     The key
     * @param string|string[] $default The default value
     * @param bool            $first   Whether to return the first value or all header values
     *
     * @return string|string[] The first header value if $first is true, an array of values otherwise
     */
    public function get($key, $default = '')
    {
        $key = strtr(strtolower($key), '_', '-');

        if (!isset($this->files[$key])) {
            return null;
        }
        return $this->files[$key];
    }
    public function __set($key, $values) {
      return $this->set($key, $values);
    }
    public function __get($key) {
      return $this->get($key);
    }

    // Métodos adicionales para contar, verificar, eliminar y acceder a los encabezados...

    /**
     * @see \IteratorAggregate::getIterator()
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->files);
    }

    /**
     * @see \Countable::count()
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->files);
    }
}
