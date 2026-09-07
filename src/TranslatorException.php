<?php

declare(strict_types=1);

namespace Neverendless\Translator;

/**
 * Thrown when the translator service returns an error or is unreachable.
 *
 * For structured service errors (HTTP 413, 422, 503) the service body includes
 * a stable result_code — use getResultCode() to branch on it instead of the
 * HTTP status, which may vary across proxy layers.
 */
class TranslatorException extends \RuntimeException
{
    /**
     * @param string $message    Human-readable error description
     * @param int    $httpCode   HTTP status code (0 for network/curl errors)
     * @param int    $resultCode Stable Translator result code (-1 if not available)
     * @param string $safeError  Bounded safe error string from the service body
     */
    public function __construct(
        string               $message,
        private readonly int    $httpCode   = 0,
        private readonly int    $resultCode = -1,
        private readonly string $safeError  = '',
    ) {
        parent::__construct($message);
    }

    /** HTTP status code, or 0 for network errors. */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Stable Translator result code from the service response body, or -1 if
     * the error did not include a structured body (e.g. 401, network failure).
     *
     * Known values: 2=INVALID_LANGUAGE, 3=INVALID_REQUEST, 4=TEXT_TOO_LARGE,
     * 5=BUSY, 6=MODEL_TIMEOUT, 7=SERVICE_UNAVAILABLE, 8=INTERNAL_ERROR.
     */
    public function getResultCode(): int
    {
        return $this->resultCode;
    }

    /**
     * Safe, bounded error description from the service body when available.
     * Never contains source or translated text.
     */
    public function getSafeError(): string
    {
        return $this->safeError;
    }
}
