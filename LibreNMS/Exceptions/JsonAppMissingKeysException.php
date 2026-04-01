<?php

namespace LibreNMS\Exceptions;

use Throwable;

class JsonAppMissingKeysException extends JsonAppException
{
    /** @param mixed $code */
    public function __construct($message, private $output, private $parsed_json = [], $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /** @return mixed */
    public function getOutput()
    {
        return $this->output;
    }

    /** @return mixed */
    public function getParsedJson()
    {
        return $this->parsed_json;
    }
}
