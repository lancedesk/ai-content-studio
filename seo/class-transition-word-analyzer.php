<?php
/**
 * Transition Word Analyzer Class
 *
 * Analyzes transition word usage in content for readability and flow compliance.
 * Identifies sentences that lack proper transitions and provides improvement suggestions.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class TransitionWordAnalyzer
 *
 * Analyzes content for transition word usage and readability flow
 */
class TransitionWordAnalyzer {
    
    /**
     * @var float Minimum required percentage of sentences with transition words
     */
    private $minTransitionPercentage;
    
    /**
     * @var array Comprehensive list of transition words and phrases
     */
    private $transitionWords;
    
    /**
     * @var array Transition words categorized by type
     */
    private $transitionCategories;
    
    /**
     * Constructor
     *
     * @param float $minTransitionPercentage Minimum required percentage (default: 30.0)
     */
    public function __construct($minTransitionPercentage = 30.0) {
        $this->minTransitionPercentage = $minTransitionPercentage;
        $this->initializeTransitionWords();
    }
    
    /**
     * Initialize transition words and categories
     */
    private function initializeTransitionWords() {
        $this->transitionCategories = [
            'addition' => [
                'also', 'additionally', 'furthermore', 'moreover', 'besides',
                'in addition', 'as well as', 'along with', 'not only', 'plus'
            ],
            'contrast' => [
                'however', 'nevertheless', 'nonetheless', 'on the other hand',
                'in contrast', 'conversely', 'although', 'though', 'despite',
                'while', 'whereas', 'but', 'yet', 'still'
            ],
            'cause_effect' => [
                'therefore', 'consequently', 'as a result', 'thus', 'hence',
                'accordingly', 'for this reason', 'because of this', 'due to',
                'since', 'because', 'so'
            ],
            'sequence' => [
                'first', 'second', 'third', 'next', 'then', 'after', 'before',
                'finally', 'lastly', 'meanwhile', 'subsequently', 'previously',
                'initially', 'ultimately', 'eventually'
            ],
            'example' => [
                'for example', 'for instance', 'such as', 'including',
                'specifically', 'in particular', 'namely', 'that is',
                'to illustrate', 'as an example'
            ],
            'emphasis' => [
                'indeed', 'certainly', 'obviously', 'clearly', 'undoubtedly',
                'without doubt', 'in fact', 'actually', 'definitely',
                'absolutely', 'particularly', 'especially'
            ],
            'summary' => [
                'in conclusion', 'to conclude', 'in summary', 'to summarize',
                'overall', 'in general', 'on the whole', 'all in all',
                'to sum up', 'in short', 'briefly'
            ],
            'comparison' => [
                'similarly', 'likewise', 'in the same way', 'equally',
                'compared to', 'in comparison', 'just as', 'like',
                'correspondingly', 'by the same token'
            ]
        ];
        
        // Flatten all transition words into a single array
        $this->transitionWords = [];
        foreach ($this->transitionCategories as $category => $words) {
            $this->transitionWords = array_merge($this->transitionWords, $words);
        }
        
        // Sort by length (longest first) for better matching
        usort($this->transitionWords, function($a, $b) {
            return strlen($b) - strlen($a);
        });
    }
    
    /**
     * Analyze transition word usage in content
     *
     * @param string $content The content to analyze
     * @return array Detailed transition word analysis
     */
    public function analyze($content) {
        $sentences = $this->getSentences($content);
        $totalSentences = count($sentences);
        
        if ($totalSentences === 0) {
            return $this->createEmptyResult();
        }
        
        $sentencesWithTransitions = [];
        $sentencesWithoutTransitions = [];
        $transitionUsage = [];
        $categoryUsage = [];
        
        // Initialize category usage counters
        foreach ($this->transitionCategories as $category => $words) {
            $categoryUsage[$category] = 0;
        }
        
        foreach ($sentences as $index => $sentence) {
            $transitionInfo = $this->analyzeTransitionsInSentence($sentence);
            
            $sentenceData = [
                'index' => $index,
                'sentence' => trim($sentence),
                'hasTransition' => !empty($transitionInfo['transitions']),
                'transitions' => $transitionInfo['transitions']
            ];
            
            if ($sentenceData['hasTransition']) {
                $sentencesWithTransitions[] = $sentenceData;
                
                // Count transition usage
                foreach ($transitionInfo['transitions'] as $transition) {
                    $word = $transition['word'];
                    $category = $transition['category'];
                    
                    if (!isset($transitionUsage[$word])) {
                        $transitionUsage[$word] = 0;
                    }
                    $transitionUsage[$word]++;
                    $categoryUsage[$category]++;
                }
            } else {
                $sentencesWithoutTransitions[] = $sentenceData;
            }
        }
        
        $transitionCount = count($sentencesWithTransitions);
        $transitionPercentage = ($transitionCount / $totalSentences) * 100;
        
        return [
            'totalSentences' => $totalSentences,
            'sentencesWithTransitions' => $transitionCount,
            'transitionPercentage' => $transitionPercentage,
            'isCompliant' => $transitionPercentage >= $this->minTransitionPercentage,
            'minRequiredPercentage' => $this->minTransitionPercentage,
            'sentencesWithTransitionDetails' => $sentencesWithTransitions,
            'sentencesWithoutTransitionDetails' => $sentencesWithoutTransitions,
            'transitionUsage' => $transitionUsage,
            'categoryUsage' => $categoryUsage,
            'categoryDistribution' => $this->calculateCategoryDistribution($categoryUsage),
            'suggestions' => $this->generateSuggestions($sentencesWithoutTransitions, $transitionPercentage, $categoryUsage)
        ];
    }
    
