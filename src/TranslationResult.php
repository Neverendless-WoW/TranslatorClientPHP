<?php

declare(strict_types=1);

namespace Neverendless\Translator;

/**
 * The result of a translate() call.
 *
 * Source and target language fields contain normalized ISO-639-1 codes
 * (e.g. 'fr', 'en') rather than full names. The 'unknown' sentinel is
 * returned when the source language could not be reliably detected.
 */
class TranslationResult
{
    public function __construct(
        /** Translated text (or original text on fallback). */
        public readonly string  $translation,

        /** Detected source language as a normalized code (e.g. 'fr'), 'unknown', or 'mixed'. */
        public readonly string  $sourceLang,

        /** Requested target language as a normalized code (e.g. 'en'). */
        public readonly string  $targetLang,

        /** Original untranslated text. */
        public readonly string  $original,

        /** Which engine served the result: "cache" | "ai" | "none". */
        public readonly string  $engine,

        /** High-level outcome: "success" | "fallback". */
        public readonly string  $status,

        /** Correlation ID echoed from the request (if provided). */
        public readonly ?string $requestId,

        /**
         * Stable numeric result code. One of:
         *   0 SUCCESS             — a real cache or AI translation
         *   1 FALLBACK            — original text returned; not a translation
         *   2 INVALID_LANGUAGE    — unsupported target language
         *   3 INVALID_REQUEST     — malformed request
         *   4 TEXT_TOO_LARGE      — input exceeds configured size limit
         *   5 BUSY                — queue full; retry shortly
         *   6 MODEL_TIMEOUT       — Ollama did not respond in time
         *   7 SERVICE_UNAVAILABLE — inference service unreachable
         *   8 INTERNAL_ERROR      — unexpected failure
         */
        public readonly int     $resultCode,

        /**
         * Safe, bounded error description when resultCode > 0.
         * Never contains source or translated text.
         */
        public readonly string  $safeError,
    ) {}

    /** True only when a real translation was produced (result_code === 0). */
    public function isSuccess(): bool
    {
        return $this->resultCode === 0;
    }

    /** True when the result came from Redis cache. */
    public function isCacheHit(): bool
    {
        return $this->engine === 'cache';
    }

    /** True when the service returned the original text unchanged. */
    public function isFallback(): bool
    {
        return $this->status === 'fallback';
    }

    /** True when the request was rejected due to an unsupported target language. */
    public function isInvalidLanguage(): bool
    {
        return $this->resultCode === 2;
    }

    /** True when the service queue was full; the caller may retry. */
    public function isBusy(): bool
    {
        return $this->resultCode === 5;
    }
}
