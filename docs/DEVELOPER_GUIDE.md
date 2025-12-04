# AI Content Studio - Developer Documentation

> Technical documentation for developers extending or integrating with AI Content Studio

**Version:** 1.0.1  
**Last Updated:** December 2024

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Plugin Structure](#plugin-structure)
3. [Hooks and Filters](#hooks-and-filters)
4. [REST API Reference](#rest-api-reference)
5. [AJAX Endpoints](#ajax-endpoints)
6. [Database Schema](#database-schema)
7. [JavaScript API](#javascript-api)
8. [Extending the Plugin](#extending-the-plugin)
9. [Security Considerations](#security-considerations)
10. [Testing](#testing)

---

## Architecture Overview

AI Content Studio follows WordPress best practices with a modular architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                     WordPress Core                          │
├─────────────────────────────────────────────────────────────┤
│                    AI Content Studio                        │
│  ┌──────────────┬──────────────┬──────────────────────────┐ │
│  │   Admin UI   │  REST API    │    Content Generation    │ │
│  │  (Unified)   │  Endpoints   │       Pipeline           │ │
│  └──────┬───────┴──────┬───────┴───────────┬──────────────┘ │
│         │              │                   │                │
│  ┌──────▼───────┬──────▼───────┬───────────▼──────────────┐ │
│  │   Settings   │  Analytics   │      AI Providers        │ │
│  │   Manager    │   Tracker    │   (Groq/OpenAI/Claude)   │ │
│  └──────────────┴──────────────┴──────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Security Layer: Encryption, Validation, Sanitization   │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Core Components

| Component | Class | Purpose |
|-----------|-------|---------|
| Core | `ACS_Core` | Plugin initialization, loading |
| Admin | `ACS_Unified_Admin` | Admin interface, menus |
| Settings | `ACS_Settings` | Options management, AJAX handlers |
| Generator | `ACS_Content_Generator` | Content generation pipeline |
| Analytics | `ACS_Analytics` | Usage tracking, metrics |
| Error Handler | `ACS_Error_Handler` | Centralized error management |
| Performance | `ACS_Performance` | Caching, optimization |

---

## Plugin Structure

```
ai-content-studio/
├── ai-content-studio.php        # Main plugin file
├── includes/
│   ├── class-acs-core.php       # Core initialization
│   ├── class-acs-activator.php  # Activation hooks
│   ├── class-acs-deactivator.php
│   ├── class-acs-analytics.php  # Analytics tracking
│   ├── class-acs-error-handler.php # Error management
│   ├── class-acs-performance.php   # Caching layer
│   ├── class-acs-logger.php     # Logging system
│   └── rest/
│       └── class-acs-rest.php   # REST API endpoints
├── admin/
│   ├── class-acs-admin.php      # Admin base class
│   ├── class-acs-unified-admin.php # Unified admin interface
│   ├── class-acs-settings.php   # Settings & AJAX
│   ├── templates/               # Admin page templates
│   │   ├── dashboard.php
│   │   ├── analytics-dashboard.php
│   │   ├── generate.php
│   │   └── settings.php
│   ├── css/
│   │   └── unified-admin.css    # Admin styles
│   └── js/
│       ├── acs-admin.js         # Main admin JS
│       └── modules/
│           ├── acs-error-handler.js
│           ├── acs-form-validation.js
│           ├── acs-lazy-load.js
│           └── acs-interactions.js
├── api/
│   ├── class-acs-ai-provider.php # Provider interface
│   └── providers/
│       ├── class-acs-groq.php
│       ├── class-acs-openai.php
│       └── class-acs-anthropic.php
├── generators/
│   └── class-acs-content-generator.php
├── seo/
│   └── [SEO optimization classes]
├── security/
│   ├── class-acs-encryption.php
│   ├── class-acs-sanitizer.php
│   └── class-acs-validator.php
└── tests/
    ├── bootstrap.php
    └── [Test classes]
```

---

## Hooks and Filters

### Actions

#### `acs_before_generate_content`
Fires before content generation begins.

```php
do_action( 'acs_before_generate_content', $params );

// Usage:
add_action( 'acs_before_generate_content', function( $params ) {
    // Log generation attempt
    error_log( 'Generating content for: ' . $params['topic'] );
}, 10, 1 );
```

#### `acs_after_content_generation`
Fires after content is successfully generated.

```php
do_action( 'acs_after_content_generation', $content, $params, $provider );

// Usage:
add_action( 'acs_after_content_generation', function( $content, $params, $provider ) {
    // Track in external analytics
    my_analytics_track( 'content_generated', [
        'provider' => $provider,
        'topic' => $params['topic']
    ]);
}, 10, 3 );
```

#### `acs_generation_failed`
Fires when content generation fails.

```php
do_action( 'acs_generation_failed', $error, $params, $provider );

// Usage:
add_action( 'acs_generation_failed', function( $error, $params, $provider ) {
    // Send alert
    wp_mail( 'admin@site.com', 'ACS Generation Failed', $error->get_error_message() );
}, 10, 3 );
```

#### `acs_settings_saved`
Fires after plugin settings are saved.

```php
do_action( 'acs_settings_saved', $old_settings, $new_settings );
```

#### `acs_analytics_tracked`
Fires after analytics data is recorded.

```php
do_action( 'acs_analytics_tracked', $generation_id, $data );
```

### Filters

#### `acs_select_provider`
Filter the AI provider selection.

```php
$provider = apply_filters( 'acs_select_provider', $provider, $params );

// Usage: Force OpenAI for specific content types
add_filter( 'acs_select_provider', function( $provider, $params ) {
    if ( $params['content_type'] === 'technical' ) {
        return 'openai';
    }
    return $provider;
}, 10, 2 );
```

#### `acs_generation_prompt`
Modify the prompt before sending to AI provider.

```php
$prompt = apply_filters( 'acs_generation_prompt', $prompt, $params );

// Usage: Add custom instructions
add_filter( 'acs_generation_prompt', function( $prompt, $params ) {
    return $prompt . "\n\nAlways include a call-to-action at the end.";
}, 10, 2 );
```

#### `acs_generated_content`
Filter the generated content before returning.

```php
$content = apply_filters( 'acs_generated_content', $content, $params, $provider );

// Usage: Add custom footer
add_filter( 'acs_generated_content', function( $content, $params, $provider ) {
    $content['content'] .= '<p class="ai-disclosure">Generated with AI assistance.</p>';
    return $content;
}, 10, 3 );
```

#### `acs_seo_meta_description`
Filter meta description before saving.

```php
$meta = apply_filters( 'acs_seo_meta_description', $meta, $post_id );
```

#### `acs_internal_link_suggestions`
Filter internal linking suggestions.

```php
$links = apply_filters( 'acs_internal_link_suggestions', $links, $content, $keywords );
```

#### `acs_capabilities`
Filter custom capabilities for access control.

```php
$caps = apply_filters( 'acs_capabilities', [
    'acs_generate_content' => 'edit_posts',
    'acs_manage_settings' => 'manage_options',
    'acs_view_analytics' => 'edit_posts',
    'acs_manage_seo' => 'edit_posts'
]);
```

#### `acs_cache_expiration`
Filter cache TTL for different data types.

```php
$expiration = apply_filters( 'acs_cache_expiration', 300, $cache_key );

// Usage: Longer cache for analytics
add_filter( 'acs_cache_expiration', function( $expiration, $key ) {
    if ( strpos( $key, 'analytics' ) !== false ) {
        return 900; // 15 minutes
    }
    return $expiration;
}, 10, 2 );
```

---

## REST API Reference

Base URL: `/wp-json/acs/v1/`

### Authentication

All endpoints require authentication via WordPress nonce or application password.

```javascript
// JavaScript example
fetch('/wp-json/acs/v1/analytics/summary', {
    headers: {
        'X-WP-Nonce': acsAdmin.nonce
    }
});
```

### Endpoints

#### POST `/generate`
Generate new content.

**Request:**
```json
{
    "topic": "Best WordPress Plugins",
    "keywords": "wordpress, plugins, security",
    "word_count": "medium",
    "tone": "professional",
    "content_type": "blog_post",
    "provider": "groq"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "title": "10 Best WordPress Plugins for 2024",
        "content": "<p>Content here...</p>",
        "meta_description": "Discover the top WordPress plugins...",
        "focus_keyword": "WordPress plugins",
        "slug": "best-wordpress-plugins-2024",
        "excerpt": "A comprehensive guide to...",
        "internal_links": ["post_id_1", "post_id_2"],
        "generation_time": 12.5,
        "tokens_used": 1500,
        "provider": "groq"
    }
}
```

#### GET `/analytics/summary`
Get analytics summary.

**Query Parameters:**
- `period`: today, week, month, year, all (default: month)

**Response:**
```json
{
    "total_generations": 150,
    "period_generations": 45,
    "average_tokens": 1250,
    "total_cost": 2.50,
    "provider_breakdown": {
        "groq": 100,
        "openai": 35,
        "anthropic": 15
    },
    "success_rate": 98.5
}
```

#### GET `/analytics/generations`
Get paginated generation history.

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)
- `provider`: Filter by provider
- `status`: Filter by status (success, failed)
- `start_date`: ISO 8601 date
- `end_date`: ISO 8601 date

**Response:**
```json
{
    "generations": [
        {
            "id": 123,
            "post_id": 456,
            "title": "Generated Title",
            "provider": "groq",
            "tokens": 1200,
            "cost": 0.01,
            "status": "success",
            "created_at": "2024-12-04T10:30:00Z"
        }
    ],
    "total": 150,
    "pages": 8,
    "current_page": 1
}
```

#### GET `/analytics/chart-data`
Get data formatted for charts.

**Query Parameters:**
- `type`: line, bar, pie
- `metric`: generations, tokens, cost
- `period`: 7d, 30d, 90d, 1y

**Response:**
```json
{
    "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    "datasets": [
        {
            "label": "Generations",
            "data": [5, 8, 3, 12, 7, 2, 4]
        }
    ]
}
```

#### GET `/analytics/export`
Export analytics data.

**Query Parameters:**
- `format`: csv, json
- `start_date`: ISO 8601 date
- `end_date`: ISO 8601 date

**Response:** File download

#### GET `/settings`
Get current settings (admin only).

**Response:**
```json
{
    "providers": {
        "groq": { "enabled": true, "model": "mixtral-8x7b-32768" },
        "openai": { "enabled": false },
        "anthropic": { "enabled": false }
    },
    "defaults": {
        "word_count": "medium",
        "tone": "professional"
    }
}
```

#### POST `/settings`
Update settings (admin only).

---

## AJAX Endpoints

All AJAX endpoints use `admin-ajax.php` and require nonce verification.

### `acs_generate_content`
Generate content via AJAX.

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'acs_generate_content',
        nonce: acsAdmin.nonce,
        topic: 'My Topic',
        keywords: 'keyword1, keyword2'
    },
    success: function(response) {
        console.log(response.data);
    }
});
```

### `acs_test_api_connection`
Test provider API connection.

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'acs_test_api_connection',
        nonce: acsAdmin.nonce,
        provider: 'groq',
        api_key: 'gsk_xxx...'
    }
});
```

### `acs_get_dashboard_metrics`
Get cached dashboard metrics.

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'acs_get_dashboard_metrics',
        nonce: acsAdmin.nonce
    }
});
```

### `acs_clear_cache`
Clear plugin cache.

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'acs_clear_cache',
        nonce: acsAdmin.nonce,
        cache_type: 'all' // or 'analytics', 'settings'
    }
});
```

### `acs_log_client_error`
Log client-side errors.

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'acs_log_client_error',
        nonce: acsAdmin.nonce,
        error_code: 'js_error',
        error_message: 'Something went wrong',
        error_data: { stack: '...' }
    }
});
```

---

## Database Schema

### `{prefix}acs_generations`
Tracks all content generations.

```sql
CREATE TABLE {prefix}acs_generations (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id bigint(20) UNSIGNED DEFAULT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    provider varchar(50) NOT NULL,
    model varchar(100) DEFAULT NULL,
    prompt_hash varchar(64) DEFAULT NULL,
    tokens_used int(11) DEFAULT 0,
    cost_estimate decimal(10,6) DEFAULT 0,
    generation_time float DEFAULT 0,
    status varchar(20) DEFAULT 'success',
    error_message text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY user_id (user_id),
    KEY provider (provider),
    KEY created_at (created_at),
    KEY status (status)
);
```

### `{prefix}acs_analytics_events`
Detailed analytics events.

```sql
CREATE TABLE {prefix}acs_analytics_events (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    generation_id bigint(20) UNSIGNED NOT NULL,
    event_type varchar(50) NOT NULL,
    payload longtext DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY generation_id (generation_id),
    KEY event_type (event_type)
);
```

### `{prefix}acs_error_logs`
Error tracking.

```sql
CREATE TABLE {prefix}acs_error_logs (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    error_code varchar(50) NOT NULL,
    error_message text NOT NULL,
    error_data longtext DEFAULT NULL,
    user_id bigint(20) UNSIGNED DEFAULT NULL,
    url varchar(500) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY error_code (error_code),
    KEY created_at (created_at)
);
```

---

## JavaScript API

### Global Objects

#### `window.ACSAdmin`
Main admin interface object.

```javascript
window.ACSAdmin = {
    nonce: '...',
    ajaxUrl: '/wp-admin/admin-ajax.php',
    restUrl: '/wp-json/acs/v1/',
    i18n: { /* Localized strings */ }
};
```

### Modules

#### Error Handler
```javascript
// Import
import ACSErrorHandler from './modules/acs-error-handler.js';

