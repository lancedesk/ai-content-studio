=== AI Content Studio ===
Contributors: lancedesk
Tags: ai, content, seo, groq, openai, anthropic, generator
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

AI Content Studio generates SEO-optimized content using multiple AI providers (Groq, OpenAI, Anthropic). It offers an admin UI for provider configuration, generation templates, and built-in SEO post-processing (meta descriptions, focus keywords, internal linking suggestions).

== Installation ==

1. Upload the `ai-content-studio` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to `AI Content Studio → Settings` and configure one or more provider API keys.

== Frequently Asked Questions ==

= How do I set my API key? =
Go to `AI Content Studio → Settings` and paste the API key for your provider under AI Providers. Administrators will see a notice if keys are not configured.

= Is this plugin safe to use on production? =
Yes. The plugin includes input sanitization, capability checks, and encrypted storage for API keys. Review the Settings → Advanced logging options before enabling detailed logs.

== Screenshots ==

1. Settings page with provider configuration and Test Connection buttons.
2. Generate Content UI with templates and keyword suggestions.
3. Generation Logs view with export options.

== Changelog ==

= 1.0.0 =
* Release Date: 2025-11-23
* Initial public release with multi-provider AI support, admin UI, SEO features, and security framework.

= Unreleased =
* Unify generation logging across flows.
* Add generation report post-meta and log export features.
* Add CI linting and tests.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Arbitrary section ==

If you have documentation, FAQs or additional notes, include them in this or separate sections.
