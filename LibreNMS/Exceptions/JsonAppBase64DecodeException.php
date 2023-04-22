<?php

namespace LibreNMS\Exceptions;

use Throwable;

class JsonAppBase64DecodeException extends JsonAppException
{
    /**
     * @var string
     */
    private $output;

    /**
     * @param  string  $message  The message.
     * @param  string  $output  The return from snmpget.
     * @param  int  $code  Error code.
     * @return static
     */
    public function __construct(string $message, string $output, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->output = $output;
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