// Show toast notification
ACSErrorHandler.showToast('Success!', 'success');
ACSErrorHandler.showToast('Error occurred', 'error');

// Handle AJAX error
ACSErrorHandler.handleAjaxError(xhr, textStatus, errorThrown);
```

#### Form Validation
```javascript
import ACSFormValidation from './modules/acs-form-validation.js';

// Initialize on form
ACSFormValidation.init(document.querySelector('#settings-form'));

// Add custom validation rule
ACSFormValidation.addRule('custom', (value) => {
    return value.length >= 5;
}, 'Must be at least 5 characters');
```

#### Lazy Loading
```javascript
import ACSLazyLoad from './modules/acs-lazy-load.js';

// Initialize
ACSLazyLoad.init();

// Manually trigger lazy load
ACSLazyLoad.loadElement(element);
```

### Custom Events

```javascript
// Listen for content generation
document.addEventListener('acs:content:generated', (e) => {
    console.log('Content generated:', e.detail);
});

// Listen for error
document.addEventListener('acs:error', (e) => {
    console.error('ACS Error:', e.detail);
});

// Listen for settings change
document.addEventListener('acs:settings:changed', (e) => {
    console.log('Settings changed:', e.detail);
});
```

---

## Extending the Plugin

### Adding Custom Provider

1. Create provider class:

```php
// my-plugin/providers/class-my-provider.php
class My_Custom_Provider extends ACS_AI_Provider {
    
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
        $this->base_url = 'https://api.myprovider.com/v1';
    }
    
    public function generate_content( $prompt, $options = [] ) {
        $response = wp_remote_post( $this->base_url . '/generate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'prompt' => $prompt,
                'max_tokens' => $options['max_tokens'] ?? 2000
            ])
        ]);
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        return [
            'title' => $body['title'],
            'content' => $body['content'],
            'meta_description' => $body['meta'] ?? '',
            'tokens_used' => $body['usage']['total_tokens'] ?? 0
        ];
    }
}
```

2. Register provider:

```php
add_filter( 'acs_available_providers', function( $providers ) {
    $providers['my_provider'] = [
        'name' => 'My Provider',
        'class' => 'My_Custom_Provider',
        'file' => __DIR__ . '/providers/class-my-provider.php'
    ];
    return $providers;
});
```

### Adding Custom Content Type

```php
add_filter( 'acs_content_types', function( $types ) {
    $types['case_study'] = [
        'label' => 'Case Study',
        'description' => 'Business case study format',
        'prompt_template' => 'Write a detailed case study about {topic}...',
        'default_word_count' => 'long'
    ];
    return $types;
});
```

### Adding Custom Analytics Widget

```php
add_action( 'acs_analytics_dashboard_widgets', function() {
    ?>
    <div class="acs-widget">
        <h3>Custom Metric</h3>
        <div id="custom-metric-widget"></div>
    </div>
    <?php
});

