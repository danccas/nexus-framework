<?php
namespace Core;

class HeaderBag implements \IteratorAggregate, \Countable
{
    protected $headers;

    /**
     * Constructor.
     *
     * @param array $headers An array of HTTP headers
     */
    public function __construct(array $headers = [])
    {
        $this->headers = [];
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
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

        $values = array_values((array) $values);

        if (true === $replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }
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
    public function get($key, $default = '', $first = true)
    {
        $key = strtr(strtolower($key), '_', '-');

        if (!isset($this->headers[$key])) {
            if ($first) {
                return $default;
            }

            return [];
        }

        if ($first) {
            return count($this->headers[$key]) > 0 ? $this->headers[$key][0] : $default;
        }

        return $this->headers[$key];
    }

    // Métodos adicionales para contar, verificar, eliminar y acceder a los encabezados...

    /**
     * @see \IteratorAggregate::getIterator()
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }

    /**
     * @see \Countable::count()
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->headers);
    }
}
