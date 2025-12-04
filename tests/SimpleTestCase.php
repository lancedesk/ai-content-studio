<?php
/**
 * Simple Test Case Base Class
 *
 * Provides basic assertion methods for unit testing without PHPUnit
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

class SimpleTestCase {
    
    protected function setUp(): void {
        // Override in child classes
    }
    
    protected function tearDown(): void {
        // Override in child classes
    }
    
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
        }
    }
    
    protected function assertNotEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            throw new Exception($message ?: "Expected value to not equal " . var_export($expected, true));
        }
    }
    
    protected function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception($message ?: "Expected true but got false");
        }
    }
    
    protected function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception($message ?: "Expected false but got true");
        }
    }
    
    protected function assertNull($value, $message = '') {
        if ($value !== null) {
            throw new Exception($message ?: "Expected null but got " . var_export($value, true));
        }
    }
    
    protected function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception($message ?: "Expected non-null value");
        }
    }
    
    protected function assertIsArray($value, $message = '') {
        if (!is_array($value)) {
            throw new Exception($message ?: "Expected array but got " . gettype($value));
        }
    }
    
    protected function assertIsString($value, $message = '') {
        if (!is_string($value)) {
            throw new Exception($message ?: "Expected string but got " . gettype($value));
        }
    }
    
    protected function assertIsInt($value, $message = '') {
        if (!is_int($value)) {
            throw new Exception($message ?: "Expected integer but got " . gettype($value));
        }
    }
    
    protected function assertIsFloat($value, $message = '') {
        if (!is_float($value)) {
            throw new Exception($message ?: "Expected float but got " . gettype($value));
        }
    }
    
    protected function assertIsBool($value, $message = '') {
        if (!is_bool($value)) {
            throw new Exception($message ?: "Expected boolean but got " . gettype($value));
        }
    }
    
    protected function assertInstanceOf($expected, $actual, $message = '') {
        if (!($actual instanceof $expected)) {
            throw new Exception($message ?: "Expected instance of " . $expected . " but got " . get_class($actual));
        }
    }
    
    protected function assertArrayHasKey($key, $array, $message = '') {
        if (!is_array($array) || !array_key_exists($key, $array)) {
            throw new Exception($message ?: "Array does not have key: " . $key);
        }
    }
    
    protected function assertArrayNotHasKey($key, $array, $message = '') {
        if (is_array($array) && array_key_exists($key, $array)) {
            throw new Exception($message ?: "Array should not have key: " . $key);
        }
    }
    
    protected function assertContains($needle, $haystack, $message = '') {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                throw new Exception($message ?: "Array does not contain: " . var_export($needle, true));
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                throw new Exception($message ?: "String does not contain: " . $needle);
            }
        } else {
            throw new Exception("assertContains requires array or string");
        }
    }
    
    protected function assertNotContains($needle, $haystack, $message = '') {
        if (is_array($haystack)) {
            if (in_array($needle, $haystack)) {
                throw new Exception($message ?: "Array should not contain: " . var_export($needle, true));
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, $needle) !== false) {
                throw new Exception($message ?: "String should not contain: " . $needle);
            }
        } else {
            throw new Exception("assertNotContains requires array or string");
        }
    }
    
    protected function assertStringContainsString($needle, $haystack, $message = '') {
        if (!is_string($haystack) || strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String does not contain: " . $needle);
        }
    }
    
    protected function assertStringContains($needle, $haystack, $message = '') {
        return $this->assertStringContainsString($needle, $haystack, $message);
    }
    
    protected function assertStringNotContains($needle, $haystack, $message = '') {
        if (is_string($haystack) && strpos($haystack, $needle) !== false) {
            throw new Exception($message ?: "String should not contain: " . $needle);
        }
    }
    
    protected function assertEmpty($value, $message = '') {
        if (!empty($value)) {
            throw new Exception($message ?: "Expected empty value but got " . var_export($value, true));
        }
    }
    
    protected function assertNotEmpty($value, $message = '') {
        if (empty($value)) {
            throw new Exception($message ?: "Expected non-empty value");
        }
    }
    
    protected function assertGreaterThan($expected, $actual, $message = '') {
        if ($actual <= $expected) {
            throw new Exception($message ?: "Expected $actual to be greater than $expected");
        }
    }
    
    protected function assertGreaterThanOrEqual($expected, $actual, $message = '') {
        if ($actual < $expected) {
            throw new Exception($message ?: "Expected $actual to be greater than or equal to $expected");
        }
    }
    
    protected function assertLessThan($expected, $actual, $message = '') {
        if ($actual >= $expected) {
            throw new Exception($message ?: "Expected $actual to be less than $expected");
        }
    }
    
    protected function assertLessThanOrEqual($expected, $actual, $message = '') {
        if ($actual > $expected) {
            throw new Exception($message ?: "Expected $actual to be less than or equal to $expected");
        }
    }
    
    protected function assertMatchesRegularExpression($pattern, $string, $message = '') {
        if (!preg_match($pattern, $string)) {
            throw new Exception($message ?: "String '$string' does not match pattern '$pattern'");
        }
    }
}

