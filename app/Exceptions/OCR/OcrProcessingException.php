<?php

declare(strict_types=1);

namespace App\Exceptions\OCR;

use Exception;
use Throwable;

class OcrProcessingException extends Exception
{
    public function __construct($message = 'OCR processing failed', $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
