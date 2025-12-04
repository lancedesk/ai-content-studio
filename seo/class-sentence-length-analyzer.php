<?php
/**
 * Sentence Length Analyzer Class
 *
 * Analyzes sentence length distribution in content for readability compliance.
 * Identifies sentences that exceed optimal length for readability.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SentenceLengthAnalyzer
 *
 * Analyzes content for sentence length distribution and readability metrics
 */
class SentenceLengthAnalyzer {
    
    /**
     * @var int Maximum recommended sentence length in words
     */
    private $maxSentenceLength;
    
    /**
     * @var float Maximum allowed percentage of long sentences
     */
    private $maxLongSentencePercentage;
    
    /**
     * @var int Optimal sentence length range minimum
     */
    private $optimalMinLength;
    
    /**
     * @var int Optimal sentence length range maximum
     */
    private $optimalMaxLength;
    
    /**
     * Constructor
     *
     * @param int $maxSentenceLength Maximum sentence length in words (default: 20)
     * @param float $maxLongSentencePercentage Maximum percentage of long sentences (default: 25.0)
     * @param int $optimalMinLength Optimal minimum sentence length (default: 8)
     * @param int $optimalMaxLength Optimal maximum sentence length (default: 15)
     */
    public function __construct($maxSentenceLength = 20, $maxLongSentencePercentage = 25.0, $optimalMinLength = 8, $optimalMaxLength = 15) {
        $this->maxSentenceLength = $maxSentenceLength;
        $this->maxLongSentencePercentage = $maxLongSentencePercentage;
        $this->optimalMinLength = $optimalMinLength;
        $this->optimalMaxLength = $optimalMaxLength;
    }
    
    /**
     * Analyze sentence length distribution in content
     *
     * @param string $content The content to analyze
     * @return array Detailed sentence length analysis
     */
    public function analyze($content) {
        $sentences = $this->getSentences($content);
        $totalSentences = count($sentences);
        
        if ($totalSentences === 0) {
            return $this->createEmptyResult();
        }
        
        $sentenceAnalysis = [];
        $longSentences = [];
        $shortSentences = [];
        $optimalSentences = [];
        $wordCounts = [];
        
        foreach ($sentences as $index => $sentence) {
            $wordCount = $this->countWords($sentence);
            $wordCounts[] = $wordCount;
            
            $sentenceData = [
                'index' => $index,
                'sentence' => trim($sentence),
                'wordCount' => $wordCount,
                'category' => $this->categorizeSentence($wordCount)
            ];
            
            $sentenceAnalysis[] = $sentenceData;
            
            // Categorize sentences
            if ($wordCount > $this->maxSentenceLength) {
                $longSentences[] = $sentenceData;
            } elseif ($wordCount < $this->optimalMinLength) {
                $shortSentences[] = $sentenceData;
            } elseif ($wordCount >= $this->optimalMinLength && $wordCount <= $this->optimalMaxLength) {
                $optimalSentences[] = $sentenceData;
            }
        }
        
        $longSentenceCount = count($longSentences);
        $longSentencePercentage = ($longSentenceCount / $totalSentences) * 100;
        
        // Calculate statistics
        $averageLength = array_sum($wordCounts) / count($wordCounts);
        $medianLength = $this->calculateMedian($wordCounts);
        
        return [
            'totalSentences' => $totalSentences,
            'longSentences' => $longSentenceCount,
            'longSentencePercentage' => $longSentencePercentage,
            'shortSentences' => count($shortSentences),
            'optimalSentences' => count($optimalSentences),
            'averageLength' => $averageLength,
            'medianLength' => $medianLength,
            'isCompliant' => $longSentencePercentage <= $this->maxLongSentencePercentage,
            'maxAllowedPercentage' => $this->maxLongSentencePercentage,
            'maxSentenceLength' => $this->maxSentenceLength,
            'longSentenceDetails' => $longSentences,
            'shortSentenceDetails' => $shortSentences,
            'sentenceDistribution' => $this->calculateDistribution($wordCounts),
            'suggestions' => $this->generateSuggestions($longSentences, $shortSentences, $longSentencePercentage, $averageLength)
        ];
    }
    
