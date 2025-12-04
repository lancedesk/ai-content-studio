<?php
/**
 * Optimization Progress Tracker
 *
 * Tracks detailed metrics for optimization passes, records pass-by-pass progress,
 * measures strategy effectiveness, creates comprehensive reports with before/after
 * comparisons, and provides content history with rollback capability.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class OptimizationProgressTracker
 *
 * Comprehensive progress tracking and reporting for multi-pass optimization
 */
class OptimizationProgressTracker {
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var array Pass-by-pass progress records
     */
    private $passRecords;
    
    /**
     * @var array Content history for rollback
     */
    private $contentHistory;
    
    /**
     * @var array Strategy effectiveness metrics
     */
    private $strategyMetrics;
    
    /**
     * @var array Overall optimization session data
     */
    private $sessionData;
    
    /**
     * @var float Session start time
     */
    private $sessionStartTime;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'enableContentHistory' => true,
            'maxHistoryEntries' => 10,
            'trackStrategyEffectiveness' => true,
            'detailedReporting' => true,
            'enableRollback' => true
        ], $config);
        
        $this->resetSession();
    }
    
    /**
     * Reset session data for new optimization
     */
    private function resetSession() {
        $this->passRecords = [];
        $this->contentHistory = [];
        $this->strategyMetrics = [];
        $this->sessionStartTime = microtime(true);
        
        $this->sessionData = [
            'sessionId' => uniqid('opt_', true),
            'startTime' => current_time('mysql'),
            'startTimestamp' => $this->sessionStartTime,
            'endTime' => null,
            'totalDuration' => 0,
            'totalPasses' => 0,
            'initialScore' => 0,
            'finalScore' => 0,
            'totalImprovement' => 0,
            'complianceAchieved' => false,
            'terminationReason' => '',
            'totalCorrections' => 0,
            'totalIssuesResolved' => 0
        ];
    }
    
    /**
     * Start new optimization session
     *
     * @param array $initialContent Initial content
     * @param float $initialScore Initial SEO score
     * @param array $initialIssues Initial issues detected
     * @return string Session ID
     */
    public function startSession($initialContent, $initialScore, $initialIssues = []) {
        $this->resetSession();
        
        $this->sessionData['initialScore'] = $initialScore;
        $this->sessionData['initialIssues'] = $initialIssues;
        $this->sessionData['initialIssueCount'] = count($initialIssues);
        
        // Store initial content in history
        if ($this->config['enableContentHistory']) {
            $this->addContentToHistory($initialContent, 0, $initialScore, 'initial');
        }
        
        return $this->sessionData['sessionId'];
    }
    
    /**
     * Record progress for a single pass
     *
     * @param int $passNumber Pass number
     * @param array $beforeContent Content before pass
     * @param array $afterContent Content after pass
     * @param float $beforeScore Score before pass
     * @param float $afterScore Score after pass
     * @param array $issuesBefore Issues before pass
     * @param array $issuesAfter Issues after pass
     * @param array $corrections Corrections applied
     * @param array $strategyUsed Strategy information
     * @return array Pass record
     */
    public function recordPass($passNumber, $beforeContent, $afterContent, $beforeScore, $afterScore, 
                               $issuesBefore = [], $issuesAfter = [], $corrections = [], $strategyUsed = []) {
        $passStartTime = microtime(true);
        
        $passRecord = [
            'passNumber' => $passNumber,
            'timestamp' => current_time('mysql'),
            'duration' => 0, // Will be calculated
            'beforeScore' => $beforeScore,
            'afterScore' => $afterScore,
            'scoreImprovement' => $afterScore - $beforeScore,
            'issuesBeforeCount' => count($issuesBefore),
            'issuesAfterCount' => count($issuesAfter),
            'issuesResolved' => count($issuesBefore) - count($issuesAfter),
            'corrections' => $corrections,
            'correctionsCount' => count($corrections),
            'strategyUsed' => $strategyUsed,
            'issuesBefore' => $issuesBefore,
            'issuesAfter' => $issuesAfter
        ];
        
        // Calculate detailed improvements
        $passRecord['improvements'] = $this->calculatePassImprovements($issuesBefore, $issuesAfter, $corrections);
        
        // Track strategy effectiveness
        if ($this->config['trackStrategyEffectiveness'] && !empty($strategyUsed)) {
            $this->trackStrategyEffectiveness($strategyUsed, $passRecord['scoreImprovement'], $passRecord['issuesResolved']);
        }
        
        // Store content in history
        if ($this->config['enableContentHistory']) {
            $this->addContentToHistory($afterContent, $passNumber, $afterScore, 'pass_complete');
        }
        
        // Store pass record
        $this->passRecords[$passNumber] = $passRecord;
        $this->sessionData['totalPasses'] = $passNumber;
        $this->sessionData['finalScore'] = $afterScore;
        $this->sessionData['totalCorrections'] += count($corrections);
        $this->sessionData['totalIssuesResolved'] += $passRecord['issuesResolved'];
        
        return $passRecord;
    }
    
    /**
     * Calculate detailed improvements for a pass
     *
     * @param array $issuesBefore Issues before pass
     * @param array $issuesAfter Issues after pass
     * @param array $corrections Corrections applied
     * @return array Improvement details
     */
    private function calculatePassImprovements($issuesBefore, $issuesAfter, $corrections) {
        $improvements = [
            'resolvedIssueTypes' => [],
            'newIssueTypes' => [],
            'persistentIssueTypes' => [],
            'correctionsByType' => [],
            'effectivenessRate' => 0
        ];
        
        // Extract issue types
        $beforeTypes = array_map(function($issue) {
            return is_object($issue) ? $issue->type : ($issue['type'] ?? 'unknown');
        }, $issuesBefore);
        
        $afterTypes = array_map(function($issue) {
            return is_object($issue) ? $issue->type : ($issue['type'] ?? 'unknown');
        }, $issuesAfter);
        
        // Identify resolved, new, and persistent issues
        $improvements['resolvedIssueTypes'] = array_values(array_diff($beforeTypes, $afterTypes));
        $improvements['newIssueTypes'] = array_values(array_diff($afterTypes, $beforeTypes));
        $improvements['persistentIssueTypes'] = array_values(array_intersect($beforeTypes, $afterTypes));
        
        // Group corrections by type
        foreach ($corrections as $correction) {
            $type = is_array($correction) ? ($correction['type'] ?? 'unknown') : 'unknown';
            if (!isset($improvements['correctionsByType'][$type])) {
                $improvements['correctionsByType'][$type] = 0;
            }
            $improvements['correctionsByType'][$type]++;
        }
        
        // Calculate effectiveness rate
        if (count($corrections) > 0) {
            $improvements['effectivenessRate'] = count($improvements['resolvedIssueTypes']) / count($corrections);
        }
        
        return $improvements;
    }
    
    /**
     * Track strategy effectiveness
     *
     * @param array $strategy Strategy information
     * @param float $scoreImprovement Score improvement achieved
     * @param int $issuesResolved Issues resolved
     */
    private function trackStrategyEffectiveness($strategy, $scoreImprovement, $issuesResolved) {
        $strategyName = $strategy['name'] ?? 'unknown';
        
        if (!isset($this->strategyMetrics[$strategyName])) {
            $this->strategyMetrics[$strategyName] = [
                'name' => $strategyName,
                'timesUsed' => 0,
                'totalScoreImprovement' => 0,
                'totalIssuesResolved' => 0,
                'averageScoreImprovement' => 0,
                'averageIssuesResolved' => 0,
                'successRate' => 0,
                'successfulApplications' => 0
            ];
        }
        
        $this->strategyMetrics[$strategyName]['timesUsed']++;
        $this->strategyMetrics[$strategyName]['totalScoreImprovement'] += $scoreImprovement;
        $this->strategyMetrics[$strategyName]['totalIssuesResolved'] += $issuesResolved;
        
        if ($scoreImprovement > 0 || $issuesResolved > 0) {
            $this->strategyMetrics[$strategyName]['successfulApplications']++;
        }
        
        // Recalculate averages
        $timesUsed = $this->strategyMetrics[$strategyName]['timesUsed'];
        $this->strategyMetrics[$strategyName]['averageScoreImprovement'] = 
            $this->strategyMetrics[$strategyName]['totalScoreImprovement'] / $timesUsed;
        $this->strategyMetrics[$strategyName]['averageIssuesResolved'] = 
            $this->strategyMetrics[$strategyName]['totalIssuesResolved'] / $timesUsed;
        $this->strategyMetrics[$strategyName]['successRate'] = 
            ($this->strategyMetrics[$strategyName]['successfulApplications'] / $timesUsed) * 100;
    }
    
    /**
     * Add content to history for rollback capability
     *
     * @param array $content Content to store
     * @param int $passNumber Pass number
     * @param float $score SEO score
     * @param string $stage Stage identifier
     */
    private function addContentToHistory($content, $passNumber, $score, $stage) {
        $historyEntry = [
            'passNumber' => $passNumber,
            'stage' => $stage,
            'timestamp' => current_time('mysql'),
            'score' => $score,
            'content' => $content,
            'contentHash' => $this->generateContentHash($content)
        ];
        
        $this->contentHistory[] = $historyEntry;
        
        // Limit history size
        if (count($this->contentHistory) > $this->config['maxHistoryEntries']) {
            array_shift($this->contentHistory);
        }
    }
    
    /**
     * Generate content hash for comparison
     *
     * @param array $content Content array
     * @return string Content hash
     */
    private function generateContentHash($content) {
        $hashData = [
            'title' => $content['title'] ?? '',
            'content' => $content['content'] ?? '',
            'meta_description' => $content['meta_description'] ?? ''
        ];
        return md5(json_encode($hashData));
    }
    
    /**
     * End optimization session
     *
     * @param bool $complianceAchieved Whether 100% compliance was achieved
     * @param string $terminationReason Reason for termination
     * @return array Session summary
     */
    public function endSession($complianceAchieved, $terminationReason) {
        $this->sessionData['endTime'] = current_time('mysql');
        $this->sessionData['totalDuration'] = microtime(true) - $this->sessionStartTime;
        $this->sessionData['complianceAchieved'] = $complianceAchieved;
        $this->sessionData['terminationReason'] = $terminationReason;
        $this->sessionData['totalImprovement'] = $this->sessionData['finalScore'] - $this->sessionData['initialScore'];
        
        return $this->generateSessionSummary();
    }
    
    /**
     * Generate comprehensive session summary
     *
     * @return array Session summary
     */
    private function generateSessionSummary() {
        return [
            'sessionId' => $this->sessionData['sessionId'],
            'duration' => $this->sessionData['totalDuration'],
            'totalPasses' => $this->sessionData['totalPasses'],
            'initialScore' => $this->sessionData['initialScore'],
            'finalScore' => $this->sessionData['finalScore'],
            'totalImprovement' => $this->sessionData['totalImprovement'],
            'complianceAchieved' => $this->sessionData['complianceAchieved'],
            'terminationReason' => $this->sessionData['terminationReason'],
            'totalCorrections' => $this->sessionData['totalCorrections'],
            'totalIssuesResolved' => $this->sessionData['totalIssuesResolved'],
            'averagePassDuration' => $this->sessionData['totalPasses'] > 0 ? 
                $this->sessionData['totalDuration'] / $this->sessionData['totalPasses'] : 0,
            'improvementRate' => $this->sessionData['totalPasses'] > 0 ? 
                $this->sessionData['totalImprovement'] / $this->sessionData['totalPasses'] : 0
        ];
    }
    
    /**
     * Generate comprehensive optimization report
     *
     * @return array Comprehensive report
     */
    public function generateComprehensiveReport() {
        $report = [
            'session' => $this->sessionData,
            'summary' => $this->generateSessionSummary(),
            'passRecords' => $this->passRecords,
            'strategyEffectiveness' => $this->strategyMetrics,
            'progressAnalysis' => $this->analyzeProgress(),
            'contentHistory' => $this->getContentHistorySummary()
        ];
        
        if ($this->config['detailedReporting']) {
            $report['detailedMetrics'] = $this->generateDetailedMetrics();
            $report['beforeAfterComparison'] = $this->generateBeforeAfterComparison();
        }
        
        return $report;
    }
    
    /**
     * Analyze progress across all passes
     *
     * @return array Progress analysis
     */
    private function analyzeProgress() {
        if (empty($this->passRecords)) {
            return ['status' => 'no_data'];
        }
        
        $scores = array_column($this->passRecords, 'afterScore');
        $improvements = array_column($this->passRecords, 'scoreImprovement');
        $issuesResolved = array_column($this->passRecords, 'issuesResolved');
        
        return [
            'status' => 'available',
            'scoreProgression' => $scores,
            'improvementProgression' => $improvements,
            'issuesResolvedProgression' => $issuesResolved,
            'averageImprovement' => array_sum($improvements) / count($improvements),
            'totalIssuesResolved' => array_sum($issuesResolved),
            'consistentImprovement' => min($improvements) >= 0,
            'bestPass' => $this->findBestPass(),
            'worstPass' => $this->findWorstPass()
        ];
    }
    
    /**
     * Find best performing pass
     *
     * @return array Best pass information
     */
    private function findBestPass() {
        if (empty($this->passRecords)) {
            return null;
        }
        
        $bestPass = null;
        $bestImprovement = -PHP_FLOAT_MAX;
        
        foreach ($this->passRecords as $pass) {
            if ($pass['scoreImprovement'] > $bestImprovement) {
                $bestImprovement = $pass['scoreImprovement'];
                $bestPass = $pass;
            }
        }
        
        return [
            'passNumber' => $bestPass['passNumber'],
            'scoreImprovement' => $bestPass['scoreImprovement'],
            'issuesResolved' => $bestPass['issuesResolved'],
            'correctionsCount' => $bestPass['correctionsCount']
        ];
    }
    
    /**
     * Find worst performing pass
     *
     * @return array Worst pass information
     */
    private function findWorstPass() {
        if (empty($this->passRecords)) {
            return null;
        }
        
        $worstPass = null;
        $worstImprovement = PHP_FLOAT_MAX;
        
        foreach ($this->passRecords as $pass) {
            if ($pass['scoreImprovement'] < $worstImprovement) {
                $worstImprovement = $pass['scoreImprovement'];
                $worstPass = $pass;
            }
        }
        
        return [
            'passNumber' => $worstPass['passNumber'],
            'scoreImprovement' => $worstPass['scoreImprovement'],
            'issuesResolved' => $worstPass['issuesResolved'],
            'correctionsCount' => $worstPass['correctionsCount']
        ];
    }
    
    /**
     * Get content history summary
     *
     * @return array Content history summary
     */
    private function getContentHistorySummary() {
        if (!$this->config['enableContentHistory']) {
            return ['status' => 'disabled'];
        }
        
        return [
            'status' => 'available',
            'totalEntries' => count($this->contentHistory),
            'entries' => array_map(function($entry) {
                return [
                    'passNumber' => $entry['passNumber'],
                    'stage' => $entry['stage'],
                    'timestamp' => $entry['timestamp'],
                    'score' => $entry['score'],
                    'contentHash' => $entry['contentHash']
                ];
            }, $this->contentHistory)
        ];
    }
    
    /**
     * Generate detailed metrics
     *
     * @return array Detailed metrics
     */
    private function generateDetailedMetrics() {
        $metrics = [
            'totalPasses' => $this->sessionData['totalPasses'],
            'totalDuration' => $this->sessionData['totalDuration'],
            'averagePassDuration' => 0,
            'totalCorrections' => $this->sessionData['totalCorrections'],
            'averageCorrectionsPerPass' => 0,
            'totalIssuesResolved' => $this->sessionData['totalIssuesResolved'],
            'averageIssuesResolvedPerPass' => 0,
            'efficiencyScore' => 0
        ];
        
        if ($this->sessionData['totalPasses'] > 0) {
            $metrics['averagePassDuration'] = $this->sessionData['totalDuration'] / $this->sessionData['totalPasses'];
            $metrics['averageCorrectionsPerPass'] = $this->sessionData['totalCorrections'] / $this->sessionData['totalPasses'];
            $metrics['averageIssuesResolvedPerPass'] = $this->sessionData['totalIssuesResolved'] / $this->sessionData['totalPasses'];
        }
        
        // Calculate efficiency score (improvement per pass)
        if ($this->sessionData['totalPasses'] > 0) {
            $metrics['efficiencyScore'] = $this->sessionData['totalImprovement'] / $this->sessionData['totalPasses'];
        }
        
        return $metrics;
    }
    
    /**
     * Generate before/after comparison
     *
     * @return array Before/after comparison
     */
    private function generateBeforeAfterComparison() {
        if (empty($this->contentHistory)) {
            return ['status' => 'no_data'];
        }
        
        $initialContent = $this->contentHistory[0] ?? null;
        $finalContent = end($this->contentHistory);
        
        if (!$initialContent || !$finalContent) {
            return ['status' => 'incomplete_data'];
        }
        
        return [
            'status' => 'available',
            'before' => [
                'score' => $initialContent['score'],
                'timestamp' => $initialContent['timestamp'],
                'contentHash' => $initialContent['contentHash']
            ],
            'after' => [
                'score' => $finalContent['score'],
                'timestamp' => $finalContent['timestamp'],
                'contentHash' => $finalContent['contentHash']
            ],
            'improvement' => $finalContent['score'] - $initialContent['score'],
            'improvementPercentage' => $initialContent['score'] > 0 ? 
                (($finalContent['score'] - $initialContent['score']) / $initialContent['score']) * 100 : 0
        ];
    }
    
    /**
     * Rollback to specific pass
     *
     * @param int $passNumber Pass number to rollback to
     * @return array|null Content at that pass, or null if not found
     */
    public function rollbackToPass($passNumber) {
        if (!$this->config['enableRollback']) {
            return null;
        }
        
        foreach ($this->contentHistory as $entry) {
            if ($entry['passNumber'] === $passNumber) {
                return $entry['content'];
            }
        }
        
        return null;
    }
    
    /**
     * Get pass records
     *
     * @return array Pass records
     */
    public function getPassRecords() {
        return $this->passRecords;
    }
    
    /**
     * Get specific pass record
     *
     * @param int $passNumber Pass number
     * @return array|null Pass record or null if not found
     */
    public function getPassRecord($passNumber) {
        return $this->passRecords[$passNumber] ?? null;
    }
    
    /**
     * Get strategy effectiveness metrics
     *
     * @return array Strategy metrics
     */
    public function getStrategyMetrics() {
        return $this->strategyMetrics;
    }
    
    /**
     * Get content history
     *
     * @return array Content history
     */
    public function getContentHistory() {
        return $this->contentHistory;
    }
    
    /**
     * Get session data
     *
     * @return array Session data
     */
    public function getSessionData() {
        return $this->sessionData;
    }
    
    /**
     * Clear all tracking data
     */
    public function clearAll() {
        $this->resetSession();
    }
    
    /**
     * Update configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
    }
    
    /**
     * Get current configuration
     *
     * @return array Current configuration
     */
    public function getConfig() {
        return $this->config;
    }
}