    /**
     * Check if content meets transition word requirements
     *
     * @param string $content The content to check
     * @return bool True if transition word usage is sufficient
     */
    public function isCompliant($content) {
        $analysis = $this->analyze($content);
        return $analysis['isCompliant'];
    }
    
    /**
     * Get transition word percentage for content
     *
     * @param string $content The content to analyze
     * @return float Percentage of sentences with transition words
     */
    public function getTransitionPercentage($content) {
        $analysis = $this->analyze($content);
        return $analysis['transitionPercentage'];
    }
    
    /**
     * Analyze a single sentence for transition words
     *
     * @param string $sentence The sentence to analyze
     * @return array Analysis result with found transitions
     */
    private function analyzeTransitionsInSentence($sentence) {
        $foundTransitions = [];
        $lowerSentence = strtolower($sentence);
        
        // Check for each transition word/phrase
        foreach ($this->transitionWords as $transition) {
            $pattern = '/\b' . preg_quote($transition, '/') . '\b/i';
            
            if (preg_match($pattern, $sentence, $matches, PREG_OFFSET_CAPTURE)) {
                $category = $this->getTransitionCategory($transition);
                $foundTransitions[] = [
                    'word' => $transition,
                    'match' => $matches[0][0],
                    'position' => $matches[0][1],
                    'category' => $category
                ];
            }
        }
        
        // Remove duplicates (in case of overlapping matches)
        $foundTransitions = $this->removeDuplicateTransitions($foundTransitions);
        
        return [
            'transitions' => $foundTransitions,
            'count' => count($foundTransitions)
        ];
    }
    
    /**
     * Get the category of a transition word
     *
     * @param string $word The transition word
     * @return string The category name
     */
    private function getTransitionCategory($word) {
        foreach ($this->transitionCategories as $category => $words) {
            if (in_array($word, $words)) {
                return $category;
            }
        }
        return 'other';
    }
    
    /**
     * Remove duplicate transitions from the same position
     *
     * @param array $transitions Array of found transitions
     * @return array Filtered transitions
     */
    private function removeDuplicateTransitions($transitions) {
        $unique = [];
        $positions = [];
        
        foreach ($transitions as $transition) {
            $pos = $transition['position'];
            $length = strlen($transition['word']);
            
            // Check if this position overlaps with existing transitions
            $overlaps = false;
            foreach ($positions as $existingPos => $existingLength) {
                if (($pos >= $existingPos && $pos < $existingPos + $existingLength) ||
                    ($existingPos >= $pos && $existingPos < $pos + $length)) {
                    $overlaps = true;
                    break;
                }
            }
            
            if (!$overlaps) {
                $unique[] = $transition;
                $positions[$pos] = $length;
            }
        }
        
        return $unique;
    }
    
