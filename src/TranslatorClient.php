<?php

declare(strict_types=1);

namespace Neverendless\Translator;

class TranslatorClient
{
    private string $baseUrl;

    /**
     * @param string $baseUrl  Base URL of the translator service, e.g. https://translate.yourdomain.com
     * @param string $token    Service token — must be kept server-side, never exposed to browsers
     * @param int    $timeout  Request timeout in seconds.
     *                         Must exceed the service's OLLAMA_TIMEOUT (default 120s) plus network headroom.
     *                         Cache hits return in <100ms; only cold AI misses approach the full timeout.
     */
    public function __construct(
        string                  $baseUrl,
        private readonly string $token,
        private readonly int    $timeout = 150,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Translate a string.
     *
     * @param string      $text        Text to translate
     * @param string      $targetLang  Target language — normalized code ('en', 'fr') or full name ('English').
     *                                 The service always normalizes and returns the code in the result.
     * @param string|null $requestId   Optional correlation ID — echoed back in the result
     * @param int         $cachePolicy One of the CachePolicy constants (default NORMAL).
     *                                 The caller selects based on message type; never forward a
     *                                 player-supplied value as the policy without validation.
     *
     * @throws TranslatorException on auth failure (401), oversized input (413),
     *                             network error, or unexpected response
     */
    public function translate(
        string  $text,
        string  $targetLang  = 'English',
        ?string $requestId   = null,
        int     $cachePolicy = CachePolicy::NORMAL,
    ): TranslationResult {
        $payload = [
            'text'         => $text,
            'target_lang'  => $targetLang,
            'cache_policy' => CachePolicy::toWire($cachePolicy),
            'request_id'   => $requestId,
        ];

        $data = $this->post('/translate', $payload);

        return new TranslationResult(
            translation: $data['translation'] ?? $text,
            sourceLang:  $data['source_lang'] ?? 'unknown',
            targetLang:  $data['target_lang']  ?? $targetLang,
            original:    $data['original']     ?? $text,
            engine:      $data['engine']       ?? 'none',
            status:      $data['status']       ?? 'fallback',
            requestId:   $data['request_id']   ?? $requestId,
            resultCode:  (int) ($data['result_code'] ?? 1),
            safeError:   '',
        );
    }

    /**
     * Check service health — useful for monitoring or before a deployment.
     *
     * @throws TranslatorException on network error
     */
    public function health(): HealthResult
    {
        $data = $this->get('/health');

        return new HealthResult(
            status:  $data['status'] ?? 'degraded',
            redis:   (bool) ($data['redis']  ?? false),
            ollama:  (bool) ($data['ollama'] ?? false),
        );
    }

    // ── HTTP internals ────────────────────────────────────────────────────────

    private function post(string $path, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        return $this->execute($ch);
    }

    private function get(string $path): array
    {
        $ch = curl_init($this->baseUrl . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        return $this->execute($ch);
    }

    private function execute($ch): array
    {
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error) {
            throw new TranslatorException('Network error: ' . $error);
        }

        if ($httpCode === 401) {
            throw new TranslatorException('Unauthorized — check your service token', 401);
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new TranslatorException(
                "Invalid JSON response (HTTP {$httpCode}): " . substr($body, 0, 200),
                $httpCode,
            );
        }

        // 413 TEXT_TOO_LARGE and 422 INVALID_LANGUAGE / bad policy are caller errors.
        // 503 BUSY may be retried. Expose the result_code from the body when available.
        if ($httpCode >= 400) {
            $detail  = $data['detail'] ?? $data['error'] ?? $data['message'] ?? "HTTP {$httpCode}";
            $message = is_array($detail) ? ($detail['message'] ?? json_encode($detail)) : (string) $detail;
            throw new TranslatorException($message, $httpCode);
        }

        return $data;
    }
}
