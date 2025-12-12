<?php
/**
 * Enhanced Prompt Engine Class
 *
 * Provides advanced prompt construction with detailed SEO constraints and validation requirements.
 * Replaces the basic prompt building with comprehensive SEO-aware prompt generation.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class EnhancedPromptEngine
 *
 * Advanced prompt engineering system with SEO constraints
 */
class EnhancedPromptEngine {
    
    /**
     * @var SEOPromptConfiguration Configuration for SEO constraints
     */
    private $config;
    
    /**
     * @var array Internal link candidates for content
     */
    private $internalCandidates;
    
    /**
     * @var array Fallback prompt strategies
     */
    private $fallbackStrategies;
    
    /**
     * Constructor
     *
     * @param SEOPromptConfiguration $config SEO configuration
     */
    public function __construct(SEOPromptConfiguration $config) {
        $this->config = $config;
        $this->internalCandidates = [];
        $this->initializeFallbackStrategies();
    }
    
    /**
     * Build SEO-optimized prompt with detailed constraints
     *
     * @param string $topic Content topic
     * @param string $keywords Primary and secondary keywords
     * @param string $wordCount Target word count (short, medium, long, detailed)
     * @param array $internalCandidates Internal link candidates
     * @return string Complete SEO-optimized prompt
     */
    public function buildSEOPrompt($topic, $keywords = '', $wordCount = 'medium', $internalCandidates = null) {
        $this->internalCandidates = $internalCandidates ?: [];
        
        // Parse keywords
        $primaryKeyword = $this->config->focusKeyword;
        if (!empty($keywords)) {
            $keywordArray = array_map('trim', explode(',', $keywords));
            $primaryKeyword = $keywordArray[0] ?? $primaryKeyword;
        }
        
        // Update config with actual word count from parameter (may be a range like "750-1500")
        if (preg_match('/^(\d+)-(\d+)$/', $wordCount, $matches)) {
            $min = intval($matches[1]);
            $max = intval($matches[2]);
            $this->config->targetWordCount = "{$min}-{$max}";
        } elseif (is_numeric($wordCount)) {
            $this->config->targetWordCount = intval($wordCount);
        }
        
        // Build comprehensive prompt
        $prompt = $this->buildPromptHeader($topic, $primaryKeyword, $wordCount);
        $prompt .= $this->buildSEOConstraints();
        $prompt .= $this->buildValidationRequirements();
        $prompt .= $this->buildStructureRequirements();
        $prompt .= $this->buildContentGuidelines();
        $prompt .= $this->buildOutputFormat();
        
        if (!empty($this->internalCandidates)) {
            $prompt .= $this->buildInternalLinkGuidance();
        }
        
        return $prompt;
    }
    
    /**
     * Build prompt header with basic requirements
     *
     * @param string $topic Content topic
     * @param string $primaryKeyword Primary focus keyword
     * @param string $wordCount Target word count
     * @return string Prompt header
     */
    private function buildPromptHeader($topic, $primaryKeyword, $wordCount) {
        // Handle range format like "750-1500"
        $target = $wordCount;
        if (preg_match('/^(\d+)-(\d+)$/', $wordCount, $matches)) {
            $min = intval($matches[1]);
            $max = intval($matches[2]);
            $target = "{$min}-{$max}";
        } elseif (is_numeric($wordCount)) {
            $target = intval($wordCount);
        } else {
            $wordTargets = [
                'short' => '500',
                'medium' => '1000',
                'long' => '1500',
                'detailed' => '2000'
            ];
            $target = $wordTargets[$wordCount] ?? '1000';
        }
        
        $header = "You are an expert SEO copywriter specializing in creating content that passes Yoast SEO and RankMath validation.\n\n";
        $header .= "CONTENT BRIEF:\n";
        $header .= "- Topic: {$topic}\n";
        $header .= "- Primary Keyword: {$primaryKeyword}\n";
        $header .= "- Target Word Count: {$target} words (THIS IS CRITICAL - CONTENT MUST BE THIS LENGTH)\n";
        
        if (!empty($this->config->secondaryKeywords)) {
            $header .= "- Secondary Keywords: " . implode(', ', $this->config->secondaryKeywords) . "\n";
        }
        
        $header .= "\n";
        
        return $header;
    }
    
