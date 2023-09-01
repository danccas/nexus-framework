<?php
namespace Core;

class ElegantException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        $html = '
            <div style="background-color: #f8f8f8; border: 1px solid #ddd; padding: 10px;">
                <h3 style="color: #900;">Error:</h3>
                <p style="font-family: Arial, sans-serif;">' . $this->getMessage() . '</p>
                <p style="font-family: Arial, sans-serif; color: #777;">File: ' . $this->getFile() . '</p>
                <p style="font-family: Arial, sans-serif; color: #777;">Line: ' . $this->getLine() . '</p>
                <pre style="background-color: #f0f0f0; padding: 10px; font-family: monospace; color: #900;">' . $this->getTraceAsString() . '</pre>
            </div>
';
			echo $html;
			exit;
        return $html;
    }
}
