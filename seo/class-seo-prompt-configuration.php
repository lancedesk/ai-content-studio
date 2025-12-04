<?php
/**
 * SEO Prompt Configuration Class
 *
 * Manages configuration parameters for SEO-optimized content generation.
 * Handles dynamic constraint adjustment and validation requirements.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SEOPromptConfiguration
 *
 * Configuration management for SEO parameters and constraints
 */
class SEOPromptConfiguration {
    
    /**
     * @var int Target word count for generated content
     */
    public $targetWordCount;
    
    /**
     * @var string Primary focus keyword
     */
    public $focusKeyword;
    
    /**
     * @var array Secondary keywords and synonyms
     */
    public $secondaryKeywords;
    
    /**
     * @var int Minimum meta description length
     */
    public $minMetaDescLength = 120;
    
    /**
     * @var int Maximum meta description length
     */
    public $maxMetaDescLength = 156;
    
    /**
     * @var float Maximum keyword density percentage
     */
    public $maxKeywordDensity = 2.5;
    
    /**
     * @var float Minimum keyword density percentage
     */
    public $minKeywordDensity = 0.5;
    
    /**
     * @var float Maximum passive voice percentage
     */
    public $maxPassiveVoice = 10.0;
    
    /**
     * @var float Minimum transition words percentage
     */
    public $minTransitionWords = 30.0;
    
    /**
     * @var float Maximum long sentences percentage
     */
    public $maxLongSentences = 25.0;
    
    /**
     * @var int Maximum title length in characters
     */
    public $maxTitleLength = 66;
    
    /**
     * @var float Maximum keyword usage in subheadings percentage
     */
    public $maxSubheadingKeywordUsage = 75.0;
    
    /**
     * @var bool Whether to require images in content
     */
    public $requireImages = true;
    
    /**
     * @var bool Whether to require keyword in alt text
     */
    public $requireKeywordInAltText = true;
    
    /**
     * @var int Maximum retry attempts for validation failures
     */
    public $maxRetryAttempts = 3;
    
    /**
     * @var array Custom validation rules
     */
    public $customRules = [];
    
    /**
     * Constructor
     *
     * @param array $config Configuration parameters
     */
    public function __construct($config = []) {
        $this->targetWordCount = $config['targetWordCount'] ?? 800;
        $this->focusKeyword = $config['focusKeyword'] ?? '';
        $this->secondaryKeywords = $config['secondaryKeywords'] ?? [];
        
        // Override defaults with provided config
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        $this->validateConfiguration();
    }
    
    /**
     * Validate configuration parameters
     *
     * @throws InvalidArgumentException If configuration is invalid
     */
    public function validateConfiguration() {
        if (empty($this->focusKeyword)) {
            throw new InvalidArgumentException('Focus keyword is required');
        }
        
        if ($this->targetWordCount < 100) {
            throw new InvalidArgumentException('Target word count must be at least 100');
        }
        
        if ($this->minMetaDescLength >= $this->maxMetaDescLength) {
            throw new InvalidArgumentException('Min meta description length must be less than max length');
        }
        
        if ($this->minKeywordDensity >= $this->maxKeywordDensity) {
            throw new InvalidArgumentException('Min keyword density must be less than max density');
        }
        
        if ($this->maxPassiveVoice < 0 || $this->maxPassiveVoice > 100) {
            throw new InvalidArgumentException('Max passive voice must be between 0 and 100');
        }
        
        if ($this->minTransitionWords < 0 || $this->minTransitionWords > 100) {
            throw new InvalidArgumentException('Min transition words must be between 0 and 100');
        }
        
        if ($this->maxTitleLength < 10) {
            throw new InvalidArgumentException('Max title length must be at least 10 characters');
        }
    }
    
    /**
     * Get meta description constraints
     *
     * @return array Meta description length constraints
     */
    public function getMetaDescriptionConstraints() {
        return [
            'minLength' => $this->minMetaDescLength,
            'maxLength' => $this->maxMetaDescLength,
            'requireKeyword' => true,
            'allowSynonyms' => !empty($this->secondaryKeywords)
        ];
    }
    