    /**
     * Build detailed SEO constraints section
     *
     * @return string SEO constraints
     */
    private function buildSEOConstraints() {
        $constraints = "CRITICAL SEO REQUIREMENTS (MUST BE FOLLOWED):\n\n";
        
        // Meta description constraints
        $metaConstraints = $this->config->getMetaDescriptionConstraints();
        $constraints .= "1. META DESCRIPTION:\n";
        $constraints .= "   - Length: {$metaConstraints['minLength']}-{$metaConstraints['maxLength']} characters (EXACT)\n";
        $constraints .= "   - Must include primary keyword '{$this->config->focusKeyword}' or close synonym\n";
        $constraints .= "   - Must be compelling and click-worthy\n";
        $constraints .= "   - No transition words at the beginning\n\n";
        
        // Title constraints
        $titleConstraints = $this->config->getTitleConstraints();
        $constraints .= "2. TITLE:\n";
        $constraints .= "   - Maximum {$titleConstraints['maxLength']} characters\n";
        $constraints .= "   - Must begin with primary keyword '{$this->config->focusKeyword}'\n";
        $constraints .= "   - Must be unique and compelling\n\n";
        
        // Keyword density constraints
        $keywordConstraints = $this->config->getKeywordDensityConstraints();
        $constraints .= "3. KEYWORD USAGE:\n";
        $constraints .= "   - Keyword density: {$keywordConstraints['minDensity']}%-{$keywordConstraints['maxDensity']}% of total words\n";
        $constraints .= "   - Include synonyms and variations naturally\n";
        $constraints .= "   - Maximum {$keywordConstraints['maxSubheadingUsage']}% of subheadings can contain the keyword\n";
        $constraints .= "   - First paragraph MUST contain the exact primary keyword\n\n";
        
        // Emphasize word count requirement
        $wordCountTarget = $this->config->targetWordCount;
        if (is_numeric($wordCountTarget)) {
            $constraints .= "4. CONTENT LENGTH (CRITICAL):\n";
            $constraints .= "   - You MUST write EXACTLY {$wordCountTarget} words (not less, not more)\n";
            $constraints .= "   - Count words carefully - this is a hard requirement\n";
            $constraints .= "   - Short content will be rejected\n\n";
        }
        
        // Readability constraints
        $readabilityConstraints = $this->config->getReadabilityConstraints();
        $sectionNum = is_numeric($this->config->targetWordCount) ? '5' : '4';
        $constraints .= "{$sectionNum}. READABILITY:\n";
        $constraints .= "   - Passive voice: Less than {$readabilityConstraints['maxPassiveVoice']}% of sentences\n";
        $constraints .= "   - Transition words: At least {$readabilityConstraints['minTransitionWords']}% of sentences\n";
        $constraints .= "   - Long sentences: Maximum {$readabilityConstraints['maxLongSentences']}% over 20 words\n";
        $constraints .= "   - Use active voice and clear, concise language\n\n";
        
        // Image constraints
        $imageConstraints = $this->config->getImageConstraints();
        if ($imageConstraints['requireImages']) {
            $sectionNum = is_numeric($this->config->targetWordCount) ? '6' : '5';
            $constraints .= "{$sectionNum}. IMAGES:\n";
            $constraints .= "   - Include at least one relevant image prompt\n";
            if ($imageConstraints['requireKeywordInAltText']) {
                $constraints .= "   - Alt text must include primary keyword or synonym\n";
            }
            $constraints .= "   - Alt text must be descriptive and accessible\n\n";
        }
        
        return $constraints;
    }
    
