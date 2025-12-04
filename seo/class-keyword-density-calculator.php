<?php
/**
 * Keyword Density Calculator
 *
 * Calculates keyword density including synonyms and analyzes subheading usage.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Keyword_Density_Calculator
 *
 * Handles calculation of keyword density including synonyms and subheading analysis.
 */
class Keyword_Density_Calculator {

    /**
     * Calculate keyword density for given content
     *
     * @param string $content The content to analyze
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @return array Density metrics including overall and subheading density
     */
    public function calculate_density($content, $focus_keyword, $synonyms = []) {
        if (empty($content) || empty($focus_keyword)) {
            return [
                'overall_density' => 0.0,
                'subheading_density' => 0.0,
                'keyword_count' => 0,
                'total_words' => 0,
                'subheading_keyword_count' => 0,
                'total_subheadings' => 0
            ];
        }

        // Normalize content and keywords
        $content_lower = strtolower($content);
        $focus_keyword_lower = strtolower($focus_keyword);
        $synonyms_lower = array_map('strtolower', $synonyms);
        
        // All keywords to search for (focus + synonyms)
        $all_keywords = array_merge([$focus_keyword_lower], $synonyms_lower);
        
        // Calculate total word count
        $total_words = $this->count_words($content);
        
        // Count keyword occurrences in entire content
        $keyword_count = $this->count_keyword_occurrences($content_lower, $all_keywords);
        
        // Calculate overall density
        $overall_density = $total_words > 0 ? ($keyword_count / $total_words) * 100 : 0.0;
        
        // Extract and analyze subheadings
        $subheading_analysis = $this->analyze_subheadings($content, $all_keywords);
        
        return [
            'overall_density' => round($overall_density, 2),
            'subheading_density' => $subheading_analysis['density'],
            'keyword_count' => $keyword_count,
            'total_words' => $total_words,
            'subheading_keyword_count' => $subheading_analysis['keyword_count'],
            'total_subheadings' => $subheading_analysis['total_subheadings']
        ];
    }

    /**
     * Count total words in content
     *
     * @param string $content The content to count words in
     * @return int Word count
     */
    private function count_words($content) {
        // Remove HTML tags and normalize whitespace
        $clean_content = strip_tags($content);
        $clean_content = preg_replace('/\s+/', ' ', trim($clean_content));
        
        if (empty($clean_content)) {
            return 0;
        }
        
        return str_word_count($clean_content);
    }

    /**
     * Count keyword occurrences including synonyms
     *
     * @param string $content_lower Lowercase content
     * @param array $keywords_lower Array of lowercase keywords
     * @return int Total keyword occurrences
     */
    private function count_keyword_occurrences($content_lower, $keywords_lower) {
        $total_count = 0;
        
        foreach ($keywords_lower as $keyword) {
            // Use word boundaries to match whole words only
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/';
            $matches = preg_match_all($pattern, $content_lower);
            $total_count += $matches;
        }
        
        return $total_count;
    }

    /**
     * Analyze keyword usage in subheadings
     *
     * @param string $content The content to analyze
     * @param array $keywords_lower Array of lowercase keywords
     * @return array Subheading analysis results
     */
    private function analyze_subheadings($content, $keywords_lower) {
        // Extract subheadings (H1-H6)
        $subheading_pattern = '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i';
        preg_match_all($subheading_pattern, $content, $matches);
        
        $subheadings = $matches[1];
        $total_subheadings = count($subheadings);
        
        if ($total_subheadings === 0) {
            return [
                'density' => 0.0,
                'keyword_count' => 0,
                'total_subheadings' => 0
            ];
        }
        
        $subheadings_with_keywords = 0;
        
        foreach ($subheadings as $subheading) {
            $subheading_lower = strtolower(strip_tags($subheading));
            
            // Check if any keyword appears in this subheading
            foreach ($keywords_lower as $keyword) {
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/';
                if (preg_match($pattern, $subheading_lower)) {
                    $subheadings_with_keywords++;
                    break; // Count each subheading only once
                }
            }
        }
        
        $density = ($subheadings_with_keywords / $total_subheadings) * 100;
        
        return [
            'density' => round($density, 2),
            'keyword_count' => $subheadings_with_keywords,
            'total_subheadings' => $total_subheadings
        ];
    }

    /**
     * Check if keyword density is within acceptable range
     *
     * @param float $density The calculated density percentage
     * @param float $min_density Minimum acceptable density (default 0.5%)
     * @param float $max_density Maximum acceptable density (default 2.5%)
     * @return array Status and recommendations
     */
    public function validate_density($density, $min_density = 0.5, $max_density = 2.5) {
        $status = 'optimal';
        $recommendation = '';
        
        if ($density < $min_density) {
            $status = 'too_low';
            $recommendation = 'Consider adding more keyword variations to reach optimal density.';
        } elseif ($density > $max_density) {
            $status = 'too_high';
            $recommendation = 'Reduce keyword usage to avoid over-optimization penalties.';
        } else {
            $recommendation = 'Keyword density is within optimal range.';
        }
        
        return [
            'status' => $status,
            'recommendation' => $recommendation,
            'is_valid' => $status === 'optimal'
        ];
    }

    /**
     * Check if subheading keyword usage is excessive
     *
     * @param float $subheading_density Percentage of subheadings with keywords
     * @param float $max_threshold Maximum acceptable percentage (default 75%)
     * @return array Status and recommendations
     */
    public function validate_subheading_density($subheading_density, $max_threshold = 75.0) {
        $is_excessive = $subheading_density > $max_threshold;
        
        return [
            'is_excessive' => $is_excessive,
            'recommendation' => $is_excessive 
                ? 'Reduce keyword usage in subheadings to avoid over-optimization.'
                : 'Subheading keyword usage is within acceptable limits.',
            'current_density' => $subheading_density,
            'threshold' => $max_threshold
        ];
    }
}