<?php
/**
 * SEO Validation Result Class
 *
 * Represents the result of SEO validation checks on generated content.
 * Contains validation status, errors, warnings, and overall scoring.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SEOValidationResult
 *
 * Data model for SEO validation results with scoring and error tracking
 */
class SEOValidationResult {
    
    /**
     * @var bool Overall validation status
     */
    public $isValid;
    
    /**
     * @var array Critical errors that prevent content publication
     */
    public $errors;
    
    /**
     * @var array Non-critical issues that should be addressed
     */
    public $warnings;
    
    /**
     * @var array Recommendations for content improvement
     */
    public $suggestions;
    
    /**
     * @var float Overall SEO score (0-100)
     */
    public $overallScore;
    
    /**
     * @var array Corrected content after validation fixes
     */
    public $correctedContent;
    
    /**
     * @var array List of corrections that were made
     */
    public $correctionsMade;
    
    /**
     * @var array Validation metrics
     */
    public $metrics;
    
    /**
     * Constructor
     *
     * @param bool $isValid Overall validation status
     * @param array $errors Critical validation errors
     * @param array $warnings Non-critical validation warnings
     * @param array $suggestions Improvement suggestions
     * @param float $overallScore Overall SEO score
     */
    public function __construct($isValid = false, $errors = [], $warnings = [], $suggestions = [], $overallScore = 0.0) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->suggestions = $suggestions;
        $this->overallScore = $overallScore;
    }
    
    /**
     * Add a critical error
     *
     * @param string $error Error message
     * @param string $component Component that failed validation
     */
    public function addError($error, $component = '') {
        $this->errors[] = [
            'message' => $error,
            'component' => $component,
            'timestamp' => current_time('mysql')
        ];
        $this->isValid = false;
    }
    
    /**
     * Add a warning
     *
     * @param string $warning Warning message
     * @param string $component Component with warning
     */
    public function addWarning($warning, $component = '') {
        $this->warnings[] = [
            'message' => $warning,
            'component' => $component,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Add a suggestion
     *
     * @param string $suggestion Improvement suggestion
     * @param string $component Component for suggestion
     */
    public function addSuggestion($suggestion, $component = '') {
        $this->suggestions[] = [
            'message' => $suggestion,
            'component' => $component,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Check if validation has any issues
     *
     * @return bool True if there are errors or warnings
     */
    public function hasIssues() {
        return !empty($this->errors) || !empty($this->warnings);
    }
    
    /**
     * Get total issue count
     *
     * @return int Total number of errors and warnings
     */
    public function getIssueCount() {
        return count($this->errors) + count($this->warnings);
    }
    
    /**
     * Calculate overall score based on errors and warnings
     *
     * @return float Calculated score (0-100)
     */
    public function calculateScore() {
        $baseScore = 100.0;
        $errorPenalty = count($this->errors) * 20; // 20 points per error
        $warningPenalty = count($this->warnings) * 5; // 5 points per warning
        
        $this->overallScore = max(0, $baseScore - $errorPenalty - $warningPenalty);
        return $this->overallScore;
    }
    
    /**
     * Convert to array for JSON serialization
     *
     * @param bool $includeContent Whether to include correctedContent (exclude to avoid circular references)
     * @return array Validation result as array
     */
    public function toArray($includeContent = false) {
        $result = [
            'isValid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'suggestions' => $this->suggestions,
            'overallScore' => $this->overallScore,
            'issueCount' => $this->getIssueCount(),
            'correctionsMade' => $this->correctionsMade ?? [],
            'metrics' => $this->metrics ?? []
        ];
        
        // Only include correctedContent if explicitly requested (to avoid circular references)
        // The corrected content is already in the main result array, so we don't need it here
        if ($includeContent && !empty($this->correctedContent)) {
            $result['correctedContent'] = $this->correctedContent;
        }
        
        return $result;
    }
    
    /**
     * Create from array data
     *
     * @param array $data Validation result data
     * @return SEOValidationResult
     */
    public static function fromArray($data) {
        return new self(
            $data['isValid'] ?? false,
            $data['errors'] ?? [],
            $data['warnings'] ?? [],
            $data['suggestions'] ?? [],
            $data['overallScore'] ?? 0.0
        );
    }
}