    /**
     * Build validation requirements section
     *
     * @return string Validation requirements
     */
    private function buildValidationRequirements() {
        $validation = "VALIDATION REQUIREMENTS:\n\n";
        $validation .= "Your content will be automatically validated against these criteria:\n";
        $validation .= "- Meta description character count (exact range required)\n";
        $validation .= "- Keyword density calculation (including synonyms)\n";
        $validation .= "- Passive voice percentage analysis\n";
        $validation .= "- Sentence length distribution\n";
        $validation .= "- Transition word usage frequency\n";
        $validation .= "- Subheading keyword distribution\n";
        $validation .= "- First paragraph keyword inclusion\n";
        $validation .= "- Image alt text keyword presence\n";
        $validation .= "- Internal and external link requirements\n\n";
        $validation .= "FAILURE TO MEET THESE REQUIREMENTS WILL RESULT IN CONTENT REJECTION.\n\n";
        
        return $validation;
    }
    
    /**
     * Build content structure requirements
     *
     * @return string Structure requirements
     */
    private function buildStructureRequirements() {
        $structure = "CONTENT STRUCTURE REQUIREMENTS:\n\n";
        $structure .= "1. TITLE: Compelling, keyword-focused, under {$this->config->maxTitleLength} characters\n";
        $structure .= "2. INTRODUCTION: Hook + keyword in first paragraph\n";
        $structure .= "3. MAIN CONTENT: Well-structured with H2/H3 subheadings\n";
        $structure .= "4. SUBHEADINGS: Descriptive, varied, strategic keyword usage\n";
        $structure .= "5. PARAGRAPHS: 2-5 sentences each, good flow\n";
        $structure .= "6. CONCLUSION: Summarize key points, call-to-action\n";
        $structure .= "7. INTERNAL LINKS: At least 2, relevant anchor text (not exact keyword)\n";
        $structure .= "8. EXTERNAL LINKS: At least 1 authoritative source\n\n";
        
        return $structure;
    }
    
    /**
     * Build content writing guidelines
     *
     * @return string Content guidelines
     */
    private function buildContentGuidelines() {
        $guidelines = "CONTENT WRITING GUIDELINES:\n\n";
        
        // Emphasize word count requirement
        $wordCountTarget = $this->config->targetWordCount;
        if (is_numeric($wordCountTarget)) {
            $guidelines .= "WORD COUNT (MANDATORY):\n";
            $guidelines .= "- You MUST write EXACTLY {$wordCountTarget} words\n";
            $guidelines .= "- This is a hard requirement - content shorter than {$wordCountTarget} words will be REJECTED\n";
            $guidelines .= "- Count your words carefully before submitting\n";
            $guidelines .= "- Expand your content with detailed explanations, examples, and practical tips\n\n";
        }
        
        $guidelines .= "TONE & STYLE:\n";
        $guidelines .= "- Professional yet conversational\n";
        $guidelines .= "- Authoritative and trustworthy\n";
        $guidelines .= "- Engaging and reader-focused\n";
        $guidelines .= "- Use active voice predominantly\n\n";
        
        $guidelines .= "KEYWORD INTEGRATION:\n";
        $guidelines .= "- Use primary keyword naturally throughout\n";
        $guidelines .= "- Include semantic variations and synonyms\n";
        $guidelines .= "- Avoid keyword stuffing\n";
        $guidelines .= "- Focus on user intent and value\n\n";
        
        $guidelines .= "READABILITY OPTIMIZATION:\n";
        $guidelines .= "- Use transition words: however, moreover, therefore, additionally\n";
        $guidelines .= "- Vary sentence length (aim for 15-20 words average)\n";
        $guidelines .= "- Use bullet points and lists where appropriate\n";
        $guidelines .= "- Include examples and practical tips\n\n";
        
        return $guidelines;
    }
    
