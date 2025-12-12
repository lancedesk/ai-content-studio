<?php
/**
 * Multi-Pass SEO Optimizer Engine
 *
 * Main controller that orchestrates iterative SEO validation and correction cycles
 * until 100% SEO compliance is achieved. Implements intelligent loop management,
 * targeted correction prompts, and comprehensive progress tracking.
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
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-pipeline.php';
require_once ACS_PLUGIN_PATH . 'seo/class-validation-improvement-tracker.php';
require_once ACS_PLUGIN_PATH . 'seo/class-optimization-progress-tracker.php';
require_once ACS_PLUGIN_PATH . 'seo/class-content-structure-preserver.php';

/**
 * Class MultiPassSEOOptimizer
 *
 * Main optimization engine that implements iterative correction cycles
 */
class MultiPassSEOOptimizer {
    
    /**
     * @var SEOValidationPipeline
     */
    private $validationPipeline;
    
    /**
     * @var ValidationImprovementTracker
     */
    private $improvementTracker;
    
    /**
     * @var OptimizationProgressTracker
     */
    private $progressTracker;
    
    /**
     * @var ContentStructurePreserver
     */
    private $structurePreserver;
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var array Progress tracking data
     */
    private $progressData;
    
    /**
     * @var array Error log for debugging
     */
    private $errorLog;
    
    /**
     * @var int Current iteration number
     */
    private $currentIteration;
    
    /**
     * @var float Previous iteration score
     */
    private $previousScore;
    