    /**
     * Calculate category distribution percentages
     *
     * @param array $categoryUsage Category usage counts
     * @return array Category distribution with percentages
     */
    private function calculateCategoryDistribution($categoryUsage) {
        $total = array_sum($categoryUsage);
        $distribution = [];
        
        foreach ($categoryUsage as $category => $count) {
            $distribution[$category] = [
                'count' => $count,
                'percentage' => $total > 0 ? ($count / $total) * 100 : 0
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Generate suggestions for improving transition word usage
     *
     * @param array $sentencesWithoutTransitions Sentences lacking transitions
     * @param float $transitionPercentage Current transition percentage
     * @param array $categoryUsage Category usage statistics
     * @return array Array of improvement suggestions
     */
    private function generateSuggestions($sentencesWithoutTransitions, $transitionPercentage, $categoryUsage) {
        $suggestions = [];
        
        if ($transitionPercentage < $this->minTransitionPercentage) {
            $deficit = $this->minTransitionPercentage - $transitionPercentage;
            $suggestions[] = sprintf(
                'Transition word usage is %.1f%% below the recommended minimum of %.1f%%. Consider adding transitions to improve flow.',
                $deficit,
                $this->minTransitionPercentage
            );
            
            // Suggest specific sentences to improve
            if (!empty($sentencesWithoutTransitions)) {
                $suggestions[] = 'Consider adding transitions to these sentences:';
                $exampleCount = min(3, count($sentencesWithoutTransitions));
                for ($i = 0; $i < $exampleCount; $i++) {
                    $sentence = $sentencesWithoutTransitions[$i]['sentence'];
                    $suggestions[] = sprintf('• "%s"', substr($sentence, 0, 80) . (strlen($sentence) > 80 ? '...' : ''));
                }
            }
        } else {
            $suggestions[] = sprintf(
                'Good transition word usage! %.1f%% of sentences contain transitions (above the %.1f%% minimum).',
                $transitionPercentage,
                $this->minTransitionPercentage
            );
        }
        
        // Analyze category balance
        $categoryBalance = $this->analyzeCategoryBalance($categoryUsage);
        if (!empty($categoryBalance['underused'])) {
            $suggestions[] = 'Consider using more variety in transition types:';
            foreach ($categoryBalance['underused'] as $category) {
                $examples = array_slice($this->transitionCategories[$category], 0, 3);
                $suggestions[] = sprintf('• %s transitions: %s', ucfirst(str_replace('_', ' ', $category)), implode(', ', $examples));
            }
        }
        
        // General tips
        if ($transitionPercentage < $this->minTransitionPercentage) {
            $suggestions[] = 'Tips: Use transitions to connect ideas, show relationships between sentences, and guide readers through your content.';
        }
        
        return $suggestions;
    }
    
    /**
     * Analyze balance of transition categories
     *
     * @param array $categoryUsage Category usage counts
     * @return array Analysis of category balance
     */
    private function analyzeCategoryBalance($categoryUsage) {
        $total = array_sum($categoryUsage);
        $underused = [];
        $overused = [];
        
        if ($total === 0) {
            return ['underused' => array_keys($this->transitionCategories), 'overused' => []];
        }
        
        foreach ($categoryUsage as $category => $count) {
            $percentage = ($count / $total) * 100;
            
            // Categories with less than 5% usage are considered underused
            if ($percentage < 5 && $count === 0) {
                $underused[] = $category;
            }
            
            // Categories with more than 40% usage might be overused
            if ($percentage > 40) {
                $overused[] = $category;
            }
        }
        
        return [
            'underused' => $underused,
            'overused' => $overused
        ];
    }
    
    /**
     * Get sentences from content
     *
     * @param string $content The content to split into sentences
     * @return array Array of sentences
     */
    private function getSentences($content) {
        // Remove HTML tags for analysis
        $cleanContent = strip_tags($content);
        
        // Split by sentence endings
        $sentences = preg_split('/[.!?]+/', $cleanContent, -1, PREG_SPLIT_NO_EMPTY);
        
        // Clean up sentences and filter out very short ones
        $cleanSentences = [];
        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            if (strlen($trimmed) > 10) { // Ignore very short fragments
                $cleanSentences[] = $trimmed;
            }
        }
        
        return $cleanSentences;
    }
    
    /**
     * Create empty result structure
     *
     * @return array Empty analysis result
     */
    private function createEmptyResult() {
        return [
            'totalSentences' => 0,
            'sentencesWithTransitions' => 0,
            'transitionPercentage' => 0.0,
            'isCompliant' => false,
            'minRequiredPercentage' => $this->minTransitionPercentage,
            'sentencesWithTransitionDetails' => [],
            'sentencesWithoutTransitionDetails' => [],
            'transitionUsage' => [],
            'categoryUsage' => [],
            'categoryDistribution' => [],
            'suggestions' => ['No content to analyze.']
        ];
    }
    
    /**
     * Update minimum transition percentage requirement
     *
     * @param float $minPercentage New minimum percentage
     */
    public function setMinTransitionPercentage($minPercentage) {
        $this->minTransitionPercentage = $minPercentage;
    }
    
    /**
     * Get current minimum transition percentage requirement
     *
     * @return float Current minimum percentage
     */
    public function getMinTransitionPercentage() {
        return $this->minTransitionPercentage;
    }
    
    /**
     * Get available transition words by category
     *
     * @param string $category Optional category filter
     * @return array Transition words (all or by category)
     */
    public function getTransitionWords($category = null) {
        if ($category && isset($this->transitionCategories[$category])) {
            return $this->transitionCategories[$category];
        }
        
        return $this->transitionCategories;
    }
}