    /**
     * Get keyword density constraints
     *
     * @return array Keyword density constraints
     */
    public function getKeywordDensityConstraints() {
        return [
            'minDensity' => $this->minKeywordDensity,
            'maxDensity' => $this->maxKeywordDensity,
            'includeSynonyms' => true,
            'maxSubheadingUsage' => $this->maxSubheadingKeywordUsage,
            'synonyms' => $this->secondaryKeywords
        ];
    }
    
    /**
     * Get readability constraints
     *
     * @return array Readability constraints
     */
    public function getReadabilityConstraints() {
        return [
            'maxPassiveVoice' => $this->maxPassiveVoice,
            'minTransitionWords' => $this->minTransitionWords,
            'maxLongSentences' => $this->maxLongSentences,
            'autoCorrect' => true
        ];
    }
    
    /**
     * Get title constraints
     *
     * @return array Title constraints
     */
    public function getTitleConstraints() {
        return [
            'maxLength' => $this->maxTitleLength,
            'requireKeyword' => true,
            'ensureUniqueness' => true,
            'allowSynonyms' => !empty($this->secondaryKeywords)
        ];
    }
    
    /**
     * Get image constraints
     *
     * @return array Image constraints
     */
    public function getImageConstraints() {
        return [
            'requireImages' => $this->requireImages,
            'requireAltText' => true,
            'requireKeywordInAltText' => $this->requireKeywordInAltText,
            'minAltTextLength' => 10,
            'allowSynonymsInAltText' => !empty($this->secondaryKeywords)
        ];
    }
    
    /**
     * Adjust constraints dynamically based on retry attempts
     *
     * @param int $attemptNumber Current retry attempt number
     * @return SEOPromptConfiguration New configuration with relaxed constraints
     */
    public function adjustConstraintsForRetry($attemptNumber) {
        $newConfig = clone $this;
        
        // Progressively relax constraints with each retry
        $relaxationFactor = $attemptNumber * 0.1; // 10% relaxation per attempt
        
        // Relax keyword density constraints
        $newConfig->minKeywordDensity = max(0.3, $this->minKeywordDensity - $relaxationFactor);
        $newConfig->maxKeywordDensity = min(3.0, $this->maxKeywordDensity + $relaxationFactor);
        
        // Relax readability constraints
        $newConfig->maxPassiveVoice = min(15.0, $this->maxPassiveVoice + ($relaxationFactor * 10));
        $newConfig->minTransitionWords = max(20.0, $this->minTransitionWords - ($relaxationFactor * 10));
        $newConfig->maxLongSentences = min(35.0, $this->maxLongSentences + ($relaxationFactor * 10));
        
        // Relax meta description constraints slightly
        $newConfig->minMetaDescLength = max(100, $this->minMetaDescLength - ($attemptNumber * 5));
        $newConfig->maxMetaDescLength = min(170, $this->maxMetaDescLength + ($attemptNumber * 5));
        
        // After 2 attempts, allow more flexibility
        if ($attemptNumber >= 2) {
            $newConfig->requireKeywordInAltText = false;
            $newConfig->maxSubheadingKeywordUsage = 85.0;
        }
        
        return $newConfig;
    }
    
    /**
     * Add custom validation rule
     *
     * @param string $name Rule name
     * @param callable $validator Validation function
     * @param array $params Rule parameters
     */
    public function addCustomRule($name, $validator, $params = []) {
        $this->customRules[$name] = [
            'validator' => $validator,
            'params' => $params,
            'enabled' => true
        ];
    }
    
    /**
     * Remove custom validation rule
     *
     * @param string $name Rule name to remove
     */
    public function removeCustomRule($name) {
        unset($this->customRules[$name]);
    }
    
    /**
     * Get all custom rules
     *
     * @return array Custom validation rules
     */
    public function getCustomRules() {
        return array_filter($this->customRules, function($rule) {
            return $rule['enabled'];
        });
    }
    
