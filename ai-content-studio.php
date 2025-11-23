<?php
/**
 * Plugin Name: AI Content Studio for WordPress
 * Plugin URI: https://lancedesk.com/ai-content-studio
 * Description: Generate SEO-optimized blog posts using AI with support for multiple LLM providers. Fully compatible with Yoast, Rank Math, and other SEO plugins.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Lance Desk
 * Author URI: https://lancedesk.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-content-studio
 * Domain Path: /languages
 * Network: false
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'ACS_VERSION', '1.0.0' );
define( 'ACS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-acs-activator.php
 */
function acs_activate_plugin() {
    require_once ACS_PLUGIN_PATH . 'includes/class-acs-activator.php';
    ACS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-acs-deactivator.php
 */
function acs_deactivate_plugin() {
    require_once ACS_PLUGIN_PATH . 'includes/class-acs-deactivator.php';
    ACS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'acs_activate_plugin' );
register_deactivation_hook( __FILE__, 'acs_deactivate_plugin' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ACS_PLUGIN_PATH . 'includes/class-acs-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function acs_run_plugin() {
    $plugin = new ACS_Core();
    $plugin->run();
}

acs_run_plugin();