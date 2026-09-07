<?php

declare(strict_types=1);

namespace Neverendless\Translator;

class TranslationResult
{
    public function __construct(
        public readonly string  $translation,
        public readonly string  $sourceLang,
        public readonly string  $targetLang,
        public readonly string  $original,
        public readonly string  $engine,     // "cache" | "ai" | "none"
        public readonly string  $status,     // "success" | "fallback"
        public readonly ?string $requestId,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isCacheHit(): bool
    {
        return $this->engine === 'cache';
    }

    public function isFallback(): bool
    {
        return $this->status === 'fallback';
    }
}
