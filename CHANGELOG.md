# Changelog

All notable changes to this plugin are documented in this file. This project follows Keep a Changelog and adheres to Semantic Versioning.

## [Unreleased]

- Unify generation prompt logging for AJAX and synchronous flows.
- Add pre-filled generation form defaults for easier testing.
- Add admin notice prompting configuration of provider API keys when missing.
- Add generation report post-meta (`_acs_generation_report`) and exportable generation logs.
- Add log rotation, log levels (info/warn/error/debug), and CSV/JSON export support for logs.
- Add lightweight PHPUnit tests and `phpcs.xml` ruleset; CI workflow for linting and tests.

## [1.0.0] - 2025-11-23

- Initial public release: multi-provider AI content generation (Groq, OpenAI, Anthropic).
- Admin UI for content generation, provider configuration, and settings.
- SEO features: meta descriptions, focus keyword optimization, internal linking suggestions.
- Security: encrypted key storage, capability checks, input sanitization.

## [Previous]

- Scaffolding and early development commits.
# Changelog

All notable changes to this plugin will be documented in this file.

## [Unreleased]
- Added auto-fix heuristics for generated content (meta truncate, title prefix, first paragraph insertion).
- Added structured generation reports persisted to post meta (`_acs_generation_report`).
- Implemented file-based logging (`logs/generation.log`) and recent-history option (`acs_generation_history`).
- Added Generation Logs admin page with filtering and export (CSV/JSON).
- Added log rotation when log file exceeds configurable size (default 2MB).
- Added log levels (info, warn, error, debug) and export filtering.
- Added lightweight PHPUnit tests and testing bootstrap for isolated class tests.
- Added `phpcs.xml` project ruleset and CI workflow for test + lint.

## [Previous]
- Initial scaffolding and core features.