    /**
     * Check if content meets sentence length requirements
     *
     * @param string $content The content to check
     * @return bool True if sentence length distribution is acceptable
     */
    public function isCompliant($content) {
        $analysis = $this->analyze($content);
        return $analysis['isCompliant'];
    }
    
    /**
     * Get percentage of long sentences in content
     *
     * @param string $content The content to analyze
     * @return float Percentage of sentences exceeding maximum length
     */
    public function getLongSentencePercentage($content) {
        $analysis = $this->analyze($content);
        return $analysis['longSentencePercentage'];
    }
    
    /**
     * Get average sentence length for content
     *
     * @param string $content The content to analyze
     * @return float Average sentence length in words
     */
    public function getAverageSentenceLength($content) {
        $analysis = $this->analyze($content);
        return $analysis['averageLength'];
    }
    
    /**
     * Count words in a sentence
     *
     * @param string $sentence The sentence to count words in
     * @return int Number of words
     */
    private function countWords($sentence) {
        // Remove extra whitespace and count words
        $cleanSentence = preg_replace('/\s+/', ' ', trim($sentence));
        return str_word_count($cleanSentence);
    }
    
    /**
     * Categorize sentence based on length
     *
     * @param int $wordCount Number of words in sentence
     * @return string Category (short, optimal, long, very_long)
     */
    private function categorizeSentence($wordCount) {
        if ($wordCount < $this->optimalMinLength) {
            return 'short';
        } elseif ($wordCount >= $this->optimalMinLength && $wordCount <= $this->optimalMaxLength) {
            return 'optimal';
        } elseif ($wordCount <= $this->maxSentenceLength) {
            return 'acceptable';
        } else {
            return 'long';
        }
    }
    
    /**
     * Calculate median value from array of numbers
     *
     * @param array $numbers Array of numbers
     * @return float Median value
     */
    private function calculateMedian($numbers) {
        sort($numbers);
        $count = count($numbers);
        
        if ($count === 0) {
            return 0;
        }
        
        $middle = floor($count / 2);
        
        if ($count % 2 === 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        } else {
            return $numbers[$middle];
        }
    }
    
    /**
     * Calculate sentence length distribution
     *
     * @param array $wordCounts Array of word counts
     * @return array Distribution statistics
     */
    private function calculateDistribution($wordCounts) {
        $distribution = [
            'short' => 0,      // < optimal min
            'optimal' => 0,    // optimal min to optimal max
            'acceptable' => 0, // optimal max to max length
            'long' => 0        // > max length
        ];
        
        foreach ($wordCounts as $count) {
            if ($count < $this->optimalMinLength) {
                $distribution['short']++;
            } elseif ($count >= $this->optimalMinLength && $count <= $this->optimalMaxLength) {
                $distribution['optimal']++;
            } elseif ($count <= $this->maxSentenceLength) {
                $distribution['acceptable']++;
            } else {
                $distribution['long']++;
            }
        }
        
        $total = array_sum($distribution);
        
        // Convert to percentages
        foreach ($distribution as $key => $value) {
            $distribution[$key . '_percentage'] = $total > 0 ? ($value / $total) * 100 : 0;
        }
        
        return $distribution;
    }
    