    /**
     * Build output format requirements
     *
     * @return string Output format specification
     */
    private function buildOutputFormat() {
        $format = "OUTPUT FORMAT REQUIREMENTS:\n\n";
        $format .= "Return ONLY a valid JSON object with these exact keys:\n";
        $format .= "{\n";
        $format .= '  "title": "SEO-optimized title under ' . $this->config->maxTitleLength . ' chars",'. "\n";
        $format .= '  "meta_description": "' . $this->config->minMetaDescLength . '-' . $this->config->maxMetaDescLength . ' character description with keyword",'. "\n";
        $format .= '  "slug": "url-friendly-slug",'. "\n";
        $format .= '  "content": "<p>Well-structured HTML content with H2/H3 headings</p>",'. "\n";
        $format .= '  "excerpt": "20-30 word summary",'. "\n";
        $format .= '  "focus_keyword": "' . $this->config->focusKeyword . '",'. "\n";
        $format .= '  "secondary_keywords": ["keyword1", "keyword2"],'. "\n";
        $format .= '  "tags": ["tag1", "tag2", "tag3"],'. "\n";
        $format .= '  "image_prompts": [{"prompt": "image description", "alt": "keyword-rich alt text"}],'. "\n";
        $format .= '  "internal_links": [{"url": "https://example.com/page", "anchor": "descriptive anchor"}],'. "\n";
        $format .= '  "outbound_links": [{"url": "https://authority-site.com", "anchor": "relevant anchor"}]'. "\n";
        $format .= "}\n\n";
        
        $format .= "CRITICAL: No surrounding text, markdown, or explanations. JSON only.\n\n";
        
        return $format;
    }
    
    /**
     * Build internal link guidance section
     *
     * @return string Internal link guidance
     */
    private function buildInternalLinkGuidance() {
        if (empty($this->internalCandidates)) {
            return "";
        }
        
        $guidance = "INTERNAL LINK CANDIDATES:\n\n";
        $guidance .= "Use these existing pages/posts for internal linking when relevant:\n";
        
        foreach ($this->internalCandidates as $candidate) {
            $title = $candidate['title'] ?? $candidate['post_title'] ?? '';
            $url = $candidate['url'] ?? $candidate['permalink'] ?? '';
            if ($title && $url) {
                $guidance .= "- Title: {$title}\n";
                $guidance .= "  URL: {$url}\n";
            }
        }
        
        $guidance .= "\nUse descriptive anchor text (NOT the exact focus keyword).\n\n";
        
        return $guidance;
    }
    
    /**
     * Add validation constraints directly to prompt
     *
     * @param array $customConstraints Additional constraints
     * @return string Embedded validation constraints
     */
    public function addValidationConstraints($customConstraints = []) {
        $constraints = "EMBEDDED VALIDATION CONSTRAINTS:\n\n";
        $constraints .= "The following constraints are embedded in this prompt and will be automatically validated:\n\n";
        
        // Standard constraints
        $constraints .= $this->config->generatePromptConstraints();
        
        // Custom constraints
        if (!empty($customConstraints)) {
            $constraints .= "\n\nADDITIONAL CONSTRAINTS:\n";
            foreach ($customConstraints as $constraint) {
                $constraints .= "- {$constraint}\n";
            }
        }
        
        $constraints .= "\n";
        
        return $constraints;
    }
    
    /**
     * Generate fallback prompt for retry scenarios
     *
     * @param string $topic Content topic
     * @param string $keywords Keywords
     * @param string $wordCount Word count target
     * @param array $validationErrors Previous validation errors
     * @param int $attemptNumber Current attempt number
     * @return string Fallback prompt
     */
    public function generateFallbackPrompt($topic, $keywords, $wordCount, $validationErrors = [], $attemptNumber = 1) {
        // Adjust configuration for retry
        $adjustedConfig = $this->config->adjustConstraintsForRetry($attemptNumber);
        
        // Create new engine with adjusted config
        $fallbackEngine = new self($adjustedConfig);
        
        // Build base prompt with relaxed constraints
        $prompt = $fallbackEngine->buildSEOPrompt($topic, $keywords, $wordCount, $this->internalCandidates);
        
        // Add specific error correction instructions
        if (!empty($validationErrors)) {
            $prompt .= $this->buildErrorCorrectionInstructions($validationErrors, $attemptNumber);
        }
        
        // Add progressive constraint relaxation notice
        $prompt .= $this->buildConstraintRelaxationNotice($attemptNumber);
        
        return $prompt;
    }
    
