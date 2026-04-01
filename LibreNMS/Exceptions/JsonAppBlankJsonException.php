<?php

namespace LibreNMS\Exceptions;

use Throwable;

class JsonAppBlankJsonException extends JsonAppException
{
    /** @param mixed $code */
    public function __construct($message, private $output, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getOutput()
    {
        return $this->output;
    }
}
