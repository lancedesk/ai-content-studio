# Changelog

All notable changes to this plugin are documented in this file. This project follows Keep a Changelog and adheres to Semantic Versioning.

## [1.0.1] - 2024-12-04

### Added

#### Admin UI Redesign
- **Unified Admin Interface**: Complete redesign of admin pages with modern, consistent styling
- **Responsive Design**: Mobile-first responsive layouts for all admin pages
- **Accessibility**: WCAG 2.1 AA compliant with proper ARIA labels and keyboard navigation
- **Dark Mode**: Support for WordPress admin color schemes

#### Analytics & Reporting (Task 11)
- **Analytics Dashboard**: New dashboard with comprehensive metrics and visualizations
- **Chart.js Integration**: Interactive charts for generation trends, provider usage, and costs
- **Date Range Filters**: Quick presets (Today, Week, Month, Year) plus custom date ranges
- **Data Export**: Export analytics to CSV or JSON format
- **REST API Endpoints**: Full REST API for analytics data (`/acs/v1/analytics/*`)

#### Advanced Settings Interface (Task 12)
- **Modern Toggle Switches**: iOS-style toggle switches replacing checkboxes
- **Settings Import/Export**: Export all settings as JSON, import from backup
- **Reset to Defaults**: One-click reset with confirmation dialog
- **Inline Validation**: Real-time validation feedback for all form fields

#### JavaScript Enhancements (Task 13)
- **Keyboard Shortcuts**: Power user shortcuts (Ctrl+Shift+G/D/A/S, ? for help)
- **Smooth Animations**: Micro-interactions and transitions throughout the UI
- **Toast Notifications**: Non-intrusive feedback for actions
- **Loading States**: Skeleton loaders and progress indicators

#### Error Handling & User Feedback (Task 14)
- **Centralized Error Handler**: `ACS_Error_Handler` class with error codes, severity levels, and retry logic
- **Client-Side Error Handler**: JavaScript module for AJAX error handling with user-friendly messages
- **Form Validation**: Real-time validation with character counters and API key testing
- **Retry Mechanism**: Automatic and manual retry options with exponential backoff
- **Error Logging**: Database table for error tracking and analysis

#### Performance Optimization (Task 15)
- **Caching Layer**: `ACS_Performance` class with WordPress transients
- **Lazy Loading**: IntersectionObserver-based lazy loading for images, charts, and data
- **Conditional Asset Loading**: Chart.js only loads on analytics page
- **Query Optimization**: Batched queries and indexed database columns
- **Dashboard Caching**: 5-minute cache for dashboard metrics

### Changed
- Logger now defaults to `wp_upload_dir()/acs-logs` instead of plugin directory
- Analytics helper methods added to `ACS_Analytics` class
- Settings AJAX handlers now include proper nonce verification
- Admin pages use unified CSS with CSS custom properties

### Fixed
- PHP 8.3 compatibility improvements
- Proper escaping in all admin templates
- Memory usage optimization for large datasets

### Documentation
- Added `docs/USER_GUIDE.md` - Comprehensive user documentation
- Added `docs/DEVELOPER_GUIDE.md` - Technical developer documentation
- Updated `planning/IMPLEMENTATION_CHECKLIST.md` with completed tasks

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