    /**
     * Build error correction instructions
     *
     * @param array $validationErrors Previous validation errors
     * @param int $attemptNumber Current attempt number
     * @return string Error correction instructions
     */
    private function buildErrorCorrectionInstructions($validationErrors, $attemptNumber) {
        $instructions = "\n\nERROR CORRECTION REQUIRED:\n\n";
        $instructions .= "The previous attempt failed validation. Please correct these specific issues:\n\n";
        
        foreach ($validationErrors as $error) {
            $instructions .= "- {$error}\n";
        }
        
        $instructions .= "\nATTEMPT #{$attemptNumber}: Focus specifically on fixing the above issues.\n";
        
        // Add specific guidance based on common errors
        if ($this->containsMetaDescriptionError($validationErrors)) {
            $instructions .= "\nMETA DESCRIPTION FIX:\n";
            $instructions .= "- Count characters carefully (aim for 130-150)\n";
            $instructions .= "- Include the exact keyword '{$this->config->focusKeyword}'\n";
            $instructions .= "- Make it compelling and actionable\n";
        }
        
        if ($this->containsKeywordDensityError($validationErrors)) {
            $instructions .= "\nKEYWORD DENSITY FIX:\n";
            $instructions .= "- Use keyword naturally 3-8 times in 800-word content\n";
            $instructions .= "- Include synonyms and variations\n";
            $instructions .= "- Ensure first paragraph contains exact keyword\n";
        }
        
        if ($this->containsReadabilityError($validationErrors)) {
            $instructions .= "\nREADABILITY FIX:\n";
            $instructions .= "- Use active voice in most sentences\n";
            $instructions .= "- Add transition words: however, therefore, moreover\n";
            $instructions .= "- Keep most sentences under 20 words\n";
        }
        
        $instructions .= "\n";
        
        return $instructions;
    }
    
    /**
     * Build constraint relaxation notice
     *
     * @param int $attemptNumber Current attempt number
     * @return string Relaxation notice
     */
    private function buildConstraintRelaxationNotice($attemptNumber) {
        $notice = "CONSTRAINT RELAXATION (Attempt #{$attemptNumber}):\n\n";
        
        if ($attemptNumber >= 2) {
            $notice .= "Some constraints have been relaxed for this attempt:\n";
            $notice .= "- Keyword density range slightly expanded\n";
            $notice .= "- Readability thresholds adjusted\n";
            $notice .= "- Meta description length tolerance increased\n";
        }
        
        if ($attemptNumber >= 3) {
            $notice .= "- Alt text keyword requirement relaxed\n";
            $notice .= "- Subheading keyword usage limit increased\n";
        }
        
        $notice .= "\nFocus on core SEO requirements while meeting these adjusted constraints.\n\n";
        
        return $notice;
    }
    
    /**
     * Initialize fallback prompt strategies
     */
    private function initializeFallbackStrategies() {
        $this->fallbackStrategies = [
            'simplified' => [
                'description' => 'Simplified prompt with basic SEO requirements',
                'constraints' => 'minimal',
                'priority' => ['meta_description', 'title', 'keyword_density']
            ],
            'keyword_focused' => [
                'description' => 'Focus primarily on keyword optimization',
                'constraints' => 'keyword_only',
                'priority' => ['keyword_density', 'title', 'first_paragraph']
            ],
            'readability_focused' => [
                'description' => 'Focus primarily on readability metrics',
                'constraints' => 'readability_only',
                'priority' => ['passive_voice', 'transition_words', 'sentence_length']
            ],
            'length_focused' => [
                'description' => 'Focus on meeting length requirements',
                'constraints' => 'length_only',
                'priority' => ['meta_description', 'title', 'word_count']
            ],
            'content_structure' => [
                'description' => 'Focus on content structure and organization',
                'constraints' => 'structure_only',
                'priority' => ['subheadings', 'paragraphs', 'internal_links']
            ],
            'minimal_seo' => [
                'description' => 'Absolute minimum SEO requirements only',
                'constraints' => 'absolute_minimal',
                'priority' => ['title', 'meta_description']
            ]
        ];
    }
    