add_action( 'admin_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize custom widget
        fetch('/wp-json/my-plugin/v1/custom-metric')
            .then(r => r.json())
            .then(data => {
                document.getElementById('custom-metric-widget').innerHTML = data.html;
            });
    });
    </script>
    <?php
});
```

---

## Security Considerations

### API Key Encryption

API keys are encrypted using AES-256:

```php
// Encryption is automatic when saving settings
$encryption = new ACS_Encryption();
$encrypted = $encryption->encrypt( $api_key );
$decrypted = $encryption->decrypt( $encrypted );
```

### Capability Checks

Always check capabilities before sensitive operations:

```php
if ( ! current_user_can( 'acs_manage_settings' ) ) {
    wp_die( 'Unauthorized access' );
}
```

### Nonce Verification

All forms and AJAX requests require nonce:

```php
// In form
wp_nonce_field( 'acs_settings_action', 'acs_settings_nonce' );

// Verification
if ( ! wp_verify_nonce( $_POST['acs_settings_nonce'], 'acs_settings_action' ) ) {
    wp_die( 'Security check failed' );
}
```

### Input Sanitization

Always sanitize user input:

```php
$topic = sanitize_text_field( $_POST['topic'] );
$content = wp_kses_post( $_POST['content'] );
$url = esc_url_raw( $_POST['url'] );
```

---

## Testing

### Running Tests

```bash
# Install dependencies
composer install

# Run all tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/TestAnalytics.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Writing Tests

```php
// tests/TestMyFeature.php
class TestMyFeature extends SimpleTestCase {
    
    public function test_feature_works() {
        // Arrange
        $input = 'test data';
        
        // Act
        $result = my_function( $input );
        
        // Assert
        $this->assertEquals( 'expected', $result );
    }
    
    public function test_error_handling() {
        $this->expectException( InvalidArgumentException::class );
        my_function( null );
    }
}
```

### Test Bootstrap

The test bootstrap (`tests/bootstrap.php`) provides:
- Mock WordPress functions
- Mock WP_Error class
- Test post/meta storage
- Mock AJAX handlers

---

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Follow WordPress Coding Standards
4. Add tests for new functionality
5. Submit pull request

### Code Standards

```bash
# Check coding standards
./vendor/bin/phpcs --standard=phpcs.xml

# Auto-fix issues
./vendor/bin/phpcbf --standard=phpcs.xml
```

---

## Support

- GitHub Issues: [Report bugs](link)
- Documentation: [Full docs](link)
- Email: support@example.com

---

*AI Content Studio Developer Documentation v1.0.1*
