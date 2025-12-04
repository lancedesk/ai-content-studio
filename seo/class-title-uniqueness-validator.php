<?php
/**
 * Title Uniqueness Validator
 *
 * Validates title uniqueness against historical titles and implements
 * title comparison algorithms for SEO compliance.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Title_Uniqueness_Validator
 *
 * Handles validation of title uniqueness and comparison algorithms
 * to ensure generated titles don't duplicate existing content.
 */
class Title_Uniqueness_Validator {

    /**
     * Minimum similarity threshold for considering titles as duplicates
     * 
     * @var float
     */
    private $similarity_threshold = 0.85;

    /**
     * WordPress database instance
     * 
     * @var wpdb
     */
    private $wpdb;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Validate if a title is unique compared to existing titles
     *
     * @param string $title The title to validate
     * @param int|null $exclude_post_id Post ID to exclude from comparison (for updates)
     * @return array Validation result with uniqueness status and similar titles
     */
    public function validate_title_uniqueness($title, $exclude_post_id = null) {
        if (empty($title)) {
            return [
                'is_unique' => false,
                'error' => 'Title cannot be empty',
                'similar_titles' => []
            ];
        }

        $similar_titles = $this->find_similar_titles($title, $exclude_post_id);
        $is_unique = empty($similar_titles);

        return [
            'is_unique' => $is_unique,
            'error' => $is_unique ? null : 'Title is too similar to existing content',
            'similar_titles' => $similar_titles,
            'similarity_threshold' => $this->similarity_threshold
        ];
    }