    /**
     * Generate strategy-specific fallback prompt
     *
     * @param string $strategy Strategy name
     * @param string $topic Content topic
     * @param string $keywords Keywords
     * @param string $wordCount Word count target
     * @param array $validationErrors Previous validation errors
     * @return string Strategy-specific prompt
     */
    public function generateStrategyPrompt($strategy, $topic, $keywords, $wordCount, $validationErrors = []) {
        if (!isset($this->fallbackStrategies[$strategy])) {
            return $this->buildSEOPrompt($topic, $keywords, $wordCount, $this->internalCandidates);
        }
        
        $strategyConfig = $this->fallbackStrategies[$strategy];
        $prompt = $this->buildPromptHeader($topic, $this->config->focusKeyword, $wordCount);
        
        switch ($strategy) {
            case 'simplified':
                $prompt .= $this->buildSimplifiedConstraints();
                break;
            case 'keyword_focused':
                $prompt .= $this->buildKeywordFocusedConstraints();
                break;
            case 'readability_focused':
                $prompt .= $this->buildReadabilityFocusedConstraints();
                break;
            case 'length_focused':
                $prompt .= $this->buildLengthFocusedConstraints();
                break;
            case 'content_structure':
                $prompt .= $this->buildStructureFocusedConstraints();
                break;
            case 'minimal_seo':
                $prompt .= $this->buildMinimalSEOConstraints();
                break;
            default:
                $prompt .= $this->buildSEOConstraints();
        }
        
        $prompt .= $this->buildOutputFormat();
        
        if (!empty($this->internalCandidates)) {
            $prompt .= $this->buildInternalLinkGuidance();
        }
        
        return $prompt;
    }
    
    /**
     * Build simplified constraints for basic SEO
     *
     * @return string Simplified constraints
     */
    private function buildSimplifiedConstraints() {
        $constraints = "SIMPLIFIED SEO REQUIREMENTS:\n\n";
        $constraints .= "1. META DESCRIPTION: 120-156 characters, include keyword '{$this->config->focusKeyword}'\n";
        $constraints .= "2. TITLE: Under 66 characters, start with keyword '{$this->config->focusKeyword}'\n";
        $constraints .= "3. CONTENT: Include keyword naturally 2-5 times\n";
        $constraints .= "4. STRUCTURE: Use H2/H3 headings, 2-4 paragraphs per section\n";
        $constraints .= "5. LINKS: Include 1-2 internal links and 1 external link\n\n";
        return $constraints;
    }
    
    /**
     * Build keyword-focused constraints
     *
     * @return string Keyword-focused constraints
     */
    private function buildKeywordFocusedConstraints() {
        $constraints = "KEYWORD OPTIMIZATION FOCUS:\n\n";
        $constraints .= "PRIMARY GOAL: Optimize for keyword '{$this->config->focusKeyword}'\n\n";
        $constraints .= "1. KEYWORD PLACEMENT:\n";
        $constraints .= "   - Title: Must start with exact keyword\n";
        $constraints .= "   - First paragraph: Include exact keyword in first sentence\n";
        $constraints .= "   - Subheadings: Use keyword in 1-2 H2/H3 tags (not all)\n";
        $constraints .= "   - Content: Natural distribution, 0.5-2.5% density\n\n";
        $constraints .= "2. KEYWORD VARIATIONS:\n";
        if (!empty($this->config->secondaryKeywords)) {
            $constraints .= "   - Use these synonyms: " . implode(', ', $this->config->secondaryKeywords) . "\n";
        }
        $constraints .= "   - Include semantic variations naturally\n";
        $constraints .= "   - Avoid keyword stuffing\n\n";
        return $constraints;
    }
    
