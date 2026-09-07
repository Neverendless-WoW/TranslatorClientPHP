<?php

declare(strict_types=1);

namespace Neverendless\Translator;

class HealthResult
{
    public function __construct(
        public readonly string $status,   // "ok" | "degraded"
        public readonly bool   $redis,
        public readonly bool   $ollama,
    ) {}

    public function isHealthy(): bool
    {
        return $this->status === 'ok';
    }
}
