<?php
/**
 * SEO Validation Pipeline Class
 *
 * Comprehensive validation pipeline that integrates all SEO validation components
 * and provides auto-correction capabilities for generated content.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load required classes
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-result.php';
require_once ACS_PLUGIN_PATH . 'seo/class-content-validation-metrics.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-error-handler.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-cache.php';
require_once ACS_PLUGIN_PATH . 'seo/class-smart-retry-manager.php';
require_once ACS_PLUGIN_PATH . 'seo/class-meta-description-validator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-meta-description-corrector.php';
require_once ACS_PLUGIN_PATH . 'seo/class-keyword-density-calculator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-keyword-density-optimizer.php';
require_once ACS_PLUGIN_PATH . 'seo/class-passive-voice-analyzer.php';
require_once ACS_PLUGIN_PATH . 'seo/class-sentence-length-analyzer.php';
require_once ACS_PLUGIN_PATH . 'seo/class-transition-word-analyzer.php';
require_once ACS_PLUGIN_PATH . 'seo/class-readability-corrector.php';
require_once ACS_PLUGIN_PATH . 'seo/class-title-uniqueness-validator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-title-optimization-engine.php';
require_once ACS_PLUGIN_PATH . 'seo/class-image-prompt-generator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-alt-text-accessibility-optimizer.php';

/**
 * Class SEOValidationPipeline
 *
 * Comprehensive SEO validation and auto-correction pipeline
 */
class SEOValidationPipeline {
    
    /**
     * @var MetaDescriptionValidator
     */
    private $metaValidator;
    
    /**
     * @var MetaDescriptionCorrector
     */
    private $metaCorrector;
    
    /**
     * @var KeywordDensityCalculator
     */
    private $densityCalculator;
    
    /**
     * @var KeywordDensityOptimizer
     */
    private $densityOptimizer;
    
    /**
     * @var PassiveVoiceAnalyzer
     */
    private $passiveAnalyzer;
    
    /**
     * @var SentenceLengthAnalyzer
     */
    private $sentenceAnalyzer;
    
    /**
     * @var TransitionWordAnalyzer
     */
    private $transitionAnalyzer;
    
    /**
     * @var ReadabilityCorrector
     */
    private $readabilityCorrector;
    
    /**
     * @var TitleUniquenessValidator
     */
    private $titleValidator;
    
    /**
     * @var TitleOptimizationEngine
     */
    private $titleOptimizer;
    
    /**
     * @var ImagePromptGenerator
     */
    private $imageGenerator;
    
    /**
     * @var AltTextAccessibilityOptimizer
     */
    private $altTextOptimizer;
    
    /**
     * @var SEOErrorHandler
     */
    private $errorHandler;
    
    /**
     * @var SEOValidationCache
     */
    private $cache;
    
