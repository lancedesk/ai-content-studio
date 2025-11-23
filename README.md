# AI Content Studio

>A WordPress plugin that generates SEO-optimized content using multiple AI providers (Groq, OpenAI, Anthropic) with admin controls, logging, and safety checks.

- Requires: PHP 7.4+ and WordPress 5.8+
- Tested up to: 6.4
- License: GPLv2 or later

## Quick links

- Settings: `AI Content Studio → Settings`
- Generate: `AI Content Studio → Generate`
- Logs: `AI Content Studio → Generation Logs`

---

## Features

- Multi-provider generation with failover (Groq, OpenAI, Anthropic)
- SEO enhancements: meta descriptions, focus keyword support, internal linking suggestions
- Admin UI for provider keys, generation templates, and content post-creation
- Role/capability based access control and nonce verification
- File-based generation logging, rotation, and export (CSV/JSON)
- Optional auto-publish and scheduling features

## Installation (GitHub / Manual)

1. Upload or clone this repository into `/wp-content/plugins/ai-content-studio/`.
2. Activate the plugin in the WordPress admin Plugins screen.
3. Go to `AI Content Studio → Settings` and configure at least one provider API key.

For WordPress.org releases, include the provided `readme.txt` in the plugin root.

## Configuration

Configure provider API keys in `AI Content Studio → Settings`:

- Groq: Recommended provider. Configure key and test connection.
- OpenAI: Optional; use as a fallback or alternative provider.
- Anthropic: Optional; configure for Claude models.

If an API key is missing, administrators will see a warning notice with a link to the settings page.

## Usage — Quick Start

1. Go to `AI Content Studio → Generate`.
2. Enter a topic, select a template, and optionally provide focus keywords.
3. Click `Generate Content` and review the draft.
4. Publish or save as a draft.

## Developer Notes

- Settings are persisted under the `acs_settings` option. Provider keys live at `acs_settings['providers'][<provider>]['api_key']`.
- Generator logic lives in `generators/class-acs-content-generator.php`.
- Activation defaults are created in `includes/class-acs-activator.php`.
- Use provided hooks: `acs_generate_content`, `acs_select_provider`, `acs_after_content_generation`.

## WordPress.org Compatibility

For WordPress.org plugin directory, include `readme.txt` (WordPress readme standard). A `readme.txt` file has been added to the plugin root to support that format.

## Changelog

See `CHANGELOG.md` for a complete changelog. The current stable release is `1.0.0`.

## License

GPL v2 or later

---

If you want I can also (optionally) generate a `readme.txt` entry tailored to the WordPress.org readme parser or add CI steps to automatically build release ZIPs for GitHub releases.
# AI Content Studio - WordPress Plugin

A comprehensive WordPress plugin for AI-powered content generation using multiple providers (Groq, OpenAI, Anthropic) with advanced SEO optimization and security features.

## Features

🚀 **Multi-Provider AI Support**
- Primary: Groq API (fast, cost-effective)
- Backup: OpenAI GPT models
- Alternative: Anthropic Claude models
- Automatic failover between providers

📝 **Content Generation**
- Blog posts, articles, product reviews
- How-to guides, listicles, comparisons
- Customizable word count (500-4000+ words)
- Multiple writing tones and styles
- Target audience optimization

🔍 **SEO Optimization**
- Automatic meta descriptions
- Focus keyword optimization
- Internal linking suggestions
- Compatible with Yoast, RankMath, SEOPress
- Schema markup support

🛡️ **Enterprise Security**
- AES-256 encryption for API keys
- Comprehensive input sanitization
- Capability-based permissions
- Rate limiting protection
- Audit logging

📊 **Analytics & Monitoring**
- Content performance tracking
- API usage and cost monitoring
- Generation success rates
- Provider performance metrics

## Installation

### Method 1: Upload Plugin

1. Download the plugin files
2. Upload to `/wp-content/plugins/ai-content-studio/`
3. Activate through WordPress admin
4. Configure AI provider API keys

### Method 2: WordPress Admin

1. Go to Plugins > Add New
2. Upload the plugin ZIP file
3. Activate the plugin
4. Follow setup wizard

## Configuration

### 1. API Provider Setup

