<?php
// Quick diagnostic script. Access via browser: /wp-content/plugins/ai-content-studio/tools/inspect_generator.php
// WARNING: remove this file after use.

// Bootstrap WP
$root = __DIR__ . '/../../../../wp-load.php';
if ( file_exists( $root ) ) {
    require_once $root;
} else {
    echo "Could not find wp-load.php at $root\n";
    exit;
}

if ( ! class_exists( 'ACS_Content_Generator' ) ) {
    require_once __DIR__ . '/../generators/class-acs-content-generator.php';
}

if ( ! class_exists( 'ACS_Content_Generator' ) ) {
    echo "ACS_Content_Generator class not found\n";
    exit;
}

$g = new ACS_Content_Generator();
try {
    $rc = new ReflectionClass( $g );
    $file = $rc->getFileName();
} catch ( Exception $e ) {
    $file = 'reflection_failed';
}

$methods = get_class_methods( $g );

header('Content-Type: text/plain');
echo "Generator class file: " . $file . "\n\n";
echo "Methods:\n";
foreach ( $methods as $m ) {
    echo " - $m\n";
}

echo "\nYou can now safely delete this file after inspection.\n";
