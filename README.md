# Neverendless Translator — PHP Client

PHP client library for the [Neverendless Translator REST API](https://github.com/YOUR_ORG/translator).
Translate text via a self-hosted AI translation service with Redis caching.

Zero dependencies — uses PHP's built-in `curl` extension only.

**Requires:** PHP 8.1+ · `ext-curl`

---

## Installation

```bash
composer require neverendless/translator-client
```

---

## Quick Start

```php
use Neverendless\Translator\TranslatorClient;

$translator = new TranslatorClient(
    baseUrl: 'https://translate.yourdomain.com',
    token:   'tok_web_your_service_token',
);

$result = $translator->translate('bonjour tout le monde', 'English');

echo $result->translation;  // "hello everyone"
echo $result->sourceLang;   // "French"
echo $result->engine;       // "cache" or "ai"
```

---

## Usage

### Translate a string

```php
$result = $translator->translate(
    text:       'hola amigo',
    targetLang: 'English',
    requestId:  'optional-correlation-id',  // echoed back in the result
);

if ($result->isFallback()) {
    // Service degraded — $result->translation contains the original text
    // Safe to display, just not translated
}

if ($result->isCacheHit()) {
    // Returned instantly from cache (~2ms), no AI inference used
}
```

### Health check

```php
$health = $translator->health();

if (!$health->isHealthy()) {
    // $health->redis  — bool
    // $health->ollama — bool
    error_log("Translator degraded: redis={$health->redis} ollama={$health->ollama}");
}
```

### Error handling

```php
use Neverendless\Translator\TranslatorException;

try {
    $result = $translator->translate($text, 'English');
} catch (TranslatorException $e) {
    // $e->getMessage()  — human-readable message
    // $e->getHttpCode() — HTTP status (0 = network/curl error, 401 = bad token)
    $displayText = $text; // fall back to original in your UI
}
```

---

## TranslationResult

| Property | Type | Description |
|---|---|---|
| `translation` | `string` | Translated text (original on fallback) |
| `sourceLang` | `string` | Detected source language |
| `targetLang` | `string` | Requested target language |
| `original` | `string` | Original input text |
| `engine` | `string` | `cache` / `ai` / `none` |
| `status` | `string` | `success` / `fallback` |
| `requestId` | `?string` | Your correlation ID echoed back |

Helper methods: `isSuccess()` · `isCacheHit()` · `isFallback()`

---

## Timeout

Default is **15 seconds** — allows for AI inference time on cache misses. For rendering-critical paths, set a shorter timeout and handle fallback:

```php
$translator = new TranslatorClient(
    baseUrl: 'https://translate.yourdomain.com',
    token:   $token,
    timeout: 5,
);
```

Cache hits always return in ~2–50ms regardless of timeout setting.

---

## Service Setup

This package is a client only. The translation service it connects to is a self-hosted Python microservice:
[github.com/YOUR_ORG/translator](https://github.com/YOUR_ORG/translator)

Service tokens are configured in the service's `config.yaml`. Request one from whoever manages the deployment.

---

## Releases

See [CHANGELOG](CHANGELOG.md) or the [GitHub releases page](https://github.com/YOUR_ORG/TranslatorClientPHP/releases).

Versioning follows [Semantic Versioning](https://semver.org).