    /**
     * @var array Optimization history
     */
    private $optimizationHistory;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'maxIterations' => 5,
            'targetComplianceScore' => 100.0,
            'enableEarlyTermination' => true,
            'priorityOrder' => ['meta_description', 'keyword_density', 'readability', 'title', 'images'],
            'enableFallbackStrategies' => true,
            'minImprovementThreshold' => 1.0, // Minimum score improvement per iteration
            'stagnationThreshold' => 2, // Number of iterations without improvement before termination
            'autoCorrection' => true,
            'logLevel' => 'info' // debug, info, warning, error
        ], $config);
        
        $this->initializeComponents();
        $this->resetProgressTracking();
    }
    
    /**
     * Initialize validation pipeline and other components
     */
    private function initializeComponents() {
        $pipelineConfig = [
            'autoCorrection' => $this->config['autoCorrection'],
            'maxRetryAttempts' => 3
        ];
        
        $this->validationPipeline = new SEOValidationPipeline($pipelineConfig);
        
        // Initialize improvement tracker
        $trackerConfig = [
            'enableCaching' => true,
            'trackTrends' => true,
            'detailedMetrics' => true
        ];
        
        $this->improvementTracker = new ValidationImprovementTracker($trackerConfig);
        
        // Initialize progress tracker
        $progressTrackerConfig = [
            'enableContentHistory' => true,
            'maxHistoryEntries' => 10,
            'trackStrategyEffectiveness' => true,
            'detailedReporting' => true,
            'enableRollback' => true
        ];
        
        $this->progressTracker = new OptimizationProgressTracker($progressTrackerConfig);
        
        // Initialize structure preserver
        $preserverConfig = [
            'enableRollback' => true,
            'maxSnapshots' => 10,
            'enableChecksums' => true,
            'preserveFormatting' => true,
            'preserveStructure' => true,
            'preserveIntent' => true,
            'strictValidation' => true,
            'logLevel' => $this->config['logLevel']
        ];
        
        $this->structurePreserver = new ContentStructurePreserver($preserverConfig);
    }
    
    /**
     * Reset progress tracking for new optimization
     */
    private function resetProgressTracking() {
        $this->progressData = [
            'startTime' => current_time('mysql'),
            'startTimestamp' => microtime(true),
            'iterations' => [],
            'totalIterations' => 0,
            'finalScore' => 0.0,
            'complianceAchieved' => false,
            'terminationReason' => '',
            'correctionsMade' => [],
            'issuesResolved' => [],
            'performanceMetrics' => []
        ];
        
        $this->errorLog = [];
        $this->currentIteration = 0;
        $this->previousScore = 0.0;
        $this->optimizationHistory = [];
    }
    
    /**
     * Main optimization entry point
     *
     * @param array $content Generated content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return array Optimization result with corrected content
     */
    public function optimizeContent($content, $focusKeyword, $secondaryKeywords = []) {
        $this->logMessage("Starting multi-pass optimization for content: " . ($content['title'] ?? 'Untitled'), 'info');
        
        $this->resetProgressTracking();
        $currentContent = $content;
        $bestContent = $content;
        $bestScore = 0.0;
        $stagnationCount = 0;
        
        try {
            // Initial validation to establish baseline
            $initialResult = $this->validationPipeline->validateAndCorrect($currentContent, $focusKeyword, $secondaryKeywords);
            $this->previousScore = $initialResult->overallScore;
            $bestScore = $this->previousScore;
            
            $this->logMessage("Initial SEO score: {$this->previousScore}%", 'info');
            $this->trackIterationProgress(0, $initialResult, $currentContent, 'baseline');
            
            // Start progress tracking session
            $initialIssues = $initialResult->errors ?? [];
            $this->progressTracker->startSession($currentContent, $this->previousScore, $initialIssues);
            
            // Create initial snapshot for rollback capability
            $this->structurePreserver->createSnapshot($currentContent, 'initial_content');
            
            // Check if already compliant
            if ($this->checkTerminationConditions($initialResult, 0, $stagnationCount)) {
                return $this->generateOptimizationReport($currentContent, $initialResult, 'initial_compliance');
            }
            
            // Main optimization loop
            for ($this->currentIteration = 1; $this->currentIteration <= $this->config['maxIterations']; $this->currentIteration++) {
                $this->logMessage("Starting optimization iteration {$this->currentIteration}", 'info');
                
                // Store content before pass
                $beforeContent = $currentContent;
                $beforeScore = $this->previousScore;
                $beforeIssues = $initialResult->errors ?? [];
                
                // Run optimization cycle
                $cycleResult = $this->runOptimizationCycle($currentContent, $focusKeyword, $secondaryKeywords, $initialResult);
                
                if (!$cycleResult['success']) {
                    $this->logMessage("Optimization cycle failed: " . $cycleResult['error'], 'error');
                    break;
                }
                
                $optimizedContent = $cycleResult['content'];
                
                // Preserve content structure and validate integrity
                $preservationResult = $this->structurePreserver->preserveContent($beforeContent, $optimizedContent);
                
                if (!$preservationResult['success'] && $preservationResult['rolledBack']) {
                    $this->logMessage("Content structure validation failed, rolled back to previous version", 'warning');
                    // Use rolled back content
                    $currentContent = $preservationResult['content'];
                    // Re-validate rolled back content
                    $validationResult = $this->validationPipeline->validateAndCorrect($currentContent, $focusKeyword, $secondaryKeywords);
                } else {
                    // Use optimized content
                    $currentContent = $preservationResult['content'];
                    $validationResult = $cycleResult['result'];
                    
                    // Create snapshot after successful optimization
                    $this->structurePreserver->createSnapshot($currentContent, "iteration_{$this->currentIteration}");
                }
                
                $currentScore = $validationResult->overallScore;
                $afterIssues = $validationResult->errors ?? [];
                
                $this->logMessage("Iteration {$this->currentIteration} score: {$currentScore}%", 'info');
                
                // Track progress
                $this->trackIterationProgress($this->currentIteration, $validationResult, $currentContent, 'optimization');
                
                // Record pass in progress tracker
                $corrections = $cycleResult['prompts'] ?? [];
                $strategy = [
                    'name' => 'multi_pass_correction',
                    'type' => 'targeted_correction',
                    'priorityOrder' => $this->config['priorityOrder']
                ];
                
                $this->progressTracker->recordPass(
                    $this->currentIteration,
                    $beforeContent,
                    $currentContent,
                    $beforeScore,
                    $currentScore,
                    $beforeIssues,
                    $afterIssues,
                    $corrections,
                    $strategy
                );
                
                // Update best result if improved
                if ($currentScore > $bestScore) {
                    $bestContent = $currentContent;
                    $bestScore = $currentScore;
                    $stagnationCount = 0;
                } else {
                    $stagnationCount++;
                }
                
                // Check termination conditions
                if ($this->checkTerminationConditions($validationResult, $this->currentIteration, $stagnationCount)) {
                    $terminationReason = $this->getTerminationReason($validationResult, $this->currentIteration, $stagnationCount);
                    $this->logMessage("Optimization terminated: {$terminationReason}", 'info');
                    break;
                }
                
                $this->previousScore = $currentScore;
            }
            
            // Use best result achieved
            $finalResult = $this->validationPipeline->validateAndCorrect($bestContent, $focusKeyword, $secondaryKeywords);
            
            // End progress tracking session
            $complianceAchieved = $finalResult->overallScore >= $this->config['targetComplianceScore'];
            $this->progressTracker->endSession($complianceAchieved, $this->progressData['terminationReason']);
            
            return $this->generateOptimizationReport($bestContent, $finalResult, $this->progressData['terminationReason']);
            
        } catch (Exception $e) {
            $this->logMessage("Critical optimization error: " . $e->getMessage(), 'error');
            $this->errorLog[] = [
                'type' => 'critical_error',
                'message' => $e->getMessage(),
                'iteration' => $this->currentIteration,
                'timestamp' => current_time('mysql')
            ];
            
            // Return best result achieved so far
            $fallbackResult = new SEOValidationResult();
            $fallbackResult->addError('Optimization failed: ' . $e->getMessage(), 'optimizer');
            return $this->generateOptimizationReport($bestContent, $fallbackResult, 'critical_error');
        }
    }
    
    /**
     * Run single optimization cycle
     *
     * @param array $content Current content
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param SEOValidationResult $previousResult Previous validation result
     * @return array Cycle result
     */
    private function runOptimizationCycle($content, $focusKeyword, $secondaryKeywords, $previousResult) {
        try {
            // Store original content for improvement measurement
            $originalContent = $content;
            
            // Generate targeted correction prompts based on previous issues
            $correctionPrompts = $this->generateCorrectionPrompts($previousResult, $focusKeyword);
            
            // Apply corrections through validation pipeline
            $validationResult = $this->validationPipeline->validateAndCorrect($content, $focusKeyword, $secondaryKeywords);
            
            // Get corrected content
            $correctedContent = $validationResult->correctedContent ?? $content;
            
            // Measure improvements using the tracker
            $improvementResult = $this->improvementTracker->validateAndMeasureImprovement(
                $originalContent,
                $correctedContent,
                $focusKeyword,
                $secondaryKeywords,
                $this->currentIteration
            );
            
            // Track corrections made
            if (isset($validationResult->correctionsMade)) {
                $this->progressData['correctionsMade'] = array_merge(
                    $this->progressData['correctionsMade'],
                    $validationResult->correctionsMade
                );
            }
            
            // Store improvement data
            if (isset($improvementResult['improvements'])) {
                $this->progressData['iterations'][$this->currentIteration]['improvementDetails'] = $improvementResult['improvements'];
            }
            
            return [
                'success' => true,
                'content' => $correctedContent,
                'result' => $validationResult,
                'prompts' => $correctionPrompts,
                'improvementData' => $improvementResult
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => $content,
                'result' => null
            ];
        }
    }
    
    /**
     * Generate targeted correction prompts based on validation issues
     *
     * @param SEOValidationResult $result Previous validation result
     * @param string $focusKeyword Focus keyword
     * @return array Correction prompts
     */
    private function generateCorrectionPrompts($result, $focusKeyword) {
        $prompts = [];
        
        // Prioritize corrections based on configuration
        foreach ($this->config['priorityOrder'] as $component) {
            $componentErrors = array_filter($result->errors, function($error) use ($component) {
                return $error['component'] === $component;
            });
            
            foreach ($componentErrors as $error) {
                $prompts[] = $this->createCorrectionPrompt($component, $error['message'], $focusKeyword);
            }
        }
        
        return $prompts;
    }
    
    /**
     * Create specific correction prompt for component and error
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param string $focusKeyword Focus keyword
     * @return array Correction prompt
     */
    private function createCorrectionPrompt($component, $error, $focusKeyword) {
        $prompts = [
            'meta_description' => [
                'type' => 'meta_description_correction',
                'instruction' => "Fix meta description to be 120-156 characters and include '{$focusKeyword}'",
                'priority' => 1
            ],
            'keyword_density' => [
                'type' => 'keyword_density_correction',
                'instruction' => "Adjust keyword density for '{$focusKeyword}' to be between 0.5-2.5%",
                'priority' => 2
            ],
            'readability' => [
                'type' => 'readability_correction',
                'instruction' => "Improve readability: reduce passive voice (<10%), shorten long sentences (<25%), add transition words (>30%)",
                'priority' => 3
            ],
            'title' => [
                'type' => 'title_correction',
                'instruction' => "Optimize title to include '{$focusKeyword}' and be under 66 characters",
                'priority' => 4
            ],
            'images' => [
                'type' => 'image_correction',
                'instruction' => "Add images with alt text containing '{$focusKeyword}'",
                'priority' => 5
            ]
        ];
        
        return array_merge($prompts[$component] ?? [], [
            'error' => $error,
            'component' => $component,
            'focusKeyword' => $focusKeyword,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Check if optimization should terminate
     *
     * @param SEOValidationResult $result Current validation result
     * @param int $iteration Current iteration number
     * @param int $stagnationCount Number of iterations without improvement
     * @return bool True if should terminate
     */
    private function checkTerminationConditions($result, $iteration, $stagnationCount) {
        // Check if 100% compliance achieved
        if ($result->overallScore >= $this->config['targetComplianceScore']) {
            $this->progressData['terminationReason'] = 'compliance_achieved';
            $this->progressData['complianceAchieved'] = true;
            return true;
        }
        
        // Check maximum iterations
        if ($iteration >= $this->config['maxIterations']) {
            $this->progressData['terminationReason'] = 'max_iterations_reached';
            return true;
        }
        
        // Check stagnation (early termination)
        if ($this->config['enableEarlyTermination'] && $stagnationCount >= $this->config['stagnationThreshold']) {
            $this->progressData['terminationReason'] = 'stagnation_detected';
            return true;
        }
        
        // Check minimum improvement threshold
        if ($iteration > 0) {
            $improvement = $result->overallScore - $this->previousScore;
            if ($improvement < $this->config['minImprovementThreshold'] && $stagnationCount > 0) {
                $this->progressData['terminationReason'] = 'insufficient_improvement';
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get human-readable termination reason
     *
     * @param SEOValidationResult $result Current validation result
     * @param int $iteration Current iteration number
     * @param int $stagnationCount Stagnation count
     * @return string Termination reason
     */
    private function getTerminationReason($result, $iteration, $stagnationCount) {
        if ($result->overallScore >= $this->config['targetComplianceScore']) {
            return 'compliance_achieved';
        } elseif ($iteration >= $this->config['maxIterations']) {
            return 'max_iterations_reached';
        } elseif ($stagnationCount >= $this->config['stagnationThreshold']) {
            return 'stagnation_detected';
        } else {
            return 'insufficient_improvement';
        }
    }
    
    /**
     * Track progress for current iteration
     *
     * @param int $iteration Iteration number
     * @param SEOValidationResult $result Validation result
     * @param array $content Current content
     * @param string $type Iteration type
     */
    private function trackIterationProgress($iteration, $result, $content, $type) {
        $iterationData = [
            'iteration' => $iteration,
            'type' => $type,
            'score' => $result->overallScore,
            'isValid' => $result->isValid,
            'errorCount' => count($result->errors),
            'warningCount' => count($result->warnings),
            'timestamp' => current_time('mysql'),
            'improvements' => []
        ];
        
        // Calculate improvements from previous iteration
        if ($iteration > 0 && !empty($this->progressData['iterations'])) {
            $previousIteration = end($this->progressData['iterations']);
            $previousScore = isset($previousIteration['score']) ? $previousIteration['score'] : 0;
            $previousErrorCount = isset($previousIteration['errorCount']) ? $previousIteration['errorCount'] : 0;
            $previousWarningCount = isset($previousIteration['warningCount']) ? $previousIteration['warningCount'] : 0;
            
            $iterationData['improvements'] = [
                'scoreImprovement' => $result->overallScore - $previousScore,
                'errorReduction' => $previousErrorCount - count($result->errors),
                'warningReduction' => $previousWarningCount - count($result->warnings)
            ];
        }
        
        $this->progressData['iterations'][] = $iterationData;
        // totalIterations represents actual optimization iterations (excluding baseline)
        $this->progressData['totalIterations'] = max(0, $iteration);
        $this->progressData['finalScore'] = $result->overallScore;
        
        // Track specific issues resolved
        if (isset($result->correctionsMade)) {
            foreach ($result->correctionsMade as $correction) {
                if (!in_array($correction, $this->progressData['issuesResolved'])) {
                    $this->progressData['issuesResolved'][] = $correction;
                }
            }
        }
    }
    
    /**
     * Generate comprehensive optimization report
     *
     * @param array $content Final optimized content
     * @param SEOValidationResult $result Final validation result
     * @param string $terminationReason Why optimization ended
     * @return array Optimization report
     */
    public function generateOptimizationReport($content, $result, $terminationReason) {
        $this->progressData['endTime'] = current_time('mysql');
        $this->progressData['terminationReason'] = $terminationReason;
        
        // Calculate performance metrics
        $startTime = strtotime($this->progressData['startTime']);
        $endTime = strtotime($this->progressData['endTime']);
        $duration = $endTime - $startTime;
        
        $this->progressData['performanceMetrics'] = [
            'totalDuration' => $duration,
            'averageIterationTime' => $this->progressData['totalIterations'] > 0 ? $duration / $this->progressData['totalIterations'] : 0,
            'iterationsPerSecond' => $duration > 0 ? $this->progressData['totalIterations'] / $duration : 0,
            'finalComplianceScore' => $result->overallScore,
            'totalCorrections' => count($this->progressData['correctionsMade']),
            'issuesResolved' => count($this->progressData['issuesResolved'])
        ];
        
        // Get comprehensive progress tracker report
        $progressTrackerReport = $this->progressTracker->generateComprehensiveReport();
        
        return [
            'success' => $result->overallScore >= $this->config['targetComplianceScore'],
            'content' => $content,
            'validationResult' => $result,
            'progressData' => $this->progressData,
            'optimizationSummary' => [
                'initialScore' => !empty($this->progressData['iterations']) ? $this->progressData['iterations'][0]['score'] : 0,
                'finalScore' => $result->overallScore,
                'improvement' => !empty($this->progressData['iterations']) ? 
                    $result->overallScore - $this->progressData['iterations'][0]['score'] : 0,
                'iterationsUsed' => $this->progressData['totalIterations'],
                'complianceAchieved' => $result->overallScore >= $this->config['targetComplianceScore'],
                'terminationReason' => $terminationReason,
                'correctionsMade' => $this->progressData['correctionsMade'],
                'issuesResolved' => $this->progressData['issuesResolved']
            ],
            'progressTrackerReport' => $progressTrackerReport,
            'errorLog' => $this->errorLog,
            'config' => $this->config
        ];
    }
    
    /**
     * Update optimization configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        
        // Reinitialize components if needed
        if (isset($newConfig['autoCorrection'])) {
            $this->initializeComponents();
        }
        
        $this->logMessage("Configuration updated", 'info');
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
     * Get optimization progress data
     *
     * @return array Progress data
     */
    public function getProgressData() {
        return $this->progressData;
    }
    
    /**
     * Get error log
     *
     * @return array Error log entries
     */
    public function getErrorLog() {
        return $this->errorLog;
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
                'iteration' => $this->currentIteration,
                'timestamp' => current_time('mysql')
            ];
            
            $this->errorLog[] = $logEntry;
            
            // Also log to WordPress if error level
            if ($level === 'error') {
                error_log("MultiPassSEOOptimizer [{$level}]: {$message}");
            }
        }
    }
    
    /**
     * Clear optimization history and logs
     */
    public function clearHistory() {
        $this->resetProgressTracking();
        $this->logMessage("Optimization history cleared", 'info');
    }
    
    /**
     * Get optimization statistics
     *
     * @return array Statistics summary
     */
    public function getOptimizationStats() {
        if (empty($this->progressData['iterations'])) {
            return ['status' => 'no_data'];
        }
        
        $iterations = $this->progressData['iterations'];
        $scores = array_column($iterations, 'score');
        
        return [
            'status' => 'available',
            'totalIterations' => count($iterations),
            'scoreProgression' => $scores,
            'averageScore' => array_sum($scores) / count($scores),
            'maxScore' => max($scores),
            'minScore' => min($scores),
            'finalImprovement' => end($scores) - reset($scores),
            'complianceAchieved' => $this->progressData['complianceAchieved'],
            'terminationReason' => $this->progressData['terminationReason'],
            'performanceMetrics' => $this->progressData['performanceMetrics']
        ];
    }
    
    /**
     * Get improvement tracker instance
     *
     * @return ValidationImprovementTracker
     */
    public function getImprovementTracker() {
        return $this->improvementTracker;
    }
    
    /**
     * Get detailed improvement analysis
     *
     * @return array Improvement analysis
     */
    public function getImprovementAnalysis() {
        if (!$this->improvementTracker) {
            return ['status' => 'not_available'];
        }
        
        return [
            'passHistory' => $this->improvementTracker->getPassHistory(),
            'cacheStats' => $this->improvementTracker->getCacheStats(),
            'status' => 'available'
        ];
    }
    
    /**
     * Get progress tracker instance
     *
     * @return OptimizationProgressTracker
     */
    public function getProgressTracker() {
        return $this->progressTracker;
    }
    
    /**
     * Get structure preserver instance
     *
     * @return ContentStructurePreserver
     */
    public function getStructurePreserver() {
        return $this->structurePreserver;
    }
    
    /**
     * Get comprehensive progress report
     *
     * @return array Comprehensive progress report
     */
    public function getComprehensiveProgressReport() {
        if (!$this->progressTracker) {
            return ['status' => 'not_available'];
        }
        
        return $this->progressTracker->generateComprehensiveReport();
    }
    
    /**
     * Rollback to specific pass
     *
     * @param int $passNumber Pass number to rollback to
     * @return array|null Content at that pass, or null if not found
     */
    public function rollbackToPass($passNumber) {
        if (!$this->progressTracker) {
            return null;
        }
        
        return $this->progressTracker->rollbackToPass($passNumber);
    }
}