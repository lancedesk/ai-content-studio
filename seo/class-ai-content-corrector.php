<?php
/**
 * AI Content Corrector
 *
 * Integrates with AI providers to apply targeted SEO corrections based on
 * specific correction prompts. Implements multi-provider support, failover,
 * retry mechanisms, and correction quality validation.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class AIContentCorrector
 *
 * Applies AI-powered corrections to content based on targeted prompts
 */
class AIContentCorrector {
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var array Available AI providers
     */
    private $providers;
    
    /**
     * @var array Provider order for failover
     */
    private $providerOrder;
    
    /**
     * @var array Correction history
     */
    private $correctionHistory;
    
    /**
     * @var array Error log
     */
    private $errorLog;
    
    /**
     * @var int Current retry attempt
     */
    private $currentRetry;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'maxRetryAttempts' => 3,
            'enableProviderFailover' => true,
            'enableCorrectionValidation' => true,
            'timeout' => 30,
            'minImprovementThreshold' => 5.0, // Minimum improvement percentage to consider correction successful
            'logLevel' => 'info'
        ], $config);
        
        $this->providers = [];
        $this->providerOrder = [];
        $this->correctionHistory = [];
        $this->errorLog = [];
        $this->currentRetry = 0;
        
        $this->initializeProviders();
    }
    
    /**
     * Initialize available AI providers
     */
    private function initializeProviders() {
        // Get WordPress settings
        $settings = get_option('acs_settings', []);
        
        // Determine provider order from settings
        $defaultProvider = $settings['default_provider'] ?? 'groq';
        $backupProviders = $settings['backup_providers'] ?? [];
        
        $this->providerOrder = array_values(array_unique(array_merge([$defaultProvider], $backupProviders)));
        
        // Map provider keys to class names
        $providerMap = [
            'groq' => 'ACS_Groq',
            'openai' => 'ACS_OpenAI',
            'anthropic' => 'ACS_Anthropic',
            'mock' => 'ACS_Mock'
        ];
        
        // Load and instantiate providers
        foreach ($this->providerOrder as $providerKey) {
            if (empty($providerKey)) {
                continue;
            }
            
            $className = $providerMap[$providerKey] ?? null;
            if (!$className) {
                continue;
            }
            
            // Require provider file if class not loaded
            if (!class_exists($className)) {
                $file = ACS_PLUGIN_PATH . 'api/providers/class-acs-' . $providerKey . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
            
            if (!class_exists($className)) {
                continue;
            }
            
            // Get API key from settings
            $apiKey = $settings['providers'][$providerKey]['api_key'] ?? '';
            $enabled = $settings['providers'][$providerKey]['enabled'] ?? false;
            
            if ($enabled && !empty($apiKey)) {
                try {
                    $this->providers[$providerKey] = new $className($apiKey);
                    $this->logMessage("Initialized provider: {$providerKey}", 'info');
                } catch (Exception $e) {
                    $this->logMessage("Failed to initialize provider {$providerKey}: " . $e->getMessage(), 'error');
                }
            }
        }
    }
    
    /**
     * Apply corrections to content based on correction prompts
     *
     * @param array $content Current content array
     * @param array $correctionPrompts Array of CorrectionPrompt objects
     * @param string $focusKeyword Focus keyword for validation
     * @return array Result with corrected content and metadata
     */
    public function applyCorrections($content, $correctionPrompts, $focusKeyword = '') {
        $this->logMessage("Applying " . count($correctionPrompts) . " corrections", 'info');
        
        if (empty($correctionPrompts)) {
            return [
                'success' => true,
                'content' => $content,
                'correctionsApplied' => 0,
                'message' => 'No corrections needed'
            ];
        }
        
        // Store original content for comparison
        $originalContent = $content;
        $currentContent = $content;
        $correctionsApplied = 0;
        $failedCorrections = [];
        
        // Group prompts by priority
        $sortedPrompts = $this->sortPromptsByPriority($correctionPrompts);
        
        // Apply corrections sequentially
        foreach ($sortedPrompts as $prompt) {
            $this->logMessage("Applying correction for: {$prompt->issueType}", 'info');
            
            $correctionResult = $this->applySingleCorrection(
                $currentContent,
                $prompt,
                $focusKeyword
            );
            
            if ($correctionResult['success']) {
                $currentContent = $correctionResult['content'];
                $correctionsApplied++;
                
                $this->correctionHistory[] = [
                    'issueType' => $prompt->issueType,
                    'success' => true,
                    'timestamp' => current_time('mysql'),
                    'provider' => $correctionResult['provider'] ?? 'unknown'
                ];
            } else {
                $failedCorrections[] = [
                    'issueType' => $prompt->issueType,
                    'error' => $correctionResult['error'] ?? 'Unknown error',
                    'timestamp' => current_time('mysql')
                ];
                
                $this->logMessage("Failed to apply correction for {$prompt->issueType}: " . 
                    ($correctionResult['error'] ?? 'Unknown error'), 'warning');
            }
        }
        
        // Validate corrections were actually applied
        if ($this->config['enableCorrectionValidation']) {
            $validationResult = $this->validateCorrectionSuccess(
                $originalContent,
                $currentContent,
                $correctionPrompts
            );
            
            if (!$validationResult['success']) {
                $this->logMessage("Correction validation failed: " . $validationResult['message'], 'warning');
            }
        }
        
        return [
            'success' => $correctionsApplied > 0,
            'content' => $currentContent,
            'correctionsApplied' => $correctionsApplied,
            'failedCorrections' => $failedCorrections,
            'correctionHistory' => $this->correctionHistory,
            'validationResult' => $validationResult ?? null
        ];
    }
    
    /**
     * Apply single correction using AI provider
     *
     * @param array $content Current content
     * @param CorrectionPrompt $prompt Correction prompt
     * @param string $focusKeyword Focus keyword
     * @return array Correction result
     */
    private function applySingleCorrection($content, $prompt, $focusKeyword) {
        $this->currentRetry = 0;
        $lastError = null;
        
        // Try each provider in order
        foreach ($this->providerOrder as $providerKey) {
            if (!isset($this->providers[$providerKey])) {
                continue;
            }
            
            $provider = $this->providers[$providerKey];
            
            // Attempt correction with retries
            for ($attempt = 0; $attempt < $prompt->maxAttempts; $attempt++) {
                $this->currentRetry = $attempt + 1;
                
                try {
                    $result = $this->attemptCorrectionWithProvider(
                        $provider,
                        $providerKey,
                        $content,
                        $prompt,
                        $focusKeyword
                    );
                    
                    if ($result['success']) {
                        $this->logMessage("Correction successful with {$providerKey} on attempt {$this->currentRetry}", 'info');
                        return $result;
                    }
                    
                    $lastError = $result['error'] ?? 'Unknown error';
                    $this->logMessage("Correction attempt {$this->currentRetry} failed with {$providerKey}: {$lastError}", 'warning');
                    
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                    $this->logMessage("Exception during correction with {$providerKey}: {$lastError}", 'error');
                }
                
                // Wait before retry
                if ($attempt < $prompt->maxAttempts - 1) {
                    usleep(500000); // 0.5 second delay
                }
            }
            
            // If failover is disabled, stop after first provider
            if (!$this->config['enableProviderFailover']) {
                break;
            }
        }
        
        // All providers and retries failed
        return [
            'success' => false,
            'content' => $content,
            'error' => $lastError ?? 'All correction attempts failed'
        ];
    }
    
    /**
     * Attempt correction with specific provider
     *
     * @param object $provider AI provider instance
     * @param string $providerKey Provider key
     * @param array $content Current content
     * @param CorrectionPrompt $prompt Correction prompt
     * @param string $focusKeyword Focus keyword
     * @return array Correction result
     */
    private function attemptCorrectionWithProvider($provider, $providerKey, $content, $prompt, $focusKeyword) {
        // Build correction prompt
        $correctionPromptText = $this->buildCorrectionPrompt($content, $prompt, $focusKeyword);
        
        // Call AI provider
        $response = $provider->generate_content($correctionPromptText, [
            'temperature' => 0.3, // Lower temperature for more focused corrections
            'max_tokens' => 4096
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'content' => $content,
                'error' => $response->get_error_message()
            ];
        }
        
        // Parse response
        $correctedContent = $this->parseCorrectionResponse($response, $content, $prompt);
        
        if (!$correctedContent) {
            return [
                'success' => false,
                'content' => $content,
                'error' => 'Failed to parse correction response'
            ];
        }
        
        // Validate correction was applied
        if ($this->config['enableCorrectionValidation']) {
            $isValid = $this->validateSingleCorrection($content, $correctedContent, $prompt);
            
            if (!$isValid) {
                return [
                    'success' => false,
                    'content' => $content,
                    'error' => 'Correction validation failed - changes not applied correctly'
                ];
            }
        }
        
        return [
            'success' => true,
            'content' => $correctedContent,
            'provider' => $providerKey
        ];
    }
    
    /**
     * Build correction prompt for AI provider
     *
     * @param array $content Current content
     * @param CorrectionPrompt $prompt Correction prompt
     * @param string $focusKeyword Focus keyword
     * @return string Correction prompt text
     */
    private function buildCorrectionPrompt($content, $prompt, $focusKeyword) {
        $promptText = "You are an expert SEO content editor. Your task is to make a SPECIFIC correction to the following content.\n\n";
        $promptText .= "CORRECTION REQUIRED:\n";
        $promptText .= $prompt->promptText . "\n\n";
        
        $promptText .= "CURRENT CONTENT:\n";
        $promptText .= "Title: " . ($content['title'] ?? '') . "\n";
        $promptText .= "Meta Description: " . ($content['meta_description'] ?? '') . "\n";
        $promptText .= "Content:\n" . ($content['content'] ?? '') . "\n\n";
        
        if (!empty($focusKeyword)) {
            $promptText .= "Focus Keyword: {$focusKeyword}\n\n";
        }
        
        $promptText .= "INSTRUCTIONS:\n";
        $promptText .= "1. Make ONLY the specific correction described above\n";
        $promptText .= "2. Preserve all other content exactly as is\n";
        $promptText .= "3. Maintain the same HTML structure and formatting\n";
        $promptText .= "4. Return the corrected content in JSON format with fields: title, meta_description, content\n";
        $promptText .= "5. Do NOT make any other changes beyond the specific correction requested\n\n";
        
        $promptText .= "Return ONLY valid JSON with no additional text or explanation.";
        
        return $promptText;
    }
    
    /**
     * Parse correction response from AI provider
     *
     * @param mixed $response Provider response
     * @param array $originalContent Original content
     * @param CorrectionPrompt $prompt Correction prompt
     * @return array|false Corrected content or false on failure
     */
    private function parseCorrectionResponse($response, $originalContent, $prompt) {
        // If response is already an array, use it
        if (is_array($response)) {
            return array_merge($originalContent, $response);
        }
        
        // Try to parse as JSON
        $content = is_string($response) ? $response : '';
        
        // Handle markdown code blocks
        if (preg_match('/```json\s*\n?(.*?)\n?```/s', $content, $matches)) {
            $content = trim($matches[1]);
        } else if (preg_match('/```\s*\n?(.*?)\n?```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }
        
        // Clean control characters
        $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
        $content = trim($content);
        
        // Try JSON decode
        $decoded = json_decode($content, true);
        
        if (is_array($decoded)) {
            // Merge with original content to preserve fields not being corrected
            return array_merge($originalContent, $decoded);
        }
        
        // Try to extract JSON object
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        
        if ($start !== false && $end !== false && $end > $start) {
            $maybe = substr($content, $start, $end - $start + 1);
            $decoded = json_decode($maybe, true);
            
            if (is_array($decoded)) {
                return array_merge($originalContent, $decoded);
            }
        }
        
        // Parsing failed
        $this->logMessage("Failed to parse correction response", 'error');
        return false;
    }
    
    /**
     * Validate that correction was successfully applied
     *
     * @param array $originalContent Original content
     * @param array $correctedContent Corrected content
     * @param CorrectionPrompt $prompt Correction prompt
     * @return bool True if correction was applied
     */
    private function validateSingleCorrection($originalContent, $correctedContent, $prompt) {
        // Check that content actually changed
        $originalHash = md5(serialize($originalContent));
        $correctedHash = md5(serialize($correctedContent));
        
        if ($originalHash === $correctedHash) {
            $this->logMessage("Correction validation failed: content unchanged", 'warning');
            return false;
        }
        
        // Validate based on issue type
        switch ($prompt->issueType) {
            case 'meta_description_short':
            case 'meta_description_long':
                $originalLength = strlen($originalContent['meta_description'] ?? '');
                $correctedLength = strlen($correctedContent['meta_description'] ?? '');
                
                // Check if length moved toward target
                $targetLength = $prompt->quantitativeTarget['target'] ?? 140;
                $originalDiff = abs($originalLength - $targetLength);
                $correctedDiff = abs($correctedLength - $targetLength);
                
                return $correctedDiff < $originalDiff;
                
            case 'title_too_long':
                $originalLength = strlen($originalContent['title'] ?? '');
                $correctedLength = strlen($correctedContent['title'] ?? '');
                
                return $correctedLength < $originalLength;
                
            case 'keyword_density_high':
            case 'keyword_density_low':
                // Check if content was modified (detailed validation would require recalculating density)
                $originalContent = $originalContent['content'] ?? '';
                $correctedContent = $correctedContent['content'] ?? '';
                
                return $originalContent !== $correctedContent;
                
            default:
                // For other types, just check that content changed
                return true;
        }
    }
    
    /**
     * Validate overall correction success
     *
     * @param array $originalContent Original content
     * @param array $correctedContent Corrected content
     * @param array $correctionPrompts Array of correction prompts
     * @return array Validation result
     */
    public function validateCorrectionSuccess($originalContent, $correctedContent, $correctionPrompts) {
        $validations = [];
        $successCount = 0;
        
        foreach ($correctionPrompts as $prompt) {
            $isValid = $this->validateSingleCorrection($originalContent, $correctedContent, $prompt);
            
            $validations[] = [
                'issueType' => $prompt->issueType,
                'valid' => $isValid
            ];
            
            if ($isValid) {
                $successCount++;
            }
        }
        
        $successRate = count($correctionPrompts) > 0 ? 
            ($successCount / count($correctionPrompts)) * 100 : 0;
        
        return [
            'success' => $successRate >= 50, // At least 50% of corrections should be applied
            'successRate' => $successRate,
            'validations' => $validations,
            'message' => "{$successCount} of " . count($correctionPrompts) . " corrections validated"
        ];
    }
    
    /**
     * Sort prompts by priority (highest first)
     *
     * @param array $prompts Array of CorrectionPrompt objects
     * @return array Sorted prompts
     */
    private function sortPromptsByPriority($prompts) {
        usort($prompts, function($a, $b) {
            return $b->priority - $a->priority;
        });
        
        return $prompts;
    }
    
    /**
     * Handle correction failures with fallback strategies
     *
     * @param array $failedPrompts Array of failed correction prompts
     * @return array Fallback result
     */
    public function handleCorrectionFailures($failedPrompts) {
        $this->logMessage("Handling " . count($failedPrompts) . " failed corrections", 'info');
        
        $fallbackStrategies = [];
        
        foreach ($failedPrompts as $prompt) {
            // Determine fallback strategy based on issue type
            $strategy = $this->determineFallbackStrategy($prompt);
            $fallbackStrategies[] = $strategy;
        }
        
        return [
            'strategies' => $fallbackStrategies,
            'message' => 'Fallback strategies determined for failed corrections'
        ];
    }
    
    /**
     * Determine fallback strategy for failed correction
     *
     * @param array $prompt Failed correction prompt
     * @return array Fallback strategy
     */
    private function determineFallbackStrategy($prompt) {
        return [
            'issueType' => $prompt['issueType'] ?? 'unknown',
            'strategy' => 'retry_with_simplified_prompt',
            'priority' => 'medium'
        ];
    }
    
    /**
     * Get correction history
     *
     * @return array Correction history
     */
    public function getCorrectionHistory() {
        return $this->correctionHistory;
    }
    
    /**
     * Get error log
     *
     * @return array Error log
     */
    public function getErrorLog() {
        return $this->errorLog;
    }
    
    /**
     * Clear correction history
     */
    public function clearHistory() {
        $this->correctionHistory = [];
        $this->errorLog = [];
    }
    
    /**
     * Update configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        
        // Reinitialize providers if needed
        if (isset($newConfig['enableProviderFailover'])) {
            $this->initializeProviders();
        }
    }
    
    /**
     * Get current configuration
     *
     * @return array Current configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Log message with appropriate level
     *
     * @param string $message Message to log
     * @param string $level Log level (debug, info, warning, error)
     */
    private function logMessage($message, $level = 'info') {
        $logLevels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $currentLevel = $logLevels[$this->config['logLevel']] ?? 1;
        $messageLevel = $logLevels[$level] ?? 1;
        
        if ($messageLevel >= $currentLevel) {
            $logEntry = [
                'level' => $level,
                'message' => $message,
                'retry' => $this->currentRetry,
                'timestamp' => current_time('mysql')
            ];
            
            $this->errorLog[] = $logEntry;
            
            // Also log to WordPress if error level
            if ($level === 'error') {
                error_log("AIContentCorrector [{$level}]: {$message}");
            }
        }
    }
    
    /**
     * Get correction statistics
     *
     * @return array Statistics summary
     */
    public function getCorrectionStats() {
        if (empty($this->correctionHistory)) {
            return ['status' => 'no_data'];
        }
        
        $successful = array_filter($this->correctionHistory, function($item) {
            return $item['success'] === true;
        });
        
        $failed = count($this->correctionHistory) - count($successful);
        
        $providerUsage = [];
        foreach ($this->correctionHistory as $item) {
            if (isset($item['provider'])) {
                $providerUsage[$item['provider']] = ($providerUsage[$item['provider']] ?? 0) + 1;
            }
        }
        
        return [
            'status' => 'available',
            'totalCorrections' => count($this->correctionHistory),
            'successful' => count($successful),
            'failed' => $failed,
            'successRate' => count($this->correctionHistory) > 0 ? 
                (count($successful) / count($this->correctionHistory)) * 100 : 0,
            'providerUsage' => $providerUsage
        ];
    }
}