**Groq (Recommended)**
- Sign up at [Groq Console](https://console.groq.com/)
- Generate API key
- Paste in Settings > AI Providers > Groq

**OpenAI (Optional)**
- Get API key from [OpenAI Platform](https://platform.openai.com/api-keys)
- Add to Settings > AI Providers > OpenAI

**Anthropic (Optional)**
- Get API key from [Anthropic Console](https://console.anthropic.com/)
- Add to Settings > AI Providers > Anthropic

### 2. Basic Settings

Navigate to **AI Content Studio > Settings**:

- **Default Provider**: Choose primary AI service
- **Content Settings**: Default word count, tone, auto-publish
- **SEO Settings**: Meta description length, keyword density
- **Advanced**: Logging level, cost tracking, rate limiting

## Usage

### Quick Start

1. Go to **AI Content Studio > Generate Content**
2. Enter your topic/prompt
3. Add target keywords
4. Select content type and settings
5. Click **Generate Content**
6. Review and publish or save as draft

### Content Templates

Use pre-configured templates for:
- **Product Reviews**: Comprehensive analysis with pros/cons
- **How-to Guides**: Step-by-step instructions
- **Listicles**: Numbered list articles
- **Comparisons**: Side-by-side product comparisons

### Advanced Features

**Keyword Research**
- Click "Get Suggestions" to find related keywords
- AI-powered keyword analysis
- Competition and search volume insights

**Content Queue**
- Batch generate multiple articles
- Schedule publication times
- Process queue automatically

**Analytics Dashboard**
- Track generation statistics
- Monitor API costs
- View content performance

## API Configuration

### Groq API Setup

```php
// Your Groq API key format
gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

// Models available:
- mixtral-8x7b-32768 (Default, recommended)
- llama2-70b-4096
- gemma-7b-it
```

### OpenAI API Setup

```php
// Your OpenAI API key format
sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

// Models available:
- gpt-4 (Highest quality)
- gpt-3.5-turbo (Fast, cost-effective)
```

### Anthropic API Setup

```php
// Your Anthropic API key format
sk-ant-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

// Models available:
- claude-3-opus (Most capable)
- claude-3-sonnet (Balanced)
- claude-3-haiku (Fastest)
```

## SEO Integration

### Supported SEO Plugins

- **Yoast SEO**: Full meta optimization
- **Rank Math**: Focus keyword integration
- **SEOPress**: Complete SEO suite
- **All in One SEO**: Meta and schema support

### SEO Features

- Automatic meta descriptions (155 char limit)
- Focus keyword density optimization (1-2%)
- Internal linking suggestions
- Schema markup generation
- Readability optimization

## Security Features

### Data Protection
- API keys encrypted with AES-256
- WordPress salts as encryption keys
- Secure key storage in database
- No plain text API key logging

### Access Control
- Role-based permissions
- Capability checks on all actions
- Nonce verification for forms
- CSRF protection

### Input Validation
- Comprehensive sanitization
- SQL injection prevention
- XSS protection
- File upload restrictions

## Performance Optimization

### Caching Strategy
- Transient API for temporary data
- Object caching for repeated queries
- Database query optimization
- Conditional loading of assets

### Rate Limiting
- API call throttling
- Provider-specific limits
- Queue management
- Error handling and retries

## Troubleshooting

### Common Issues

**API Connection Failed**
1. Verify API key is correct
2. Check provider status page
3. Confirm account has credits
4. Test with different model

**Content Generation Errors**
1. Check prompt length limits
2. Verify keyword formatting
3. Try different provider
4. Review error logs

**Plugin Activation Issues**
1. Check PHP version (7.4+ required)
2. Verify WordPress version (5.8+)
3. Confirm write permissions
4. Check error logs

### Debug Mode

Enable debug logging in Settings > Advanced:

```php
// Add to wp-config.php for detailed logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('ACS_DEBUG', true);
```

## Database Schema

The plugin creates 4 tables:

```sql
-- Projects and content organization
acs_projects (id, name, description, settings, created_at)

-- Keyword research and tracking
acs_keywords (id, keyword, competition, volume, difficulty)

-- API usage logging
acs_api_logs (id, provider, endpoint, tokens, cost, timestamp)

-- Content generation queue
acs_content_queue (id, prompt, status, priority, scheduled_at)
```

## API Endpoints

### REST API

```php
// Generate content
POST /wp-json/acs/v1/generate
{
    "prompt": "Your content topic",
    "keywords": "keyword1, keyword2",
    "word_count": "1500-2500",
    "provider": "groq"
}

// Get keywords
GET /wp-json/acs/v1/keywords?topic=your-topic

// Check status
GET /wp-json/acs/v1/status
```

### AJAX Endpoints

- `acs_generate_content`: Generate new content
- `acs_test_api_connection`: Test provider API
- `acs_get_keyword_suggestions`: Get keyword ideas
- `acs_save_settings`: Save plugin settings

## Cost Estimation

### Groq Pricing (Approximate)
- Input: $0.27 per 1M tokens
- Output: $0.27 per 1M tokens
- Average 1500-word article: ~$0.01-0.03

### OpenAI Pricing
- GPT-4: $30/$60 per 1M tokens (input/output)
- GPT-3.5 Turbo: $0.50/$1.50 per 1M tokens
- Average article: $0.05-0.15

### Anthropic Pricing
- Claude 3 Opus: $15/$75 per 1M tokens
- Claude 3 Sonnet: $3/$15 per 1M tokens
- Average article: $0.02-0.08

## Development

### Plugin Structure

```
ai-content-studio/
├── ai-content-studio.php          # Main plugin file
├── includes/                      # Core functionality
│   ├── class-acs-core.php
│   ├── class-acs-activator.php
│   └── class-acs-deactivator.php
├── admin/                         # Admin interface
│   ├── class-acs-admin.php
│   └── templates/
├── api/                          # AI provider integration
│   ├── class-acs-ai-provider.php
│   └── providers/
├── security/                     # Security classes
│   ├── class-acs-encryption.php
│   ├── class-acs-sanitizer.php
│   └── class-acs-validator.php
└── assets/                       # CSS/JS files
    ├── css/
    └── js/
```

### Hooks and Filters

```php
// Content generation filter
add_filter('acs_generate_content', 'your_function', 10, 2);

// Provider selection filter
add_filter('acs_select_provider', 'your_function', 10, 1);

// SEO optimization hook
add_action('acs_after_content_generation', 'your_function', 10, 1);
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Follow WordPress Coding Standards
4. Add unit tests
5. Submit pull request

## License

GPL v2 or later - Compatible with WordPress.org requirements

## Support

- **Documentation**: [Plugin Wiki](link-to-docs)
- **Issues**: [GitHub Issues](link-to-issues)
- **Community**: [WordPress Forum](link-to-forum)

## Changelog

### Version 1.0.0
- Initial release
- Multi-provider AI support
- Complete admin interface
- SEO optimization features
- Security framework

---

**Made with ❤️ for the WordPress community**