    /**
     * Build readability-focused constraints
     *
     * @return string Readability-focused constraints
     */
    private function buildReadabilityFocusedConstraints() {
        $constraints = "READABILITY OPTIMIZATION FOCUS:\n\n";
        $constraints .= "PRIMARY GOAL: Create highly readable, engaging content\n\n";
        $constraints .= "1. SENTENCE STRUCTURE:\n";
        $constraints .= "   - Use active voice in 90%+ of sentences\n";
        $constraints .= "   - Keep sentences under 20 words (80%+ of content)\n";
        $constraints .= "   - Vary sentence length for rhythm\n\n";
        $constraints .= "2. FLOW AND TRANSITIONS:\n";
        $constraints .= "   - Use transition words in 30%+ of sentences\n";
        $constraints .= "   - Examples: however, therefore, moreover, additionally\n";
        $constraints .= "   - Create logical flow between paragraphs\n\n";
        $constraints .= "3. CLARITY:\n";
        $constraints .= "   - Use simple, clear language\n";
        $constraints .= "   - Explain technical terms\n";
        $constraints .= "   - Include examples and analogies\n\n";
        return $constraints;
    }
    
    /**
     * Build length-focused constraints
     *
     * @return string Length-focused constraints
     */
    private function buildLengthFocusedConstraints() {
        $constraints = "LENGTH OPTIMIZATION FOCUS:\n\n";
        $constraints .= "PRIMARY GOAL: Meet exact length requirements\n\n";
        $constraints .= "1. META DESCRIPTION:\n";
        $constraints .= "   - EXACTLY 130-150 characters (count carefully)\n";
        $constraints .= "   - Include keyword '{$this->config->focusKeyword}'\n";
        $constraints .= "   - Make every character count\n\n";
        $constraints .= "2. TITLE:\n";
        $constraints .= "   - Maximum 60 characters (leave room for SEO)\n";
        $constraints .= "   - Start with keyword, be descriptive\n\n";
        $constraints .= "3. CONTENT LENGTH:\n";
        $constraints .= "   - Target: {$this->config->targetWordCount} words\n";
        $constraints .= "   - Use word count strategically\n";
        $constraints .= "   - Don't pad with fluff - add value\n\n";
        return $constraints;
    }
    
    /**
     * Build structure-focused constraints
     *
     * @return string Structure-focused constraints
     */
    private function buildStructureFocusedConstraints() {
        $constraints = "CONTENT STRUCTURE FOCUS:\n\n";
        $constraints .= "PRIMARY GOAL: Create well-organized, scannable content\n\n";
        $constraints .= "1. HEADING STRUCTURE:\n";
        $constraints .= "   - Use H2 for main sections\n";
        $constraints .= "   - Use H3 for subsections\n";
        $constraints .= "   - Make headings descriptive and keyword-relevant\n\n";
        $constraints .= "2. PARAGRAPH ORGANIZATION:\n";
        $constraints .= "   - 2-4 sentences per paragraph\n";
        $constraints .= "   - One main idea per paragraph\n";
        $constraints .= "   - Use bullet points for lists\n\n";
        $constraints .= "3. LINKING STRATEGY:\n";
        $constraints .= "   - 2+ internal links with descriptive anchors\n";
        $constraints .= "   - 1+ external link to authoritative source\n";
        $constraints .= "   - Integrate links naturally in content\n\n";
        return $constraints;
    }
    
    /**
     * Build minimal SEO constraints
     *
     * @return string Minimal SEO constraints
     */
    private function buildMinimalSEOConstraints() {
        $constraints = "MINIMAL SEO REQUIREMENTS:\n\n";
        $constraints .= "ONLY focus on these essential elements:\n\n";
        $constraints .= "1. TITLE: Include keyword '{$this->config->focusKeyword}', under 66 characters\n";
        $constraints .= "2. META DESCRIPTION: 120-156 characters, include keyword\n";
        $constraints .= "3. BASIC CONTENT: Well-written, informative, include keyword naturally\n";
        $constraints .= "4. SIMPLE STRUCTURE: Use some headings, readable paragraphs\n\n";
        $constraints .= "Don't worry about complex SEO metrics - focus on quality content.\n\n";
        return $constraints;
    }
    
