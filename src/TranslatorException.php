<?php

declare(strict_types=1);

namespace Neverendless\Translator;

class TranslatorException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $httpCode = 0)
    {
        parent::__construct($message);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
