# Changelog

All notable changes to this package will be documented here.
Versioning follows [Semantic Versioning](https://semver.org).

---

## [Unreleased]

### Added
- Initial release — `TranslatorClient`, `TranslationResult`, `HealthResult`, `TranslatorException`
- REST API client with zero dependencies (uses built-in `ext-curl`)
- `translate()` with optional request ID correlation
- `health()` endpoint check
- `isCacheHit()`, `isFallback()`, `isSuccess()` helpers on result objects
