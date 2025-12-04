<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class TestGeneratorProviderSelection extends TestCase {

    public function test_generator_uses_fallback_when_primary_fails() {
        global $acs_test_provider_behavior, $acs_test_options;

        // Set provider behavior: groq fails, openai succeeds
        $acs_test_provider_behavior['groq'] = 'error';
        $acs_test_provider_behavior['openai'] = 'ok';

        // Configure options so default provider is groq, backup is openai
        $acs_test_options['acs_settings'] = array(
            'default_provider' => 'groq',
            'backup_providers' => array( 'openai' ),
            'providers' => array(
                'groq' => array( 'api_key' => 'G' ),
                'openai' => array( 'api_key' => 'O' ),
            ),
        );

        $gen = new ACS_Content_Generator();
        $res = $gen->generate( array( 'topic' => 'Test topic' ) );

        // Should return successful array from OpenAI provider
        $this->assertIsArray( $res );
        $this->assertStringContainsString( 'OpenAI', $res['title'] );
        $this->assertEquals( 'openai', $res['provider'] );
    }

}
