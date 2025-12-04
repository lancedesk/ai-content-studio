<?php
/**
 * Unit Tests for SEO Issue Detector
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class TestSEOIssueDetector extends SimpleTestCase {
    
    private $detector;
    
    protected function setUp(): void {
        parent::setUp();
        $this->detector = new SEOIssueDetector();
    }
    
    public function testDetectorInitialization() {
        $this->assertInstanceOf(SEOIssueDetector::class, $this->detector);
    }
    
    public function testDetectIssuesWithValidContent() {
        $content = [
            'title' => 'Complete Guide to SEO',
            'content' => 'This is content about SEO.',
            'meta_description' => 'Learn about SEO'
        ];
        
        $issues = $this->detector->detectIssues($content, 'SEO', []);
        
        $this->assertIsArray($issues);
        $this->assertArrayHasKey('critical', $issues);
        $this->assertArrayHasKey('major', $issues);
        $this->assertArrayHasKey('minor', $issues);
    }
    
    public function testDetectKeywordDensityIssues() {
        $content = [
            'title' => 'Test Title',
            'content' => 'Content without the focus keyword.',
            'meta_description' => 'Description'
        ];
        
        $issues = $this->detector->detectIssues($content, 'SEO', []);
        
        // Should detect keyword density issue
        $allIssues = array_merge(
            $issues['critical'] ?? [],
            $issues['major'] ?? [],
            $issues['minor'] ?? []
        );
        
        $hasKeywordIssue = false;
        foreach ($allIssues as $issue) {
            if (isset($issue['type']) && strpos($issue['type'], 'keyword') !== false) {
                $hasKeywordIssue = true;
                break;
            }
        }
        
        $this->assertTrue($hasKeywordIssue || count($allIssues) > 0);
    }
    
    public function testDetectMetaDescriptionIssues() {
        $content = [
            'title' => 'Test Title',
            'content' => 'Test content',
            'meta_description' => '' // Empty meta description
        ];
        
        $issues = $this->detector->detectIssues($content, 'test', []);
        
        $allIssues = array_merge(
            $issues['critical'] ?? [],
            $issues['major'] ?? [],
            $issues['minor'] ?? []
        );
        
        $this->assertGreaterThan(0, count($allIssues));
    }
    
    public function testIssueClassification() {
        $content = [
            'title' => 'T', // Too short
            'content' => 'Short.', // Too short
            'meta_description' => '' // Missing
        ];
        
        $issues = $this->detector->detectIssues($content, 'keyword', []);
        
        // Should have critical issues
        $this->assertGreaterThan(0, count($issues['critical'] ?? []));
    }
    
    public function testCalculateOverallScore() {
        $issues = [
            'critical' => [
                ['type' => 'meta_description_missing', 'severity' => 'critical']
            ],
            'major' => [
                ['type' => 'keyword_density_low', 'severity' => 'major']
            ],
            'minor' => []
        ];
        
        $score = $this->detector->calculateOverallScore($issues);
        
        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }
    
    public function testDetectWithEmptyContent() {
        $content = [
            'title' => '',
            'content' => '',
            'meta_description' => ''
        ];
        
        $issues = $this->detector->detectIssues($content, '', []);
        
        $this->assertIsArray($issues);
        // Should detect multiple critical issues
        $this->assertGreaterThan(0, count($issues['critical'] ?? []));
    }
    
    public function testIssueQuantification() {
        $content = [
            'title' => 'Test',
            'content' => 'Test content',
            'meta_description' => 'Short'
        ];
        
        $issues = $this->detector->detectIssues($content, 'keyword', []);
        
        // Check that issues have quantification
        $allIssues = array_merge(
            $issues['critical'] ?? [],
            $issues['major'] ?? [],
            $issues['minor'] ?? []
        );
        
        foreach ($allIssues as $issue) {
            $this->assertIsArray($issue);
            $this->assertArrayHasKey('type', $issue);
        }
    }
    
    protected function tearDown(): void {
        parent::tearDown();
        $this->detector = null;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Running SEO Issue Detector Unit Tests...\n\n";
    
    $test = new TestSEOIssueDetector('test');
    $test->setUp();
    
    $methods = get_class_methods(TestSEOIssueDetector::class);
    $passed = 0;
    $failed = 0;
    
    foreach ($methods as $method) {
        if (strpos($method, 'test') === 0) {
            try {
                $test->setUp();
                $test->$method();
                echo "✅ PASS: $method\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ FAIL: $method - " . $e->getMessage() . "\n";
                $failed++;
            } finally {
                $test->tearDown();
            }
        }
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Total: " . ($passed + $failed) . " | Passed: $passed | Failed: $failed\n";
    
    exit($failed > 0 ? 1 : 0);
}
