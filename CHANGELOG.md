# Changelog

All notable changes to this package will be documented here.
Versioning follows [Semantic Versioning](https://semver.org).

---

## [Unreleased]

---

## [0.1.1] — 2026-09-07

### Added
- `CachePolicy` class with `NORMAL`, `NO_STORE`, and `BYPASS` constants and `toWire()` helper
- Optional `$cachePolicy` argument on `translate()` (default `CachePolicy::NORMAL`)
- `$resultCode` field on `TranslationResult` — stable numeric code for programmatic branching
- `$safeError` field on `TranslationResult` — bounded error description; never contains source/translated text
- `isInvalidLanguage()` helper on `TranslationResult` — true when `resultCode === 2`
- `isBusy()` helper on `TranslationResult` — true when `resultCode === 5`; caller may retry
- `getResultCode()` on `TranslatorException` — exposes the stable result code from structured error bodies (413/422/503); returns `-1` for errors without a structured body (401, network errors)
- `getSafeError()` on `TranslatorException` — bounded error description from the service body

### Changed
- `$sourceLang` and `$targetLang` on `TranslationResult` now contain normalized ISO-639-1 codes (`'fr'`, `'en'`) returned by the V2 API, rather than full names
- `TranslatorException` constructor updated to accept `$resultCode` and `$safeError`; existing callers that only use `getHttpCode()` and `getMessage()` are unaffected
- Default `$timeout` raised from 90s to 150s to exceed the service's 120s Ollama inference deadline

### Notes
- The `translate()` signature is backward compatible — existing calls without `$cachePolicy` continue to use `NORMAL` behavior
- The service accepts both normalized codes (`'en'`) and legacy full names (`'English'`) in `$targetLang`; the response always returns normalized codes
- `resultCode` is only available in `TranslatorException` for structured error responses (HTTP 413, 422, 503); auth errors (401) and network failures set it to `-1`

---

## [0.1.0] — 2026-08-01

### Added
- Initial release — `TranslatorClient`, `TranslationResult`, `HealthResult`, `TranslatorException`
- REST API client with zero dependencies (uses built-in `ext-curl`)
- `translate()` with optional request ID correlation
- `health()` endpoint check
- `isCacheHit()`, `isFallback()`, `isSuccess()` helpers on `TranslationResult`