    /**
     * Generate progressive fallback prompts with multiple strategies
     *
     * @param string $topic Content topic
     * @param string $keywords Keywords
     * @param string $wordCount Word count target
     * @param array $validationErrors Previous validation errors
     * @param int $attemptNumber Current attempt number
     * @return array Array of fallback prompts with different strategies
     */
    public function generateProgressiveFallbacks($topic, $keywords, $wordCount, $validationErrors = [], $attemptNumber = 1) {
        $fallbacks = [];
        
        // Strategy selection based on attempt number and error types
        $strategies = $this->selectStrategiesForErrors($validationErrors, $attemptNumber);
        
        foreach ($strategies as $strategy) {
            $fallbacks[$strategy] = $this->generateStrategyPrompt($strategy, $topic, $keywords, $wordCount, $validationErrors);
        }
        
        return $fallbacks;
    }
    
    /**
     * Select appropriate strategies based on validation errors
     *
     * @param array $validationErrors Previous validation errors
     * @param int $attemptNumber Current attempt number
     * @return array Selected strategies
     */
    private function selectStrategiesForErrors($validationErrors, $attemptNumber) {
        $strategies = [];
        
        // Analyze error types
        $hasMetaErrors = $this->containsMetaDescriptionError($validationErrors);
        $hasKeywordErrors = $this->containsKeywordDensityError($validationErrors);
        $hasReadabilityErrors = $this->containsReadabilityError($validationErrors);
        
        // Select strategies based on attempt number and error types
        if ($attemptNumber == 1) {
            if ($hasMetaErrors || $hasKeywordErrors) {
                $strategies[] = 'simplified';
            }
            if ($hasReadabilityErrors) {
                $strategies[] = 'readability_focused';
            }
            if ($hasKeywordErrors) {
                $strategies[] = 'keyword_focused';
            }
        } elseif ($attemptNumber == 2) {
            $strategies[] = 'length_focused';
            $strategies[] = 'content_structure';
        } else {
            // Final attempt - use minimal requirements
            $strategies[] = 'minimal_seo';
        }
        
        // Ensure we always have at least one strategy
        if (empty($strategies)) {
            $strategies[] = 'simplified';
        }
        
        return $strategies;
    }
    
    /**
     * Check if validation errors contain meta description issues
     *
     * @param array $errors Validation errors
     * @return bool True if meta description errors found
     */
    private function containsMetaDescriptionError($errors) {
        foreach ($errors as $error) {
            if (stripos($error, 'meta description') !== false || 
                stripos($error, 'meta_description') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if validation errors contain keyword density issues
     *
     * @param array $errors Validation errors
     * @return bool True if keyword density errors found
     */
    private function containsKeywordDensityError($errors) {
        foreach ($errors as $error) {
            if (stripos($error, 'keyword density') !== false || 
                stripos($error, 'keyphrase density') !== false ||
                stripos($error, 'keyword') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if validation errors contain readability issues
     *
     * @param array $errors Validation errors
     * @return bool True if readability errors found
     */
    private function containsReadabilityError($errors) {
        foreach ($errors as $error) {
            if (stripos($error, 'passive voice') !== false || 
                stripos($error, 'transition') !== false ||
                stripos($error, 'sentence') !== false ||
                stripos($error, 'readability') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get current configuration
     *
     * @return SEOPromptConfiguration Current configuration
     */
    public function getConfiguration() {
        return $this->config;
    }
    
    /**
     * Update configuration
     *
     * @param SEOPromptConfiguration $config New configuration
     */
    public function setConfiguration(SEOPromptConfiguration $config) {
        $this->config = $config;
    }
    
    /**
     * Set internal link candidates
     *
     * @param array $candidates Internal link candidates
     */
    public function setInternalCandidates($candidates) {
        $this->internalCandidates = $candidates ?: [];
    }
}