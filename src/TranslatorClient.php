<?php

declare(strict_types=1);

namespace Neverendless\Translator;

class TranslatorClient
{
    private string $baseUrl;

    /**
     * @param string $baseUrl  Base URL of the translator service, e.g. https://translate.yourdomain.com
     * @param string $token    Service token (from config.yaml service_tokens)
     * @param int    $timeout  Request timeout in seconds (default 15 — allows for AI inference)
     */
    public function __construct(
        string               $baseUrl,
        private readonly string $token,
        private readonly int    $timeout = 15,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Translate a string.
     *
     * @param string      $text       Text to translate
     * @param string      $targetLang Target language (default: English)
     * @param string|null $requestId  Optional correlation ID — echoed back in the result
     *
     * @throws TranslatorException on auth failure, network error, or unexpected response
     */
    public function translate(
        string  $text,
        string  $targetLang = 'English',
        ?string $requestId  = null,
    ): TranslationResult {
        $payload = [
            'text'        => $text,
            'target_lang' => $targetLang,
            'request_id'  => $requestId,
        ];

        $data = $this->post('/translate', $payload);

        return new TranslationResult(
            translation: $data['translation'] ?? $text,
            sourceLang:  $data['source_lang'] ?? '',
            targetLang:  $data['target_lang'] ?? $targetLang,
            original:    $data['original']    ?? $text,
            engine:      $data['engine']      ?? 'none',
            status:      $data['status']      ?? 'fallback',
            requestId:   $data['request_id']  ?? $requestId,
        );
    }

    /**
     * Check service health — useful before deploying or for monitoring.
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

        if ($httpCode >= 400) {
            $message = $data['detail'] ?? $data['error'] ?? $data['message'] ?? "HTTP {$httpCode}";
            throw new TranslatorException((string) $message, $httpCode);
        }

        return $data;
    }
}
