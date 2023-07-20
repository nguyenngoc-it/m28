<?php

namespace Gobiz\Support;

use Exception;

class ForwardException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        ($message instanceof Exception)
            ? parent::__construct($message->getMessage(), $message->getCode(), $message)
            : parent::__construct($message, $code, $previous);
    }
}