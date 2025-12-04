<?php
/**
 * Simple test for SEO Issue Detector
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== SEO Issue Detector Test ===\n\n";

// Test basic functionality
$content = [
    'title' => 'SEO SEO SEO SEO SEO Guide',
    'content' => '<p>SEO SEO SEO optimization SEO guide SEO tips SEO SEO best practices SEO SEO.</p>',
    'meta_description' => 'Short'
];

$detector = new SEOIssueDetector();
$result = $detector->detectAllIssues($content, 'SEO', []);

echo "Result structure:\n";
var_dump(array_keys($result));

echo "\nNumber of issues detected: " . count($result['issues']) . "\n";

echo "\nIssue types detected:\n";
foreach ($result['issues'] as $issue) {
    echo "- " . $issue->type . " (severity: " . $issue->severity . ")\n";
}

echo "\nCompliance score: " . $result['complianceScore'] . "\n";

echo "\n=== Test Complete ===\n";