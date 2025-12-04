# AI Content Studio - User Guide

> Complete guide to using AI Content Studio for WordPress content generation

**Version:** 1.0.1  
**Last Updated:** December 2024

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Generating Content](#generating-content)
4. [Analytics & Reporting](#analytics--reporting)
5. [Settings Configuration](#settings-configuration)
6. [SEO Optimization](#seo-optimization)
7. [Keyboard Shortcuts](#keyboard-shortcuts)
8. [Troubleshooting](#troubleshooting)

---

## Getting Started

### First-Time Setup

1. **Activate the Plugin**
   - Go to Plugins → Installed Plugins
   - Find "AI Content Studio" and click Activate

2. **Configure API Keys**
   - Navigate to AI Content Studio → Settings
   - Click the "AI Providers" tab
   - Enter your API key for at least one provider:
     - **Groq** (Recommended): Fast and cost-effective
     - **OpenAI**: High-quality GPT models
     - **Anthropic**: Claude models

3. **Test Connection**
   - Click the "Test Connection" button next to your API key
   - A green checkmark indicates successful connection
   - If failed, verify your API key and account status

### Minimum Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- At least one AI provider API key

---

## Dashboard Overview

### Main Dashboard

The main dashboard provides a quick overview of your content generation activity:

- **Total Generations**: All-time content pieces generated
- **This Month**: Content generated in the current month
- **Average Tokens**: Average token usage per generation
- **Primary Provider**: Your most-used AI provider

### Quick Actions

- **Generate Content**: Start a new content generation
- **View Analytics**: See detailed statistics
- **Settings**: Configure plugin options

### Recent Activity

The dashboard shows your most recent generations with:
- Content title
- Provider used
- Generation date
- Quick edit link

---

## Generating Content

### Step-by-Step Guide

1. **Access Generator**
   - Go to AI Content Studio → Generate Content
   - Or use keyboard shortcut: `Ctrl+Shift+G`

2. **Enter Topic**
   - Type your content topic in the main input field
   - Be specific for better results
   - Example: "10 Best WordPress Security Plugins for Small Businesses"

3. **Add Keywords** (Optional)
   - Enter focus keywords separated by commas
   - These help optimize SEO
   - Example: "WordPress security, website protection, malware scanner"

4. **Select Content Type**
   - Blog Post
   - Product Review
   - How-to Guide
   - Listicle
   - Comparison Article

5. **Choose Word Count**
   - Short (500-800 words)
   - Medium (1000-1500 words)
   - Long (2000-3000 words)
   - Extended (3500+ words)

6. **Select Tone**
   - Professional
   - Casual
   - Informative
   - Persuasive
   - Technical

7. **Generate**
   - Click "Generate Content"
   - Wait for AI to process (usually 10-30 seconds)
   - Review the generated content

### Content Preview

After generation, you'll see:
- Generated title
- Full content with formatting
- Meta description
- Focus keyword suggestions
- Internal link suggestions

### Publishing Options

- **Publish Immediately**: Content goes live right away
- **Save as Draft**: Edit before publishing
- **Schedule**: Set a future publish date
- **Copy to Clipboard**: Use content elsewhere

### Generation Tips

✅ **Do:**
- Use specific, detailed topics
- Include target audience in prompt
- Add relevant keywords
- Review and edit generated content

❌ **Don't:**
- Use vague one-word topics
- Skip keyword research
- Publish without review
- Exceed API rate limits

---

## Analytics & Reporting

### Accessing Analytics

Navigate to AI Content Studio → Analytics or use `Ctrl+Shift+A`

### Dashboard Metrics

1. **Generation Overview**
   - Total generations (all time)
   - This week/month/year
   - Average tokens per generation
   - Estimated costs

2. **Provider Usage**
   - Pie chart showing provider distribution
   - Cost breakdown by provider
   - Success/failure rates

3. **Trends**
   - Line chart of generations over time
   - Daily/weekly/monthly views
   - Compare periods

### Filtering Data

- **Date Range**: Today, Last 7 days, Last 30 days, Custom
- **Provider**: All, Groq, OpenAI, Anthropic
- **Status**: Success, Failed, All

### Exporting Data

1. Click the Export button
2. Choose format:
   - **CSV**: For spreadsheets
   - **JSON**: For developers
3. Select date range
4. Download file

---

## Settings Configuration

### General Settings

Access via AI Content Studio → Settings

**Content Defaults:**
- Default word count
- Default tone
- Auto-save drafts
- Default content type

**SEO Settings:**
- Meta description length (default: 155)
- Keyword density target (default: 1.5%)
- Enable internal linking suggestions

### AI Providers

**Groq Configuration:**
- API Key
- Default model (mixtral-8x7b-32768)
- Temperature (0-1)
- Max tokens

**OpenAI Configuration:**
- API Key
- Model selection (GPT-4, GPT-3.5-turbo)
- Temperature
- Max tokens

**Anthropic Configuration:**
- API Key
- Model (Claude 3)
- Temperature
- Max tokens

### Advanced Settings

**Performance:**
- Enable caching (recommended)
- Cache duration (default: 5 minutes)
- Lazy load analytics data

**Logging:**
- Log level (Info, Warning, Error, Debug)
- Log retention days
- Enable error tracking

**Rate Limiting:**
- Max requests per minute
- Max requests per hour
- Cooldown period

### Import/Export Settings

- **Export**: Download all settings as JSON
- **Import**: Upload JSON settings file
- **Reset**: Restore default settings

---

## SEO Optimization

### Automatic SEO Features

AI Content Studio automatically:
- Generates meta descriptions
- Suggests focus keywords
- Optimizes keyword density
- Recommends internal links
- Creates SEO-friendly titles

### SEO Plugin Integration

Compatible with:
- **Yoast SEO**: Automatic meta fill
- **Rank Math**: Focus keyword integration
- **SEOPress**: Full meta support
- **All in One SEO**: Complete integration

### Readability Optimization

The plugin analyzes:
- Sentence length
- Paragraph structure
- Passive voice usage
- Transition words
- Flesch reading ease

### Best Practices

1. Always review auto-generated meta descriptions
2. Verify keyword placement
3. Check internal link relevance
4. Run SEO plugin analysis before publishing

---

## Keyboard Shortcuts

Speed up your workflow with these shortcuts:

| Shortcut | Action |
|----------|--------|
| `Ctrl+Shift+G` | Open Generate Content |
| `Ctrl+Shift+D` | Go to Dashboard |
| `Ctrl+Shift+A` | Open Analytics |
| `Ctrl+Shift+S` | Open Settings |
| `?` | Show all shortcuts |
| `Esc` | Close modal/dialog |

*Note: On Mac, use `Cmd` instead of `Ctrl`*

---

## Troubleshooting

### Common Issues

#### "API Connection Failed"

**Causes:**
- Invalid API key
- Account has no credits
- Provider service is down

**Solutions:**
1. Verify API key is correct
2. Check provider account status
3. Visit provider's status page
4. Try a different provider

#### "Generation Failed"

**Causes:**
- Prompt too long
- Rate limit exceeded
- Server timeout

**Solutions:**
1. Shorten your topic/prompt
2. Wait and retry
3. Check error message for details
4. Switch to backup provider

#### "Content Quality Issues"

**Causes:**
- Vague prompts
- Incorrect settings
- Model limitations

**Solutions:**
1. Be more specific in your topic
2. Add relevant keywords
3. Try different content type
4. Adjust tone settings

### Error Messages

| Error | Meaning | Solution |
|-------|---------|----------|
| `rate_limit_exceeded` | Too many requests | Wait 60 seconds |
| `invalid_api_key` | API key incorrect | Re-enter your API key |
| `insufficient_quota` | No credits left | Add credits to provider |
| `context_length_exceeded` | Prompt too long | Shorten your input |
| `server_error` | Provider issue | Try again later |

### Getting Help

1. **Check Logs**: AI Content Studio → Logs
2. **Enable Debug Mode**: Settings → Advanced → Enable Debug
3. **Contact Support**: [support link]
4. **Community Forum**: [forum link]

### Debug Mode

Enable detailed logging:

1. Go to Settings → Advanced
2. Set Log Level to "Debug"
3. Reproduce the issue
4. Check AI Content Studio → Logs

---

## FAQ

**Q: How much does AI generation cost?**
A: Costs vary by provider. Groq is cheapest (~$0.01/article), OpenAI mid-range (~$0.05-0.15/article), Anthropic varies (~$0.02-0.08/article).

**Q: Can I use multiple providers?**
A: Yes! Configure all providers and the plugin automatically fails over if one is unavailable.

**Q: Is my API key secure?**
A: Yes, all API keys are encrypted with AES-256 before storage.

**Q: How do I update the plugin?**
A: Updates appear in WordPress admin when available. Always backup before updating.

**Q: Can I customize the generated content?**
A: Yes! All content is generated as drafts by default so you can edit before publishing.

---

## Need More Help?

- 📖 [Full Documentation](link)
- 🐛 [Report a Bug](link)
- 💡 [Request a Feature](link)
- 💬 [Community Support](link)

---

*AI Content Studio - Making content creation effortless*
