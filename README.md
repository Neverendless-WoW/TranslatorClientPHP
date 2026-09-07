# Neverendless Translator — PHP Client

PHP client library for the [Neverendless Translator REST API](https://github.com/Neverendless-WoW/TranslatorService).
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
use Neverendless\Translator\CachePolicy;

$translator = new TranslatorClient(
    baseUrl: 'https://translate.yourdomain.com',
    token:   'tok_web_your_service_token',
);

$result = $translator->translate('bonjour tout le monde', 'en');

echo $result->translation;  // "hello everyone"
echo $result->sourceLang;   // "fr"  (normalized ISO code)
echo $result->engine;       // "cache" or "ai"
echo $result->resultCode;   // 0 = SUCCESS
```

---

## Usage

### Translate a string

```php
use Neverendless\Translator\CachePolicy;
use Neverendless\Translator\TranslatorException;

try {
    $result = $translator->translate(
        text:        'hola amigo',
        targetLang:  'en',
        requestId:   'optional-correlation-id',  // echoed back in the result
        cachePolicy: CachePolicy::NORMAL,         // default; omit for normal use
    );

    if ($result->isSuccess()) {
        echo $result->translation;   // translated string
        echo $result->sourceLang;    // e.g. "es" — auto-detected, normalized code
        echo $result->targetLang;    // e.g. "en" — normalized code
        echo $result->engine;        // "cache" or "ai"
    } elseif ($result->isFallback()) {
        // Service degraded — translation contains the original text.
        // Safe to display as-is; do not persist as a translated variant.
        echo $result->translation;
    }
} catch (TranslatorException $e) {
    // HTTP 401: bad token · 413: text too large · 422: unsupported language
    // 503: queue full (retry shortly) · 0: network error
    error_log("Translator error {$e->getHttpCode()}: {$e->getMessage()}");
    echo $originalText;  // safe fallback
}
```

### Cache policies

Select the policy based on the message type — never forward a player-supplied value:

```php
use Neverendless\Translator\CachePolicy;

// Public/global/guild chat — shared cache benefits all players
$result = $translator->translate($chatMessage, 'en', cachePolicy: CachePolicy::NORMAL);

// Private whispers — read cache but do not add to it
$result = $translator->translate($whisperText, 'en', cachePolicy: CachePolicy::NO_STORE);

// GMT sensitive ticket content — skip Redis entirely
$result = $translator->translate($ticketBody, 'en', cachePolicy: CachePolicy::BYPASS);
```

| Constant | Wire | Redis read | Redis write | Use case |
|---|---|---|---|---|
| `CachePolicy::NORMAL` | `normal` | Yes | Yes (AI only) | Public chat |
| `CachePolicy::NO_STORE` | `no_store` | Yes | Never | Private messages |
| `CachePolicy::BYPASS` | `bypass` | No | Never | Sensitive content |

### Language codes

The service accepts both normalized ISO-639-1 codes and full English names for backward compatibility:

```php
$translator->translate($text, 'en');       // normalized code (preferred)
$translator->translate($text, 'English');  // full name — still accepted
```

The response always returns normalized codes in `sourceLang` and `targetLang`.

### Health check

```php
$health = $translator->health();

if (!$health->isHealthy()) {
    error_log("Translator degraded: redis={$health->redis} ollama={$health->ollama}");
}
```

---

## TranslationResult

| Property | Type | Description |
|---|---|---|
| `translation` | `string` | Translated text (original on fallback) |
| `sourceLang` | `string` | Detected source language — normalized code (`'fr'`), `'unknown'`, or `'mixed'` |
| `targetLang` | `string` | Target language — normalized code (`'en'`) |
| `original` | `string` | Original input text |
| `engine` | `string` | `'cache'` · `'ai'` · `'none'` |
| `status` | `string` | `'success'` · `'fallback'` |
| `requestId` | `?string` | Correlation ID echoed from the request |
| `resultCode` | `int` | Stable numeric result code (see below) |
| `safeError` | `string` | Bounded error description when `resultCode > 0` |

**Result codes:**

| Code | Name | Meaning |
|---|---|---|
| `0` | SUCCESS | A real cache or AI translation was produced |
| `1` | FALLBACK | Original text returned; do not persist as translated |
| `2` | INVALID_LANGUAGE | Unsupported target language |
| `3` | INVALID_REQUEST | Malformed request |
| `4` | TEXT_TOO_LARGE | Input exceeds the service size limit |
| `5` | BUSY | Queue full — retry shortly |
| `6` | MODEL_TIMEOUT | Ollama timed out |
| `7` | SERVICE_UNAVAILABLE | Inference service unreachable |
| `8` | INTERNAL_ERROR | Unexpected failure |

**Helper methods:** `isSuccess()` · `isCacheHit()` · `isFallback()` · `isInvalidLanguage()` · `isBusy()`

---

## Timeout

Default is **150 seconds** — must exceed the service's `OLLAMA_TIMEOUT` (default 120s) plus network headroom. Cache hits return in ~2–50ms regardless of timeout. For rendering-critical paths that cannot wait for AI inference, catch `TranslatorException` and display the original text:

```php
$translator = new TranslatorClient(
    baseUrl: 'https://translate.yourdomain.com',
    token:   $token,
    timeout: 150,
);
```

---

## Service Setup

This package is a client only. The translation service is a self-hosted Python microservice:
[github.com/Neverendless-WoW/TranslatorService](https://github.com/Neverendless-WoW/TranslatorService)

Tokens are configured in the service's environment. Never place tokens in browser JavaScript.

---

## Releases

See [CHANGELOG](CHANGELOG.md) or the [GitHub releases page](https://github.com/Neverendless-WoW/TranslatorClientPHP/releases).

Versioning follows [Semantic Versioning](https://semver.org).