    /**
     * @var SmartRetryManager
     */
    private $retryManager;
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'minMetaDescLength' => 110,  // Reduced from 120 to allow slightly shorter descriptions
            'maxMetaDescLength' => 156,
            'minKeywordDensity' => 0.5,
            'maxKeywordDensity' => 3.0,  // Increased from 2.5 to 3.0 for more flexibility
            'maxPassiveVoice' => 15.0,   // Increased from 10.0 to 15.0 for more natural writing
            'maxLongSentences' => 25.0,
            'minTransitionWords' => 30.0,
            'maxTitleLength' => 66,
            'maxSubheadingKeywordUsage' => 75.0,
            'requireImages' => false,    // Changed to false - images are nice but not required
            'requireKeywordInAltText' => true,
            'autoCorrection' => true,
            'maxRetryAttempts' => 3
        ], $config);
        
        $this->initializeComponents();
        
        // Initialize performance optimization components
        $this->cache = new SEOValidationCache();
        $this->retryManager = new SmartRetryManager($this->config, $this->cache);
    }
    
    /**
     * Initialize all validation and correction components
     */
    private function initializeComponents() {
        $this->metaValidator = new MetaDescriptionValidator(
            $this->config['minMetaDescLength'], 
            $this->config['maxMetaDescLength'], 
            true
        );
        $this->metaCorrector = new MetaDescriptionCorrector(
            $this->config['minMetaDescLength'], 
            $this->config['maxMetaDescLength']
        );
        $this->densityCalculator = new Keyword_Density_Calculator();
        $this->densityOptimizer = new Keyword_Density_Optimizer();
        $this->passiveAnalyzer = new PassiveVoiceAnalyzer();
        $this->sentenceAnalyzer = new SentenceLengthAnalyzer();
        $this->transitionAnalyzer = new TransitionWordAnalyzer();
        $this->readabilityCorrector = new ReadabilityCorrector();
        $this->titleValidator = new Title_Uniqueness_Validator();
        $this->titleOptimizer = new Title_Optimization_Engine();
        $this->imageGenerator = new ImagePromptGenerator();
        $this->altTextOptimizer = new AltTextAccessibilityOptimizer();
        $this->errorHandler = new SEOErrorHandler();
    }
    
    /**
     * Validate and auto-correct generated content
     *
     * @param array $content Generated content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return SEOValidationResult Validation result with corrected content
     */
    public function validateAndCorrect($content, $focusKeyword, $secondaryKeywords = []) {
        // Generate cache keys
        $contentHash = $this->cache->generateContentHash($content);
        $configHash = $this->cache->generateConfigHash($this->config);
        $keywordHash = $this->cache->generateKeywordHash($focusKeyword, $secondaryKeywords);
        
        // Check cache first
        $cachedResult = $this->cache->getValidationResult($contentHash, $configHash);
        if ($cachedResult !== false) {
            return $this->deserializeValidationResult($cachedResult);
        }
        
        $result = new SEOValidationResult();
        $metrics = new ContentValidationMetrics();
        $correctedContent = $content;
        $correctionsMade = [];
        
        try {
            // Step 1: Validate meta description
            $metaResult = $this->validateMetaDescription($correctedContent, $focusKeyword, $metrics);
            if (!$metaResult['isValid'] && $this->config['autoCorrection']) {
                $beforeMeta = $correctedContent['meta_description'] ?? '';
                $correctedContent = $this->correctMetaDescription($correctedContent, $focusKeyword);
                $afterMeta = $correctedContent['meta_description'] ?? '';
                if ($beforeMeta !== $afterMeta) {
                    $correctionsMade[] = 'meta_description';
                    error_log('[ACS][SEO_CORRECTION] Meta description corrected: ' . strlen($beforeMeta) . ' -> ' . strlen($afterMeta) . ' chars');
                    // Re-validate after correction to get accurate error messages
                    $metaResult = $this->validateMetaDescription($correctedContent, $focusKeyword, $metrics);
                }
            }
            $this->addValidationResults($result, $metaResult, 'meta_description');
            
            // Step 2: Validate keyword density
            $densityResult = $this->validateKeywordDensity($correctedContent, $focusKeyword, $secondaryKeywords, $metrics);
            if (!$densityResult['isValid'] && $this->config['autoCorrection']) {
                $beforeDensity = $this->densityCalculator->calculate_density($correctedContent['content'] ?? '', $focusKeyword, $secondaryKeywords);
                $correctedContent = $this->correctKeywordDensity($correctedContent, $focusKeyword, $secondaryKeywords);
                $afterDensity = $this->densityCalculator->calculate_density($correctedContent['content'] ?? '', $focusKeyword, $secondaryKeywords);
                if (abs($beforeDensity['overall_density'] - $afterDensity['overall_density']) > 0.1) {
                    $correctionsMade[] = 'keyword_density';
                    error_log('[ACS][SEO_CORRECTION] Keyword density corrected: ' . round($beforeDensity['overall_density'], 2) . '% -> ' . round($afterDensity['overall_density'], 2) . '%');
                    // Re-validate after correction to get accurate error messages
                    $densityResult = $this->validateKeywordDensity($correctedContent, $focusKeyword, $secondaryKeywords, $metrics);
                }
            }
            $this->addValidationResults($result, $densityResult, 'keyword_density');
            
            // Step 3: Validate readability
            $readabilityResult = $this->validateReadability($correctedContent, $metrics);
            if (!$readabilityResult['isValid'] && $this->config['autoCorrection']) {
                $beforeContent = $correctedContent['content'] ?? '';
                $correctedContent = $this->correctReadability($correctedContent);
                $afterContent = $correctedContent['content'] ?? '';
                if ($beforeContent !== $afterContent) {
                    $correctionsMade[] = 'readability';
                    error_log('[ACS][SEO_CORRECTION] Readability corrected (passive voice reduced)');
                    // Re-validate after correction to get accurate error messages
                    $readabilityResult = $this->validateReadability($correctedContent, $metrics);
                }
            }
            $this->addValidationResults($result, $readabilityResult, 'readability');
            
            // Step 4: Validate title
            $titleResult = $this->validateTitle($correctedContent, $focusKeyword, $metrics);
            if (!$titleResult['isValid'] && $this->config['autoCorrection']) {
                $correctedContent = $this->correctTitle($correctedContent, $focusKeyword);
                $correctionsMade[] = 'title';
            }
            $this->addValidationResults($result, $titleResult, 'title');
            
            // Step 5: Validate images and alt text
            $imageResult = $this->validateImages($correctedContent, $focusKeyword, $metrics);
            if (!$imageResult['isValid'] && $this->config['autoCorrection']) {
                $correctedContent = $this->correctImages($correctedContent, $focusKeyword);
                $correctionsMade[] = 'images';
            }
            $this->addValidationResults($result, $imageResult, 'images');
            
            // Step 6: Final validation check
            $finalValidation = $this->performFinalValidation($correctedContent, $focusKeyword, $secondaryKeywords);
            $result->isValid = $finalValidation['isValid'];
            
            // Add corrected content and correction log
            $result->correctedContent = $correctedContent;
            $result->correctionsMade = $correctionsMade;
            $result->metrics = $metrics->toArray();
            
            // Calculate overall score
            $result->calculateScore();
            
            // Cache successful result
            $this->cache->setValidationResult($contentHash, $configHash, $this->serializeValidationResult($result));
            
        } catch (Exception $e) {
            $result->addError('Validation pipeline error: ' . $e->getMessage(), 'pipeline');
            
            // Log error with detailed context
            $this->errorHandler->logValidationFailure('pipeline', $e->getMessage(), [
                'content_title' => $content['title'] ?? 'Unknown',
                'focus_keyword' => $focusKeyword,
                'stack_trace' => $e->getTraceAsString()
            ], 'error');
        }
        
        return $result;
    }
    
    /**
     * Validate meta description with caching
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Validation result
     */
    private function validateMetaDescription($content, $focusKeyword, $metrics) {
        $metaDescription = $content['meta_description'] ?? '';
        
        // Check cache for meta validation
        $contentHash = $this->cache->generateContentHash(['meta_description' => $metaDescription]);
        $cached = $this->cache->getContentMetrics($contentHash);
        if ($cached !== false && isset($cached['meta_validation'])) {
            return $cached['meta_validation'];
        }
        
        $length = $metrics->checkMetaDescriptionLength($metaDescription);
        
        $lengthValidation = $this->metaValidator->validateLength($metaDescription);
        $isValid = $lengthValidation['isValid'];
        $hasKeyword = $this->metaValidator->validateKeywordInclusion($metaDescription, $focusKeyword);
        
        $errors = [];
        $warnings = [];
        
        if (!$isValid) {
            if ($length < $this->config['minMetaDescLength']) {
                $errors[] = "Meta description too short ({$length} chars, minimum {$this->config['minMetaDescLength']})";
            } elseif ($length > $this->config['maxMetaDescLength']) {
                $errors[] = "Meta description too long ({$length} chars, maximum {$this->config['maxMetaDescLength']})";
            }
        }
        
        if (!$hasKeyword) {
            $warnings[] = "Meta description should include focus keyword: {$focusKeyword}";
        }
        
        $result = [
            'isValid' => $isValid && $hasKeyword,
            'errors' => $errors,
            'warnings' => $warnings
        ];
        
        // Cache result for 30 minutes
        $this->cache->setContentMetrics($contentHash, ['meta_validation' => $result], 1800);
        
        return $result;
    }
    
    /**
     * Correct meta description
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @return array Corrected content
     */
    private function correctMetaDescription($content, $focusKeyword) {
        $metaDescription = $content['meta_description'] ?? '';
        
        // Use corrector to fix length and keyword inclusion
        $correctionResult = $this->metaCorrector->autoCorrect($metaDescription, $focusKeyword, [], '');
        $corrected = $correctionResult['corrected'] ?? $metaDescription;
        
        $content['meta_description'] = $corrected;
        return $content;
    }
    
    /**
     * Validate keyword density with caching
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Validation result
     */
    private function validateKeywordDensity($content, $focusKeyword, $secondaryKeywords, $metrics) {
        $contentText = strip_tags($content['content'] ?? '');
        $contentHash = md5($contentText);
        $keywordHash = $this->cache->generateKeywordHash($focusKeyword, $secondaryKeywords);
        
        // Check cache for keyword analysis
        $cachedAnalysis = $this->cache->getKeywordAnalysis($contentHash, $keywordHash);
        if ($cachedAnalysis !== false) {
            return $this->processKeywordAnalysis($cachedAnalysis);
        }
        
        $density = $metrics->calculateKeywordDensity($contentText, $focusKeyword, $secondaryKeywords);
        $subheadingUsage = $metrics->calculateSubheadingKeywordUsage($content['content'] ?? '', $focusKeyword);
        
        $analysis = [
            'density' => $density,
            'subheading_usage' => $subheadingUsage,
            'content_length' => strlen($contentText),
            'keyword_count' => substr_count(strtolower($contentText), strtolower($focusKeyword))
        ];
        
        // Cache analysis
        $this->cache->setKeywordAnalysis($contentHash, $keywordHash, $analysis);
        
        return $this->processKeywordAnalysis($analysis);
    }
    
    /**
     * Process keyword analysis into validation result
     *
     * @param array $analysis Keyword analysis data
     * @return array Validation result
     */
    private function processKeywordAnalysis($analysis) {
        $errors = [];
        $warnings = [];
        
        if ($analysis['density'] < $this->config['minKeywordDensity']) {
            $errors[] = "Keyword density too low ({$analysis['density']}%, minimum {$this->config['minKeywordDensity']}%)";
        } elseif ($analysis['density'] > $this->config['maxKeywordDensity']) {
            $errors[] = "Keyword density too high ({$analysis['density']}%, maximum {$this->config['maxKeywordDensity']}%)";
        }
        
        if ($analysis['subheading_usage'] > $this->config['maxSubheadingKeywordUsage']) {
            $warnings[] = "Too many subheadings contain keyword ({$analysis['subheading_usage']}%, maximum {$this->config['maxSubheadingKeywordUsage']}%)";
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Correct keyword density
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return array Corrected content
     */
    private function correctKeywordDensity($content, $focusKeyword, $secondaryKeywords) {
        $contentText = $content['content'] ?? '';
        
        // Calculate current density
        $currentDensity = $this->densityCalculator->calculate_density($contentText, $focusKeyword, $secondaryKeywords);
        
        if ($currentDensity['overall_density'] < $this->config['minKeywordDensity']) {
            // Optimize keyword density
            $optimizationResult = $this->densityOptimizer->optimize_content(
                $contentText, 
                $focusKeyword, 
                $secondaryKeywords,
                $this->config['minKeywordDensity'],
                $this->config['minKeywordDensity'],
                $this->config['maxKeywordDensity']
            );
            $content['content'] = $optimizationResult['content'];
        } elseif ($currentDensity['overall_density'] > $this->config['maxKeywordDensity']) {
            // Optimize keyword density
            $optimizationResult = $this->densityOptimizer->optimize_content(
                $contentText, 
                $focusKeyword, 
                $secondaryKeywords,
                $this->config['maxKeywordDensity'],
                $this->config['minKeywordDensity'],
                $this->config['maxKeywordDensity']
            );
            $content['content'] = $optimizationResult['content'];
        }
        
        return $content;
    }
    
    /**
     * Validate readability with caching
     *
     * @param array $content Content array
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Validation result
     */
    private function validateReadability($content, $metrics) {
        $contentText = strip_tags($content['content'] ?? '');
        $contentHash = md5($contentText);
        
        // Check cache for readability analysis
        $cachedAnalysis = $this->cache->getReadabilityAnalysis($contentHash);
        if ($cachedAnalysis !== false) {
            return $this->processReadabilityAnalysis($cachedAnalysis);
        }
        
        $passiveVoice = $metrics->calculatePassiveVoice($contentText);
        $longSentences = $metrics->calculateLongSentences($contentText);
        $transitionWords = $metrics->calculateTransitionWords($contentText);
        
        $analysis = [
            'passive_voice' => $passiveVoice,
            'long_sentences' => $longSentences,
            'transition_words' => $transitionWords,
            'sentence_count' => substr_count($contentText, '.') + substr_count($contentText, '!') + substr_count($contentText, '?'),
            'word_count' => str_word_count($contentText)
        ];
        
        // Cache analysis
        $this->cache->setReadabilityAnalysis($contentHash, $analysis);
        
        return $this->processReadabilityAnalysis($analysis);
    }
    
    /**
     * Process readability analysis into validation result
     *
     * @param array $analysis Readability analysis data
     * @return array Validation result
     */
    private function processReadabilityAnalysis($analysis) {
        $errors = [];
        $warnings = [];
        
        if ($analysis['passive_voice'] > $this->config['maxPassiveVoice']) {
            $errors[] = "Too much passive voice ({$analysis['passive_voice']}%, maximum {$this->config['maxPassiveVoice']}%)";
        }
        
        if ($analysis['long_sentences'] > $this->config['maxLongSentences']) {
            $errors[] = "Too many long sentences ({$analysis['long_sentences']}%, maximum {$this->config['maxLongSentences']}%)";
        }
        
        if ($analysis['transition_words'] < $this->config['minTransitionWords']) {
            $warnings[] = "Not enough transition words ({$analysis['transition_words']}%, minimum {$this->config['minTransitionWords']}%)";
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Correct readability issues
     *
     * @param array $content Content array
     * @return array Corrected content
     */
    private function correctReadability($content) {
        $contentText = $content['content'] ?? '';
        
        // Apply readability corrections
        $correctionResult = $this->readabilityCorrector->correctReadability($contentText);
        
        // Handle both return formats: 'corrected_content' or 'content'
        $correctedText = $correctionResult['corrected_content'] ?? $correctionResult['content'] ?? $contentText;
        
        if ($correctedText !== $contentText) {
            $content['content'] = $correctedText;
            error_log('[ACS][SEO_CORRECTION] Readability corrected - passive voice and/or long sentences fixed');
            if (!empty($correctionResult['changes_made'])) {
                error_log('[ACS][SEO_CORRECTION] Changes: ' . json_encode($correctionResult['changes_made']));
            }
        } else {
            error_log('[ACS][SEO_CORRECTION] Readability correction attempted but no changes made');
        }
        
        return $content;
    }
    
    /**
     * Validate title
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Validation result
     */
    private function validateTitle($content, $focusKeyword, $metrics) {
        $title = $content['title'] ?? '';
        $titleMetrics = $metrics->analyzeTitle($title, $focusKeyword);
        
        $uniquenessResult = $this->titleValidator->validate_title_uniqueness($title);
        $isUnique = $uniquenessResult['is_unique'];
        
        $errors = [];
        $warnings = [];
        
        if ($titleMetrics['length'] > $this->config['maxTitleLength']) {
            $errors[] = "Title too long ({$titleMetrics['length']} chars, maximum {$this->config['maxTitleLength']})";
        }
        
        if (!$titleMetrics['containsKeyword']) {
            $errors[] = "Title should contain focus keyword: {$focusKeyword}";
        }
        
        if (!$isUnique) {
            $warnings[] = "Title may not be unique";
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Correct title
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @return array Corrected content
     */
    private function correctTitle($content, $focusKeyword) {
        $title = $content['title'] ?? '';
        
        // Optimize title
        $optimized = $this->titleOptimizer->generate_optimized_title([
            'focus_keyword' => $focusKeyword,
            'max_length' => $this->config['maxTitleLength'],
            'current_title' => $title
        ]);
        $content['title'] = $optimized['title'] ?? $title;
        
        return $content;
    }
    
    /**
     * Validate images and alt text
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Validation result
     */
    private function validateImages($content, $focusKeyword, $metrics) {
        $contentText = $content['content'] ?? '';
        $imageAnalysis = $metrics->analyzeImages($contentText, $focusKeyword);
        
        $errors = [];
        $warnings = [];
        
        if ($this->config['requireImages'] && !$imageAnalysis['hasImages']) {
            $errors[] = "Content should include at least one image";
        }
        
        if ($this->config['requireKeywordInAltText'] && $imageAnalysis['hasImages'] && !$imageAnalysis['hasProperAltText']) {
            $warnings[] = "Image alt text should include focus keyword";
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Correct images and alt text
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @return array Corrected content
     */
    private function correctImages($content, $focusKeyword) {
        // Generate image prompts if missing
        if (empty($content['image_prompts'])) {
            $imagePrompt = $this->imageGenerator->generateImagePrompt($content['title'] ?? '', $focusKeyword);
            $imagePrompts = [$imagePrompt];
            $content['image_prompts'] = $imagePrompts;
        }
        
        // Optimize alt text
        if (!empty($content['image_prompts'])) {
            foreach ($content['image_prompts'] as &$imagePrompt) {
                if (isset($imagePrompt['alt'])) {
                    $imagePrompt['alt'] = $this->altTextOptimizer->optimizeAltText($imagePrompt['alt'], $focusKeyword);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Perform final validation check
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return array Final validation result
     */
    private function performFinalValidation($content, $focusKeyword, $secondaryKeywords) {
        $metrics = new ContentValidationMetrics();
        
        // Re-validate all components
        $metaValid = $this->validateMetaDescription($content, $focusKeyword, $metrics)['isValid'];
        $densityValid = $this->validateKeywordDensity($content, $focusKeyword, $secondaryKeywords, $metrics)['isValid'];
        $readabilityValid = $this->validateReadability($content, $metrics)['isValid'];
        $titleValid = $this->validateTitle($content, $focusKeyword, $metrics)['isValid'];
        $imageValid = $this->validateImages($content, $focusKeyword, $metrics)['isValid'];
        
        return [
            'isValid' => $metaValid && $densityValid && $readabilityValid && $titleValid && $imageValid,
            'components' => [
                'meta_description' => $metaValid,
                'keyword_density' => $densityValid,
                'readability' => $readabilityValid,
                'title' => $titleValid,
                'images' => $imageValid
            ]
        ];
    }
    
    /**
     * Add validation results to main result object
     *
     * @param SEOValidationResult $result Main result object
     * @param array $componentResult Component validation result
     * @param string $component Component name
     */
    private function addValidationResults($result, $componentResult, $component) {
        foreach ($componentResult['errors'] as $error) {
            $result->addError($error, $component);
            
            // Log error with context
            $this->errorHandler->logValidationFailure($component, $error, [
                'validation_step' => 'component_validation',
                'auto_correction' => $this->config['autoCorrection']
            ], 'error');
        }
        
        foreach ($componentResult['warnings'] as $warning) {
            $result->addWarning($warning, $component);
            
            // Log warning with context
            $this->errorHandler->logValidationFailure($component, $warning, [
                'validation_step' => 'component_validation',
                'auto_correction' => $this->config['autoCorrection']
            ], 'warning');
        }
    }
    
    /**
     * Get validation configuration
     *
     * @return array Current configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Update validation configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        
        // Log configuration update
        $this->errorHandler->logValidationFailure('configuration', 'Validation config updated', [
            'old_config' => $this->config,
            'new_config' => $newConfig
        ], 'info');
    }
    
    /**
     * Get error handler instance
     *
     * @return SEOErrorHandler Error handler
     */
    public function getErrorHandler() {
        return $this->errorHandler;
    }
    
    /**
     * Add manual override for persistent validation issues
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param array $override Override configuration
     * @return bool Success status
     */
    public function addManualOverride($component, $error, $override) {
        return $this->errorHandler->addManualOverride($component, $error, $override);
    }
    
    /**
     * Check if validation should be skipped due to manual override
     *
     * @param string $component Component name
     * @param string $error Error message
     * @return bool True if validation should be skipped
     */
    private function shouldSkipValidation($component, $error) {
        $override = $this->errorHandler->getManualOverride($component, $error);
        return $override !== false && isset($override['skip_validation']) && $override['skip_validation'];
    }
    
    /**
     * Update adaptive rules based on error patterns
     *
     * @param array $newRules New rule configuration
     * @return bool Success status
     */
    public function updateAdaptiveRules($newRules) {
        $success = $this->errorHandler->updateAdaptiveRules($newRules);
        
        if ($success) {
            // Update pipeline configuration with new rules
            $adaptiveRules = $this->errorHandler->getAdaptiveRules();
            $this->updateConfig($adaptiveRules);
        }
        
        return $success;
    }
    
    /**
     * Get error statistics for monitoring
     *
     * @param string $component Optional component filter
     * @param int $days Number of days to include
     * @return array Error statistics
     */
    public function getErrorStats($component = null, $days = 30) {
        return $this->errorHandler->getErrorStats($component, $days);
    }
    
    /**
     * Export validation logs for analysis
     *
     * @param string $format Export format (json, csv)
     * @param int $days Number of days to include
     * @return string|false Exported data or false on failure
     */
    public function exportValidationLogs($format = 'json', $days = 30) {
        return $this->errorHandler->exportLogs($format, $days);
    }
    
    /**
     * Serialize validation result for caching
     *
     * @param SEOValidationResult $result Validation result
     * @return array Serialized result
     */
    private function serializeValidationResult($result) {
        return [
            'isValid' => $result->isValid,
            'errors' => $result->errors,
            'warnings' => $result->warnings,
            'suggestions' => $result->suggestions,
            'overallScore' => $result->overallScore,
            'correctedContent' => $result->correctedContent,
            'correctionsMade' => $result->correctionsMade,
            'metrics' => $result->metrics
        ];
    }
    
    /**
     * Deserialize validation result from cache
     *
     * @param array $data Serialized result data
     * @return SEOValidationResult Validation result
     */
    private function deserializeValidationResult($data) {
        $result = new SEOValidationResult();
        $result->isValid = $data['isValid'] ?? false;
        $result->errors = $data['errors'] ?? [];
        $result->warnings = $data['warnings'] ?? [];
        $result->suggestions = $data['suggestions'] ?? [];
        $result->overallScore = $data['overallScore'] ?? 0;
        $result->correctedContent = $data['correctedContent'] ?? [];
        $result->correctionsMade = $data['correctionsMade'] ?? [];
        $result->metrics = $data['metrics'] ?? [];
        
        return $result;
    }
    
    /**
     * Get performance statistics
     *
     * @return array Performance statistics
     */
    public function getPerformanceStats() {
        $cacheStats = $this->cache->getStats();
        $retryStats = $this->retryManager->getRetryStats();
        $cacheSize = $this->cache->getCacheSize();
        
        return [
            'cache' => $cacheStats,
            'retry' => $retryStats,
            'cache_size' => $cacheSize,
            'optimization_enabled' => true
        ];
    }
    
    /**
     * Clear performance cache
     *
     * @return bool Success status
     */
    public function clearPerformanceCache() {
        return $this->cache->clearAll();
    }
    
    /**
     * Warm up cache with common content patterns
     *
     * @param array $commonContent Array of common content to pre-cache
     */
    public function warmUpCache($commonContent = []) {
        if (empty($commonContent)) {
            // Generate some common content patterns for warming up
            $commonContent = [
                [
                    'title' => 'Sample Blog Post Title',
                    'content' => str_repeat('This is sample content for cache warming. ', 100),
                    'meta_description' => 'This is a sample meta description for cache warming purposes.'
                ],
                [
                    'title' => 'Another Sample Title',
                    'content' => str_repeat('Different sample content for testing. ', 150),
                    'meta_description' => 'Another sample meta description for testing cache performance.'
                ]
            ];
        }
        
        $this->cache->warmUpCache($commonContent, $this->config);
    }
    
    /**
     * Optimize validation pipeline for performance
     *
     * @param array $optimizationConfig Optimization configuration
     */
    public function optimizePerformance($optimizationConfig = []) {
        $config = array_merge([
            'enable_aggressive_caching' => true,
            'cache_expiration_multiplier' => 2,
            'enable_batch_processing' => true,
            'parallel_validation' => false // Future enhancement
        ], $optimizationConfig);
        
        if ($config['enable_aggressive_caching']) {
            // Note: Cache expiration optimization would be handled internally by cache class
            // This is a placeholder for future cache optimization features
        }
        
        if ($config['enable_batch_processing']) {
            // Enable batch processing for multiple validations
            $this->config['batch_processing'] = true;
        }
        
        // Note: Retry manager optimization would be handled through constructor parameters
        // This is a placeholder for future retry optimization features
    }
}