    /**
     * Find titles similar to the given title
     *
     * @param string $title The title to compare against
     * @param int|null $exclude_post_id Post ID to exclude from comparison
     * @return array Array of similar titles with their similarity scores
     */
    public function find_similar_titles($title, $exclude_post_id = null) {
        $existing_titles = $this->get_existing_titles($exclude_post_id);
        $similar_titles = [];

        foreach ($existing_titles as $existing_title) {
            $similarity = $this->calculate_title_similarity($title, $existing_title['title']);
            
            if ($similarity >= $this->similarity_threshold) {
                $similar_titles[] = [
                    'title' => $existing_title['title'],
                    'post_id' => $existing_title['post_id'],
                    'similarity' => $similarity,
                    'post_date' => $existing_title['post_date']
                ];
            }
        }

        // Sort by similarity score (highest first)
        usort($similar_titles, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similar_titles;
    }

    /**
     * Calculate similarity between two titles using multiple algorithms
     *
     * @param string $title1 First title
     * @param string $title2 Second title
     * @return float Similarity score between 0 and 1
     */
    public function calculate_title_similarity($title1, $title2) {
        // Normalize titles for comparison
        $normalized1 = $this->normalize_title($title1);
        $normalized2 = $this->normalize_title($title2);

        // If normalized titles are identical, return 1.0
        if ($normalized1 === $normalized2) {
            return 1.0;
        }

        // Calculate different similarity metrics
        $levenshtein_similarity = $this->calculate_levenshtein_similarity($normalized1, $normalized2);
        $word_similarity = $this->calculate_word_similarity($normalized1, $normalized2);
        $ngram_similarity = $this->calculate_ngram_similarity($normalized1, $normalized2);

        // Weighted average of different similarity measures
        $final_similarity = (
            $levenshtein_similarity * 0.3 +
            $word_similarity * 0.4 +
            $ngram_similarity * 0.3
        );

        return round($final_similarity, 3);
    }

    /**
     * Normalize title for comparison
     *
     * @param string $title The title to normalize
     * @return string Normalized title
     */
    private function normalize_title($title) {
        // Convert to lowercase
        $normalized = strtolower($title);
        
        // Remove common stop words that don't affect uniqueness
        $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = explode(' ', $normalized);
        $filtered_words = array_filter($words, function($word) use ($stop_words) {
            return !in_array(trim($word), $stop_words) && !empty(trim($word));
        });
        
        // Remove punctuation and extra spaces
        $normalized = preg_replace('/[^\w\s]/', '', implode(' ', $filtered_words));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Calculate Levenshtein-based similarity
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score between 0 and 1
     */
    private function calculate_levenshtein_similarity($str1, $str2) {
        $max_length = max(strlen($str1), strlen($str2));
        if ($max_length === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $max_length);
    }

    /**
     * Calculate word-based similarity
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score between 0 and 1
     */
    private function calculate_word_similarity($str1, $str2) {
        $words1 = array_unique(explode(' ', $str1));
        $words2 = array_unique(explode(' ', $str2));
        
        if (empty($words1) && empty($words2)) {
            return 1.0;
        }
        
        if (empty($words1) || empty($words2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        return count($intersection) / count($union);
    }

    /**
     * Calculate n-gram similarity
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @param int $n N-gram size
     * @return float Similarity score between 0 and 1
     */
    private function calculate_ngram_similarity($str1, $str2, $n = 2) {
        $ngrams1 = $this->get_ngrams($str1, $n);
        $ngrams2 = $this->get_ngrams($str2, $n);
        
        if (empty($ngrams1) && empty($ngrams2)) {
            return 1.0;
        }
        
        if (empty($ngrams1) || empty($ngrams2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($ngrams1, $ngrams2);
        $union = array_unique(array_merge($ngrams1, $ngrams2));
        
        return count($intersection) / count($union);
    }

    /**
     * Generate n-grams from a string
     *
     * @param string $str Input string
     * @param int $n N-gram size
     * @return array Array of n-grams
     */
    private function get_ngrams($str, $n) {
        $ngrams = [];
        $length = strlen($str);
        
        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = substr($str, $i, $n);
        }
        
        return $ngrams;
    }

    /**
     * Get existing titles from the database
     *
     * @param int|null $exclude_post_id Post ID to exclude from results
     * @return array Array of existing titles with post information
     */
    private function get_existing_titles($exclude_post_id = null) {
        $exclude_clause = '';
        if ($exclude_post_id) {
            $exclude_clause = $this->wpdb->prepare(' AND ID != %d', $exclude_post_id);
        }

        $query = "
            SELECT ID as post_id, post_title as title, post_date
            FROM {$this->wpdb->posts}
            WHERE post_status IN ('publish', 'draft', 'pending', 'private')
            AND post_type IN ('post', 'page')
            AND post_title != ''
            {$exclude_clause}
            ORDER BY post_date DESC
        ";

        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        return $results ?: [];
    }

    /**
     * Set similarity threshold for uniqueness validation
     *
     * @param float $threshold Similarity threshold (0.0 to 1.0)
     * @return bool True if threshold was set successfully
     */
    public function set_similarity_threshold($threshold) {
        if ($threshold >= 0.0 && $threshold <= 1.0) {
            $this->similarity_threshold = $threshold;
            return true;
        }
        return false;
    }

    /**
     * Get current similarity threshold
     *
     * @return float Current similarity threshold
     */
    public function get_similarity_threshold() {
        return $this->similarity_threshold;
    }

    /**
     * Check if a title meets basic requirements (length, keyword inclusion)
     *
     * @param string $title The title to validate
     * @param string $focus_keyword The focus keyword that should be included
     * @return array Validation result
     */
    public function validate_title_requirements($title, $focus_keyword = '') {
        $errors = [];
        $warnings = [];

        // Check title length (should be under 66 characters for SEO)
        if (strlen($title) > 66) {
            $errors[] = 'Title exceeds 66 characters (' . strlen($title) . ' characters)';
        }

        // Check if title is too short
        if (strlen($title) < 10) {
            $warnings[] = 'Title is very short (less than 10 characters)';
        }

        // Check keyword inclusion if provided
        if (!empty($focus_keyword)) {
            $title_lower = strtolower($title);
            $keyword_lower = strtolower($focus_keyword);
            
            if (strpos($title_lower, $keyword_lower) === false) {
                $errors[] = 'Title does not contain the focus keyword: ' . $focus_keyword;
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'character_count' => strlen($title)
        ];
    }
}