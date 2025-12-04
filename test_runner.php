<?php
require_once __DIR__ . '/tests/bootstrap.php';
require_once __DIR__ . '/tests/TestContentGenerator.php';

echo "Running manual test...\n";

$test = new TestContentGenerator();

try {
    $test->test_parse_generated_content_basic();
    echo "✓ test_parse_generated_content_basic passed\n";
} catch (Exception $e) {
    echo "✗ test_parse_generated_content_basic failed: " . $e->getMessage() . "\n";
}

echo "Manual test completed.\n";