    /**
     * Generate prompt constraints string for AI model
     *
     * @return string Formatted constraints for prompt inclusion
     */
    public function generatePromptConstraints() {
        $constraints = [];
        
        // Meta description constraints
        $constraints[] = sprintf(
            "Meta description must be %d-%d characters and include the keyword '%s'",
            $this->minMetaDescLength,
            $this->maxMetaDescLength,
            $this->focusKeyword
        );
        
        // Keyword density constraints
        $constraints[] = sprintf(
            "Keyword density must be %.1f%%-%.1f%% (including synonyms: %s)",
            $this->minKeywordDensity,
            $this->maxKeywordDensity,
            implode(', ', $this->secondaryKeywords)
        );
        
        // Readability constraints
        $constraints[] = sprintf(
            "Use passive voice in less than %.0f%% of sentences",
            $this->maxPassiveVoice
        );
        
        $constraints[] = sprintf(
            "Include transition words in at least %.0f%% of sentences",
            $this->minTransitionWords
        );
        
        $constraints[] = sprintf(
            "Keep sentences under 20 words (max %.0f%% can exceed this)",
            $this->maxLongSentences
        );
        
        // Title constraints
        $constraints[] = sprintf(
            "Title must be under %d characters and include the keyword '%s'",
            $this->maxTitleLength,
            $this->focusKeyword
        );
        
        // Image constraints
        if ($this->requireImages) {
            $keywordRequirement = $this->requireKeywordInAltText ? 
                " and include the keyword '{$this->focusKeyword}'" : "";
            $constraints[] = "Include at least one relevant image with descriptive alt text{$keywordRequirement}";
        }
        
        // Subheading constraints
        $constraints[] = sprintf(
            "Use the keyword in no more than %.0f%% of subheadings",
            $this->maxSubheadingKeywordUsage
        );
        
        return implode("\n- ", $constraints);
    }
    
    /**
     * Convert configuration to array
     *
     * @return array Configuration as array
     */
    public function toArray() {
        return [
            'targetWordCount' => $this->targetWordCount,
            'focusKeyword' => $this->focusKeyword,
            'secondaryKeywords' => $this->secondaryKeywords,
            'minMetaDescLength' => $this->minMetaDescLength,
            'maxMetaDescLength' => $this->maxMetaDescLength,
            'maxKeywordDensity' => $this->maxKeywordDensity,
            'minKeywordDensity' => $this->minKeywordDensity,
            'maxPassiveVoice' => $this->maxPassiveVoice,
            'minTransitionWords' => $this->minTransitionWords,
            'maxLongSentences' => $this->maxLongSentences,
            'maxTitleLength' => $this->maxTitleLength,
            'maxSubheadingKeywordUsage' => $this->maxSubheadingKeywordUsage,
            'requireImages' => $this->requireImages,
            'requireKeywordInAltText' => $this->requireKeywordInAltText,
            'maxRetryAttempts' => $this->maxRetryAttempts,
            'customRules' => $this->customRules
        ];
    }
    
    /**
     * Create configuration from array
     *
     * @param array $data Configuration data
     * @return SEOPromptConfiguration
     */
    public static function fromArray($data) {
        return new self($data);
    }
    
    /**
     * Create default configuration for WordPress content
     *
     * @param string $focusKeyword The focus keyword
     * @return SEOPromptConfiguration
     */
    public static function createDefault($focusKeyword) {
        return new self([
            'focusKeyword' => $focusKeyword,
            'targetWordCount' => 800,
            'secondaryKeywords' => [],
            'minMetaDescLength' => 120,
            'maxMetaDescLength' => 156,
            'maxKeywordDensity' => 2.5,
            'minKeywordDensity' => 0.5,
            'maxPassiveVoice' => 10.0,
            'minTransitionWords' => 30.0,
            'maxLongSentences' => 25.0,
            'maxTitleLength' => 66,
            'maxSubheadingKeywordUsage' => 75.0,
            'requireImages' => true,
            'requireKeywordInAltText' => true,
            'maxRetryAttempts' => 3
        ]);
    }
}