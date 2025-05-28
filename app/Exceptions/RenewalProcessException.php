<?php

namespace App\Exceptions;

use Exception;

class RenewalProcessException extends Exception
{
    protected $step;
    protected $errors;

    public function __construct($message, $step = null, $errors = [], $code = 0, $previous = null)
    {
        $this->step = $step;
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getStep()
    {
        return $this->step;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