    /**
     * Generate suggestions for improving sentence length
     *
     * @param array $longSentences Array of long sentences
     * @param array $shortSentences Array of short sentences
     * @param float $longSentencePercentage Percentage of long sentences
     * @param float $averageLength Average sentence length
     * @return array Array of improvement suggestions
     */
    private function generateSuggestions($longSentences, $shortSentences, $longSentencePercentage, $averageLength) {
        $suggestions = [];
        
        // Long sentence suggestions
        if ($longSentencePercentage > $this->maxLongSentencePercentage) {
            $excessPercentage = $longSentencePercentage - $this->maxLongSentencePercentage;
            $suggestions[] = sprintf(
                'Long sentence usage is %.1f%% above the recommended maximum of %.1f%%. Consider breaking down some sentences.',
                $excessPercentage,
                $this->maxLongSentencePercentage
            );
            
            if (!empty($longSentences)) {
                $suggestions[] = 'Examples of sentences to shorten:';
                $exampleCount = min(3, count($longSentences));
                for ($i = 0; $i < $exampleCount; $i++) {
                    $sentence = $longSentences[$i]['sentence'];
                    $wordCount = $longSentences[$i]['wordCount'];
                    $suggestions[] = sprintf('• %d words: "%s"', $wordCount, substr($sentence, 0, 80) . (strlen($sentence) > 80 ? '...' : ''));
                }
            }
            
            $suggestions[] = 'Tips: Use periods instead of commas, break complex ideas into multiple sentences, remove unnecessary words.';
        }
        
        // Average length feedback
        if ($averageLength > $this->optimalMaxLength) {
            $suggestions[] = sprintf(
                'Average sentence length (%.1f words) is above optimal range (%d-%d words). Consider shortening some sentences.',
                $averageLength,
                $this->optimalMinLength,
                $this->optimalMaxLength
            );
        } elseif ($averageLength < $this->optimalMinLength) {
            $suggestions[] = sprintf(
                'Average sentence length (%.1f words) is below optimal range (%d-%d words). Consider combining some short sentences.',
                $averageLength,
                $this->optimalMinLength,
                $this->optimalMaxLength
            );
        } else {
            $suggestions[] = sprintf(
                'Good sentence length variety! Average length (%.1f words) is within the optimal range.',
                $averageLength
            );
        }
        
        // Short sentence suggestions
        if (count($shortSentences) > 0) {
            $shortPercentage = (count($shortSentences) / (count($longSentences) + count($shortSentences) + 1)) * 100;
            if ($shortPercentage > 30) {
                $suggestions[] = sprintf(
                    'Consider combining some very short sentences (%.1f%% of total) to improve flow.',
                    $shortPercentage
                );
            }
        }
        
        return $suggestions;
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
            if (strlen($trimmed) > 5) { // Ignore very short fragments
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
            'longSentences' => 0,
            'longSentencePercentage' => 0.0,
            'shortSentences' => 0,
            'optimalSentences' => 0,
            'averageLength' => 0.0,
            'medianLength' => 0.0,
            'isCompliant' => true,
            'maxAllowedPercentage' => $this->maxLongSentencePercentage,
            'maxSentenceLength' => $this->maxSentenceLength,
            'longSentenceDetails' => [],
            'shortSentenceDetails' => [],
            'sentenceDistribution' => [],
            'suggestions' => ['No content to analyze.']
        ];
    }
    
    /**
     * Update sentence length constraints
     *
     * @param int $maxLength Maximum sentence length
     * @param float $maxPercentage Maximum percentage of long sentences
     * @param int $optimalMin Optimal minimum length
     * @param int $optimalMax Optimal maximum length
     */
    public function updateConstraints($maxLength, $maxPercentage, $optimalMin = null, $optimalMax = null) {
        $this->maxSentenceLength = $maxLength;
        $this->maxLongSentencePercentage = $maxPercentage;
        
        if ($optimalMin !== null) {
            $this->optimalMinLength = $optimalMin;
        }
        
        if ($optimalMax !== null) {
            $this->optimalMaxLength = $optimalMax;
        }
    }
    
    /**
     * Get current constraints
     *
     * @return array Current constraint settings
     */
    public function getConstraints() {
        return [
            'maxSentenceLength' => $this->maxSentenceLength,
            'maxLongSentencePercentage' => $this->maxLongSentencePercentage,
            'optimalMinLength' => $this->optimalMinLength,
            'optimalMaxLength' => $this->optimalMaxLength
        ];
    }
}