<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    ACS
 * @subpackage ACS/admin
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            ACS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            ACS_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'acs_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'acs_ajax_nonce' ),
                'rest_nonce' => wp_create_nonce( 'wp_rest' ),
                'strings' => array(
                    'generating' => __( 'Generating content...', 'ai-content-studio' ),
                    'success' => __( 'Content generated successfully!', 'ai-content-studio' ),
                    'error' => __( 'Error generating content. Please try again.', 'ai-content-studio' ),
                    'confirm_delete' => __( 'Are you sure you want to delete this project?', 'ai-content-studio' ),
                ),
                'settings_url' => admin_url( 'admin.php?page=acs-settings' ),
            )
        );
    }

    /**
     * Add admin menu pages.
     *
     * @since    1.0.0
     */

    /**
     * Render the generation report meta box content.
     *
     * @param WP_Post $post
     */
    public function render_generation_report_meta_box( $post ) {
        $report = get_post_meta( $post->ID, '_acs_generation_report', true );
        if ( empty( $report ) ) {
            echo '<p>' . esc_html__( 'No AI generation report for this post.', 'ai-content-studio' ) . '</p>';
            return;
        }

        echo '<div class="acs-generation-report">';
        echo '<p><strong>' . esc_html__( 'Provider:', 'ai-content-studio' ) . '</strong> ' . esc_html( $report['provider'] ?? '' ) . '</p>';
        if ( ! empty( $report['initial_errors'] ) && is_array( $report['initial_errors'] ) ) {
            echo '<p><strong>' . esc_html__( 'Initial Issues:', 'ai-content-studio' ) . '</strong></p><ul>';
            foreach ( $report['initial_errors'] as $err ) {
                echo '<li>' . esc_html( $err ) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p><strong>' . esc_html__( 'Initial Issues:', 'ai-content-studio' ) . '</strong> ' . esc_html__( 'None', 'ai-content-studio' ) . '</p>';
        }

        echo '<p><strong>' . esc_html__( 'Auto-fix applied:', 'ai-content-studio' ) . '</strong> ' . ( ! empty( $report['auto_fix_applied'] ) ? esc_html__( 'Yes', 'ai-content-studio' ) : esc_html__( 'No', 'ai-content-studio' ) ) . '</p>';
        echo '<p><strong>' . esc_html__( 'Retry performed:', 'ai-content-studio' ) . '</strong> ' . ( ! empty( $report['retry'] ) ? esc_html__( 'Yes', 'ai-content-studio' ) : esc_html__( 'No', 'ai-content-studio' ) ) . '</p>';

        if ( ! empty( $report['retry_errors'] ) && is_array( $report['retry_errors'] ) ) {
            echo '<p><strong>' . esc_html__( 'Retry Issues:', 'ai-content-studio' ) . '</strong></p><ul>';
            foreach ( $report['retry_errors'] as $err ) {
                echo '<li>' . esc_html( $err ) . '</li>';
            }
            echo '</ul>';
        }

        // Actions (Revalidate / Retry)
        $post_id_attr = intval( $post->ID );
        echo '<div class="acs-generation-report-actions" style="margin-top:10px;">';
        echo '<button type="button" class="button acs-revalidate-btn" data-post-id="' . $post_id_attr . '">' . esc_html__( 'Revalidate', 'ai-content-studio' ) . '</button> ';
        echo '<button type="button" class="button acs-retry-btn" data-post-id="' . $post_id_attr . '">' . esc_html__( 'Retry', 'ai-content-studio' ) . '</button>';
        echo '</div>';

        // Placeholder for AJAX results
    }

    /**
     * AJAX: Re-validate generated output for an existing post.
     */
    public function ajax_revalidate_generation() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( $post_id <= 0 ) {
            wp_send_json_error( __( 'Invalid post ID.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) && ! current_user_can( 'acs_generate_content' ) ) {
            wp_send_json_error( __( 'You do not have permission to revalidate this post.', 'ai-content-studio' ) );
        }

        if ( file_exists( ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
            $generator = new ACS_Content_Generator();
            $validation = $generator->validate_post_by_id( $post_id );
            if ( $validation === true ) {
                wp_send_json_success( array( 'valid' => true, 'message' => __( 'Post content meets validation rules.', 'ai-content-studio' ) ) );
            } else {
                wp_send_json_success( array( 'valid' => false, 'errors' => $validation ) );
            }
        }

        wp_send_json_error( __( 'Content generator unavailable.', 'ai-content-studio' ) );
    }

    /**
     * AJAX: Retry generation and update an existing post.
     */
    public function ajax_retry_generation() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( $post_id <= 0 ) {
            wp_send_json_error( __( 'Invalid post ID.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) && ! current_user_can( 'acs_generate_content' ) ) {
            wp_send_json_error( __( 'You do not have permission to retry generation for this post.', 'ai-content-studio' ) );
        }

        if ( file_exists( ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
            $generator = new ACS_Content_Generator();
            $result = $generator->retry_generation_for_post( $post_id );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }

            // Return updated report if available
            $report = get_post_meta( $post_id, '_acs_generation_report', true );
            wp_send_json_success( array( 'post_id' => $post_id, 'report' => $report, 'edit_link' => get_edit_post_link( $post_id ) ) );
        }

        wp_send_json_error( __( 'Content generator unavailable.', 'ai-content-studio' ) );
    }

    /**
     * Handle activation redirect.
     *
     * @since    1.0.0
     */
    public function handle_activation_redirect() {
        if ( get_option( 'acs_activation_redirect' ) ) {
            delete_option( 'acs_activation_redirect' );
            
            if ( ! isset( $_GET['activate-multi'] ) ) {
                wp_redirect( admin_url( 'admin.php?page=acs-wizard' ) );
                exit;
            }
        }
    }

    /**
     * Display dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard() {
        include_once ACS_PLUGIN_PATH . 'admin/partials/dashboard.php';
    }

    /**
     * Initialize admin settings, meta boxes, and other admin hooks.
     */
    public function admin_init() {
        // Register settings option if settings module not present
        if ( ! class_exists( 'ACS_Settings' ) ) {
            register_setting( 'acs_settings_group', 'acs_settings' );
        }

        // Add generation report meta box to posts
        add_action( 'add_meta_boxes', array( $this, 'register_generation_meta_box' ) );

        // Ensure AJAX handlers are available
        add_action( 'wp_ajax_acs_revalidate_generation', array( $this, 'ajax_revalidate_generation' ) );
        add_action( 'wp_ajax_acs_retry_generation', array( $this, 'ajax_retry_generation' ) );
    }

    /**
     * Register the generation report meta box.
     */
    public function register_generation_meta_box() {
        add_meta_box(
            'acs_generation_report',
            __( 'AI Generation Report', 'ai-content-studio' ),
            array( $this, 'render_generation_report_meta_box' ),
            'post',
            'side',
            'high'
        );
    }

    /**
     * Register admin menu and subpages.
     */
    public function add_admin_menu() {
        $cap = 'acs_generate_content';
        add_menu_page( __( 'AI Content Studio', 'ai-content-studio' ), __( 'AI Content Studio', 'ai-content-studio' ), $cap, 'acs-dashboard', array( $this, 'display_dashboard' ), 'dashicons-edit', 26 );
        add_submenu_page( 'acs-dashboard', __( 'Generate Content', 'ai-content-studio' ), __( 'Generate', 'ai-content-studio' ), $cap, 'acs-generate', array( $this, 'display_generate_page' ) );
        add_submenu_page( 'acs-dashboard', __( 'Generation Logs', 'ai-content-studio' ), __( 'Generation Logs', 'ai-content-studio' ), 'acs_view_analytics', 'acs-generation-logs', array( $this, 'display_generation_logs' ) );
        add_submenu_page( 'acs-dashboard', __( 'Analytics', 'ai-content-studio' ), __( 'Analytics', 'ai-content-studio' ), 'acs_view_analytics', 'acs-analytics', array( $this, 'display_analytics_page' ) );
        add_submenu_page( 'acs-dashboard', __( 'Settings', 'ai-content-studio' ), __( 'Settings', 'ai-content-studio' ), 'acs_manage_settings', 'acs-settings', array( $this, 'display_settings_page' ) );
    }

    /**
                                Generate Blog Post
                            </button>
                        </p>
                    </form>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3>How it works</h3>
                <ol>
                    <li><strong>Enter your topic:</strong> Describe what you want to write about</li>
                    <li><strong>Add keywords:</strong> Include relevant SEO keywords (optional)</li>
                    <li><strong>Choose length:</strong> Select your preferred word count</li>
                    <li><strong>Generate:</strong> AI will create a complete blog post with title, content, and SEO optimization</li>
                    <li><strong>Review & Publish:</strong> Edit the generated post as needed and publish</li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Handle export requests from Generation Logs page.
     */
    private function maybe_handle_logs_export() {
        if ( empty( $_GET['acs_export'] ) ) {
            return;
        }

        if ( ! current_user_can( 'acs_view_analytics' ) ) {
            wp_die( __( 'Insufficient permissions.', 'ai-content-studio' ) );
        }

        $format = sanitize_text_field( $_GET['acs_export'] );
        $provider = isset( $_GET['acs_provider'] ) ? sanitize_text_field( $_GET['acs_provider'] ) : '';
        $since = isset( $_GET['acs_since'] ) ? sanitize_text_field( $_GET['acs_since'] ) : '';
        $until = isset( $_GET['acs_until'] ) ? sanitize_text_field( $_GET['acs_until'] ) : '';
        $level = isset( $_GET['acs_level'] ) ? sanitize_text_field( $_GET['acs_level'] ) : '';

        if ( file_exists( ACS_PLUGIN_PATH . 'includes/class-acs-logger.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'includes/class-acs-logger.php';
            $entries = ACS_Logger::export_logs( array( 'provider' => $provider, 'since' => $since, 'until' => $until, 'level' => $level ) );
        } else {
            wp_die( __( 'Logger not available.', 'ai-content-studio' ) );
        }

        if ( $format === 'csv' ) {
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=acs-generation-logs-' . date( 'Ymd-His' ) . '.csv' );
            $out = fopen( 'php://output', 'w' );
            fputcsv( $out, array( 'time', 'post_id', 'provider', 'status', 'title', 'level', 'user_id' ) );
            foreach ( $entries as $e ) {
                $provider = isset( $e['report']['provider'] ) ? $e['report']['provider'] : '';
                $status = ( ! empty( $e['report'] ) && empty( $e['report']['initial_errors'] ) ) ? 'OK' : 'Issues';
                $title = isset( $e['context']['title'] ) ? $e['context']['title'] : ( isset( $e['post_id'] ) ? get_the_title( $e['post_id'] ) : '' );
                fputcsv( $out, array( $e['time'], $e['post_id'], $provider, $status, $title, $e['level'] ?? '', $e['user_id'] ?? '' ) );
            }
            fclose( $out );
            exit;
        }

        // default to JSON
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=acs-generation-logs-' . date( 'Ymd-His' ) . '.json' );
        echo wp_json_encode( $entries );
        exit;
    }

    /**
     * Display settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Delegate settings rendering to modular settings class
        if ( class_exists( 'ACS_Settings' ) ) {
            $settings_manager = new ACS_Settings( $this->plugin_name, $this->version );
            $settings_manager->display_settings_page();
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Settings module unavailable.', 'ai-content-studio' ) . '</p></div>';
        }
    }

    /**
     * Display analytics page.
     *
     * @since    1.0.0
     */
    public function display_analytics_page() {
        include_once ACS_PLUGIN_PATH . 'admin/partials/analytics.php';
    }

    /**
     * Display Generation Logs admin page.
     */
    public function display_generation_logs() {
        // If an export parameter is present, handle export first and exit.
        $this->maybe_handle_logs_export();
        // Support simple filter controls
        $filter_provider = isset( $_GET['acs_provider'] ) ? sanitize_text_field( $_GET['acs_provider'] ) : '';
        $filter_since = isset( $_GET['acs_since'] ) ? sanitize_text_field( $_GET['acs_since'] ) : '';
        $filter_until = isset( $_GET['acs_until'] ) ? sanitize_text_field( $_GET['acs_until'] ) : '';
        $filter_level = isset( $_GET['acs_level'] ) ? sanitize_text_field( $_GET['acs_level'] ) : '';

        $history = get_option( 'acs_generation_history', array() );
        if ( ! is_array( $history ) ) {
            $history = array();
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p><?php esc_html_e( 'Recent AI generation attempts (most recent first).', 'ai-content-studio' ); ?></p>

            <form method="get" style="margin-bottom:12px;" class="acs-log-filters">
                <input type="hidden" name="page" value="acs-generation-logs" />
                <label><?php esc_html_e( 'Provider:', 'ai-content-studio' ); ?>
                    <input type="text" name="acs_provider" value="<?php echo esc_attr( $filter_provider ); ?>" placeholder="openai, groq">
                </label>
                &nbsp;
                <label><?php esc_html_e( 'Since (YYYY-mm-dd):', 'ai-content-studio' ); ?>
                    <input type="date" name="acs_since" value="<?php echo esc_attr( $filter_since ); ?>">
                </label>
                &nbsp;
                <label><?php esc_html_e( 'Until:', 'ai-content-studio' ); ?>
                    <input type="date" name="acs_until" value="<?php echo esc_attr( $filter_until ); ?>">
                </label>
                &nbsp;
                <label><?php esc_html_e( 'Level:', 'ai-content-studio' ); ?>
                    <select name="acs_level">
                        <option value=""<?php selected( $filter_level, '' ); ?>><?php esc_html_e( 'All', 'ai-content-studio' ); ?></option>
                        <option value="info"<?php selected( $filter_level, 'info' ); ?>><?php esc_html_e( 'Info', 'ai-content-studio' ); ?></option>
                        <option value="warn"<?php selected( $filter_level, 'warn' ); ?>><?php esc_html_e( 'Warn', 'ai-content-studio' ); ?></option>
                        <option value="error"<?php selected( $filter_level, 'error' ); ?>><?php esc_html_e( 'Error', 'ai-content-studio' ); ?></option>
                        <option value="debug"<?php selected( $filter_level, 'debug' ); ?>><?php esc_html_e( 'Debug', 'ai-content-studio' ); ?></option>
                    </select>
                </label>
                &nbsp;
                <button class="button" type="submit"><?php esc_html_e( 'Filter', 'ai-content-studio' ); ?></button>
                &nbsp;
                <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'acs-generation-logs', 'acs_export' => 'json', 'acs_provider' => $filter_provider, 'acs_since' => $filter_since, 'acs_until' => $filter_until, 'acs_level' => $filter_level ) , admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Export JSON', 'ai-content-studio' ); ?></a>
                <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'acs-generation-logs', 'acs_export' => 'csv', 'acs_provider' => $filter_provider, 'acs_since' => $filter_since, 'acs_until' => $filter_until, 'acs_level' => $filter_level ) , admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Export CSV', 'ai-content-studio' ); ?></a>
            </form>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Time', 'ai-content-studio' ); ?></th>
                        <th><?php esc_html_e( 'Post', 'ai-content-studio' ); ?></th>
                        <th><?php esc_html_e( 'Provider', 'ai-content-studio' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'ai-content-studio' ); ?></th>
                        <th><?php esc_html_e( 'Title / Note', 'ai-content-studio' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'ai-content-studio' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $history ) ) : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No generation logs available.', 'ai-content-studio' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( array_reverse( $history ) as $entry ) :
                        $ts = isset( $entry['time'] ) ? esc_html( $entry['time'] ) : '-';
                        $post_id = isset( $entry['post_id'] ) ? intval( $entry['post_id'] ) : 0;
                        $provider = isset( $entry['report']['provider'] ) ? esc_html( $entry['report']['provider'] ) : ( isset( $entry['provider'] ) ? esc_html( $entry['provider'] ) : '' );
                        $status = ( ! empty( $entry['report'] ) && empty( $entry['report']['initial_errors'] ) ) ? esc_html__( 'OK', 'ai-content-studio' ) : esc_html__( 'Issues', 'ai-content-studio' );
                        $title = isset( $entry['context']['title'] ) ? esc_html( $entry['context']['title'] ) : ( $post_id ? get_the_title( $post_id ) : '' );
                    ?>
                        <tr>
                            <td><?php echo $ts; ?></td>
                            <td><?php if ( $post_id ) { echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( $post_id ) . '</a>'; } else { echo '-'; } ?></td>
                            <td><?php echo $provider; ?></td>
                            <td><?php echo $status; ?></td>
                            <td><?php echo $title; ?></td>
                            <td>
                                <?php if ( $post_id ) : ?>
                                    <a class="button" href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'ai-content-studio' ); ?></a>
                                <?php endif; ?>
                                <button class="button acs-view-log" data-log='<?php echo esc_attr( wp_json_encode( $entry ) ); ?>'><?php esc_html_e( 'View', 'ai-content-studio' ); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <div id="acs-log-modal" style="display:none;margin-top:20px;">
                <h2><?php esc_html_e( 'Log Detail', 'ai-content-studio' ); ?></h2>
                <pre id="acs-log-json" style="white-space:pre-wrap;background:#f7f7f7;padding:10px;border:1px solid #ddd;max-height:400px;overflow:auto;"></pre>
            </div>
        </div>

        <script>
        (function(){
            document.addEventListener('click', function(e){
                if ( e.target && e.target.classList.contains('acs-view-log') ) {
                    var data = e.target.getAttribute('data-log');
                    try {
                        var obj = JSON.parse(data);
                        document.getElementById('acs-log-json').textContent = JSON.stringify(obj, null, 2);
                        document.getElementById('acs-log-modal').style.display = 'block';
                        window.scrollTo(0, document.getElementById('acs-log-modal').offsetTop - 20);
                    } catch(err) {
                        alert('Failed to parse log entry');
                    }
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Simple Generate Content admin page (synchronous fallback).
     */
    public function display_generate_page() {
        $settings = get_option( 'acs_settings', array() );
        $api_key = $settings['providers']['groq']['api_key'] ?? '';

        if ( isset( $_POST['generate_content'] ) && wp_verify_nonce( $_POST['acs_generate_nonce'] ?? '', 'acs_generate_action' ) ) {
            if ( empty( $api_key ) ) {
                echo '<div class="notice notice-error"><p>Please configure your API key in <a href="' . admin_url('admin.php?page=acs-settings') . '">Settings</a> first.</p></div>';
            } else {
                $topic = sanitize_textarea_field( $_POST['content_topic'] ?? '' );
                $keywords = sanitize_text_field( $_POST['keywords'] ?? '' );
                $word_count = sanitize_text_field( $_POST['word_count'] ?? 'medium' );

                if ( ! empty( $topic ) ) {
                    $generated_content = $this->generate_full_content( $api_key, $topic, $keywords, $word_count );
                    if ( $generated_content ) {
                        $post_id = $this->create_wordpress_post( $generated_content, $keywords );
                        if ( $post_id ) {
                            echo '<div class="notice notice-success"><p>Content generated successfully! <a href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank">Edit the post</a> | <a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">Preview</a></p></div>';
                        } else {
                            echo '<div class="notice notice-error"><p>Content generated but failed to create post. Please try again.</p></div>';
                        }
                    } else {
                        echo '<div class="notice notice-error"><p>Failed to generate content. Please check your API key and try again.</p></div>';
                    }
                }
            }
        }

        // Render simple generate UI
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php if ( empty( $api_key ) ) : ?>
                <div class="notice notice-warning">
                    <p><strong>API Key Required:</strong> Please configure your Groq API key in <a href="<?php echo admin_url('admin.php?page=acs-settings'); ?>">Settings</a> first.</p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Generate Blog Post</h2>
                <?php
                $default_topic = 'The Benefits of AI Content Generation for Small Businesses';
                $default_keywords = 'AI, content generation, small business, SEO, automation';
                ?>
                <form method="post" action="">
                    <?php wp_nonce_field( 'acs_generate_action', 'acs_generate_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="content_topic">Topic/Title</label>
                            </th>
                            <td>
                                <textarea id="content_topic" name="content_topic" rows="3" class="large-text" placeholder="Enter the main topic or title for your blog post..." required><?php echo esc_textarea( $default_topic ); ?></textarea>
                                <p class="description">Describe what you want to write about. Be specific for better results.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="keywords">Keywords (Optional)</label>
                            </th>
                            <td>
                                <input type="text" id="keywords" name="keywords" class="regular-text" placeholder="SEO keywords, separated by commas" value="<?php echo esc_attr( $default_keywords ); ?>">
                                <p class="description">Add relevant keywords to optimize for SEO.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="word_count">Target Word Count</label>
                            </th>
                            <td>
                                <select id="word_count" name="word_count">
                                    <option value="short">Short (~500 words)</option>
                                    <option value="medium" selected>Medium (~1000 words)</option>
                                    <option value="long">Long (~1500 words)</option>
                                    <option value="detailed">Detailed (~2000+ words)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="generate_content" class="button button-primary" <?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
                            Generate Blog Post
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for generating content.
     *
     * @since    1.0.0
     */
    public function ajax_generate_content() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'acs_generate_content' ) ) {
            wp_die( __( 'You do not have permission to generate content.', 'ai-content-studio' ) );
        }

        // Sanitize input
        $prompt_data = ACS_Sanitizer::sanitize_prompt_input( $_POST );

        // Initialize content generator (modular)
        if ( file_exists( ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
            $generator = new ACS_Content_Generator();
            $result = $generator->generate( $prompt_data );
        } else {
            wp_send_json_error( __( 'Content generator not available.', 'ai-content-studio' ) );
            return;
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        } else {
            wp_send_json_success( $result );
        }
    }

    /**
     * AJAX handler for saving settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_settings() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'acs_manage_settings' ) ) {
            wp_die( __( 'You do not have permission to manage settings.', 'ai-content-studio' ) );
        }

        // Sanitize and save settings
        $settings = ACS_Sanitizer::sanitize_settings( $_POST['settings'] );
        update_option( 'acs_settings', $settings );

        wp_send_json_success( __( 'Settings saved successfully.', 'ai-content-studio' ) );
    }

    /**
     * AJAX handler for testing API connection.
     *
     * @since    1.0.0
     */
    public function ajax_test_api_connection() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'acs_manage_settings' ) ) {
            wp_die( __( 'You do not have permission to test API connections.', 'ai-content-studio' ) );
        }

        $provider = sanitize_text_field( $_POST['provider'] );
        $api_key = sanitize_text_field( $_POST['api_key'] );

        // Test connection based on provider
        switch ( $provider ) {
            case 'groq':
                $provider_instance = new ACS_Groq( $api_key );
                break;
            case 'openai':
                $provider_instance = new ACS_OpenAI( $api_key );
                break;
            case 'anthropic':
                $provider_instance = new ACS_Anthropic( $api_key );
                break;
            default:
                wp_send_json_error( __( 'Invalid provider.', 'ai-content-studio' ) );
                return;
        }

        $is_valid = $provider_instance->authenticate( $api_key );

        if ( $is_valid ) {
            wp_send_json_success( __( 'API connection successful!', 'ai-content-studio' ) );
        } else {
            wp_send_json_error( __( 'API connection failed. Please check your API key.', 'ai-content-studio' ) );
        }
    }

    /**
     * AJAX handler to create a WordPress post from generated content.
     *
     * @since 1.0.0
     */
    public function ajax_create_post() {
        // Verify nonce
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'acs_generate_content' ) ) {
            wp_send_json_error( __( 'You do not have permission to create posts.', 'ai-content-studio' ) );
        }

        // Collect and sanitize input
        $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
        $meta_description = isset( $_POST['meta_description'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_description'] ) ) : '';
        $focus_keyword = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ) ) : '';
        $tags = isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '';

        $generated = array(
            'title' => $title,
            'content' => $content,
            'meta_description' => $meta_description,
            'focus_keyword' => $focus_keyword,
        );

        // Use content generator's create_post helper when available
        if ( file_exists( ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
            $generator = new ACS_Content_Generator();
            $post_id = $generator->create_post( $generated, $tags );
        } else {
            // Fallback to local creation method if generator not present
            $post_id = $this->create_wordpress_post( $generated, $tags );
        }

        if ( $post_id ) {
            wp_send_json_success( array(
                'post_id' => $post_id,
                'edit_link' => get_edit_post_link( $post_id ),
                'permalink' => get_permalink( $post_id ),
            ) );
        }

        wp_send_json_error( __( 'Failed to create post.', 'ai-content-studio' ) );
    }

    /**
     * AJAX handler for getting keyword suggestions.
     *
     * @since    1.0.0
     */
    public function ajax_get_keyword_suggestions() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        $topic = sanitize_text_field( $_POST['topic'] );

        // Get keyword suggestions
        require_once ACS_PLUGIN_PATH . 'api/class-acs-keyword-research.php';
        $keyword_research = new ACS_Keyword_Research();
        $suggestions = $keyword_research->get_suggestions( $topic );

        if ( is_wp_error( $suggestions ) ) {
            wp_send_json_error( $suggestions->get_error_message() );
        } else {
            wp_send_json_success( $suggestions );
        }
    }

    /**
     * Sanitize settings input.
     *
     * @since    1.0.0
     * @param    array    $input    The input settings.
     * @return   array              The sanitized settings.
     */
    public function sanitize_settings( $input ) {
        return ACS_Sanitizer::sanitize_settings( $input );
    }

    /**
     * Add settings sections.
     *
     * @since    1.0.0
     */
    private function add_settings_sections() {
        // General settings section
        add_settings_section(
            'acs_general_section',
            __( 'General Settings', 'ai-content-studio' ),
            array( $this, 'general_section_callback' ),
            'acs-settings'
        );

        // Provider settings section
        add_settings_section(
            'acs_providers_section',
            __( 'AI Providers', 'ai-content-studio' ),
            array( $this, 'providers_section_callback' ),
            'acs-settings'
        );

        // SEO settings section
        add_settings_section(
            'acs_seo_section',
            __( 'SEO Settings', 'ai-content-studio' ),
            array( $this, 'seo_section_callback' ),
            'acs-settings'
        );

        // Content settings section
        add_settings_section(
            'acs_content_section',
            __( 'Content Settings', 'ai-content-studio' ),
            array( $this, 'content_section_callback' ),
            'acs-settings'
        );
    }

    /**
     * Test Groq API connection.
     *
     * @param string $api_key The API key to test
     * @return bool True if connection successful
     */
    private function test_groq_api( $api_key ) {
        if ( empty( $api_key ) ) {
            return false;
        }
        
        $url = 'https://api.groq.com/openai/v1/models';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        $response = wp_remote_get( $url, array(
            'headers' => $headers,
            'timeout' => 10
        ) );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        return $response_code === 200;
    }

    /**
     * Test content generation with Groq API.
     *
     * @param string $api_key The API key to use
     * @return string|false Generated content or false on failure
     */
    private function test_content_generation( $api_key ) {
        if ( empty( $api_key ) ) {
            return false;
        }
        
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        $body = array(
            'model' => 'llama-3.3-70b-versatile',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Write a brief introduction paragraph about the benefits of AI in content creation. Keep it under 100 words.'
                )
            ),
            'max_tokens' => 200,
            'temperature' => 0.7
        );
        
        $response = wp_remote_post( $url, array(
            'headers' => $headers,
            'body' => wp_json_encode( $body ),
            'timeout' => 30
        ) );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return $data['choices'][0]['message']['content'];
        }
        
        return false;
    }

    /**
     * Generate full blog post content using Groq API.
     *
     * @param string $api_key The API key
     * @param string $topic The topic/title
     * @param string $keywords The keywords
     * @param string $word_count The target word count
     * @return array|false Generated content array or false
     */
    private function generate_full_content( $api_key, $topic, $keywords = '', $word_count = 'medium' ) {
        $word_targets = array(
            'short' => '500',
            'medium' => '1000', 
            'long' => '1500',
            'detailed' => '2000+'
        );
        
        $target_words = $word_targets[$word_count] ?? '1000';
        
        // Build the prompt for better SEO content
        $primary_keyword = '';
        if ( ! empty( $keywords ) ) {
            $keyword_array = array_map( 'trim', explode( ',', $keywords ) );
            $primary_keyword = $keyword_array[0]; // Use first keyword as primary
        }
        
        $prompt = "Write a complete, SEO-optimized blog post about: {$topic}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Target length: approximately {$target_words} words\n";
        $prompt .= "- Include a compelling, SEO-optimized title\n";
        $prompt .= "- Write an engaging introduction that includes the main keyword\n";
        $prompt .= "- Use HTML headings (h2, h3) instead of markdown\n";
        $prompt .= "- Include the main keyword naturally throughout (1-2% density)\n";
        $prompt .= "- Add internal linking opportunities (mention related topics)\n";
        $prompt .= "- Include a strong conclusion with a call to action\n";
        $prompt .= "- Write in a professional, engaging tone\n";
        $prompt .= "- Structure content for easy scanning (short paragraphs, bullet points)\n";
        
        if ( ! empty( $keywords ) ) {
            $prompt .= "- Primary keyword (use prominently): {$primary_keyword}\n";
            $prompt .= "- Secondary keywords to include naturally: {$keywords}\n";
        }
        
        $prompt .= "\nContent Structure Requirements:\n";
        $prompt .= "- Use <h2> and <h3> tags for headings (NOT ## or ###)\n";
        $prompt .= "- Include the primary keyword in the first paragraph\n";
        $prompt .= "- Add relevant subheadings that could contain keywords\n";
        $prompt .= "- Include mentions of outbound link opportunities\n";
        $prompt .= "- Write in HTML format ready for WordPress\n";
        
        $prompt .= "\nPlease structure the response as:\n";
        $prompt .= "TITLE: [SEO-optimized blog post title with primary keyword]\n";
        $prompt .= "CONTENT: [Full blog post content in HTML format with proper h2/h3 headings]\n";
        $prompt .= "META_DESCRIPTION: [SEO meta description with primary keyword, max 155 characters]\n";
        $prompt .= "FOCUS_KEYWORD: [Primary focus keyword for SEO]\n";

        // Log prompt for debugging (wp-content directory for universal access)
        $debug_log_path = WP_CONTENT_DIR . '/acs_prompt_debug.log';
        $debug_entry = date('Y-m-d H:i:s') . "\nPROMPT:\n" . $prompt . "\n---\n";
        file_put_contents($debug_log_path, $debug_entry, FILE_APPEND | LOCK_EX);
        
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        $body = array(
            'model' => 'llama-3.3-70b-versatile',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 4000,
            'temperature' => 0.7
        );
        
        $response = wp_remote_post( $url, array(
            'headers' => $headers,
            'body' => wp_json_encode( $body ),
            'timeout' => 60
        ) );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            $content = $data['choices'][0]['message']['content'];
            return $this->parse_generated_content( $content );
        }
        
        return false;
    }

    /**
     * Parse the generated content into title, content, meta description, and focus keyword.
     *
     * @param string $content The raw generated content
     * @return array Parsed content array
     */
    private function parse_generated_content( $content ) {
        $result = array(
            'title' => '',
            'content' => '',
            'meta_description' => '',
            'focus_keyword' => ''
        );
        
        // Extract title
        if ( preg_match('/TITLE:\s*(.+?)(?=\n|$)/i', $content, $matches) ) {
            $result['title'] = trim( $matches[1] );
        }
        
        // Extract meta description
        if ( preg_match('/META_DESCRIPTION:\s*(.+?)(?=\n|$)/i', $content, $matches) ) {
            $result['meta_description'] = trim( $matches[1] );
        }
        
        // Extract focus keyword
        if ( preg_match('/FOCUS_KEYWORD:\s*(.+?)(?=\n|$)/i', $content, $matches) ) {
            $result['focus_keyword'] = trim( $matches[1] );
        }
        
        // Extract content
        if ( preg_match('/CONTENT:\s*(.+?)(?=\n\s*(?:META_DESCRIPTION|FOCUS_KEYWORD):|$)/is', $content, $matches) ) {
            $result['content'] = trim( $matches[1] );
        }
        
        // Convert markdown headings to HTML if present
        $result['content'] = $this->convert_markdown_to_html( $result['content'] );
        
        // Fallbacks if parsing fails
        if ( empty( $result['title'] ) ) {
            $lines = explode( "\n", $content );
            $result['title'] = trim( $lines[0] );
        }
        
        if ( empty( $result['content'] ) ) {
            $result['content'] = $this->convert_markdown_to_html( $content );
        }
        
        return $result;
    }

    /**
     * Create a WordPress post from generated content.
     *
     * @param array $content The generated content array
     * @param string $keywords The original keywords input
     * @return int|false Post ID or false on failure
     */
    private function create_wordpress_post( $content, $keywords = '' ) {
        $title = $content['title'] ?: 'AI Generated Post - ' . current_time( 'Y-m-d H:i:s' );
        $post_content = $content['content'] ?: 'No content generated.';
        
        // Create SEO-friendly slug
        $slug = sanitize_title( $title );
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $post_content,
            'post_name' => $slug,
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
            'post_type' => 'post',
            'meta_input' => array(
                '_acs_generated' => true,
                '_acs_generated_date' => current_time( 'mysql' ),
                '_acs_original_keywords' => $keywords,
            )
        );
        
        $post_id = wp_insert_post( $post_data );
        
        if ( $post_id && ! is_wp_error( $post_id ) ) {
            // Set Yoast SEO meta fields
            if ( ! empty( $content['meta_description'] ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', $content['meta_description'] );
            }
            
            // Set focus keyword for Yoast
            $focus_keyword = $content['focus_keyword'];
            if ( empty( $focus_keyword ) && ! empty( $keywords ) ) {
                $keyword_array = array_map( 'trim', explode( ',', $keywords ) );
                $focus_keyword = $keyword_array[0]; // Use first keyword as focus
            }
            
            if ( ! empty( $focus_keyword ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyword );
            }
            
            // Set custom SEO title if needed
            if ( ! empty( $focus_keyword ) ) {
                $seo_title = $title;
                if ( stripos( $title, $focus_keyword ) === false ) {
                    $seo_title = $focus_keyword . ' - ' . $title;
                }
                update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
            }
            
            return $post_id;
        }
        
        return false;
    }

    /**
     * Display recent generated posts.
     *
     * @since 1.0.0
     */
    private function display_recent_generated_posts() {
        $posts = get_posts( array(
            'post_type' => 'post',
            'posts_per_page' => 5,
            'meta_key' => '_acs_generated',
            'meta_value' => true,
            'post_status' => array( 'draft', 'publish', 'private' )
        ) );
        
        if ( empty( $posts ) ) {
            echo '<p>No generated posts yet. <a href="' . admin_url('admin.php?page=acs-generate') . '">Create your first post!</a></p>';
            return;
        }
        
        echo '<ul>';
        foreach ( $posts as $post ) {
            $status = get_post_status( $post->ID );
            $status_label = array(
                'draft' => 'Draft',
                'publish' => 'Published',
                'private' => 'Private'
            )[$status] ?? ucfirst( $status );
            
            echo '<li>';
            echo '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a>';
            echo ' <span style="color: #666;">(' . $status_label . ')</span>';
            echo '<br><small>' . human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) . ' ago</small>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Get count of generated posts.
     *
     * @return int Number of generated posts
     */
    private function get_generated_posts_count() {
        $posts = get_posts( array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_key' => '_acs_generated',
            'meta_value' => true,
            'post_status' => array( 'draft', 'publish', 'private' ),
            'fields' => 'ids'
        ) );
        
        return count( $posts );
    }

    /**
     * Convert markdown headings to HTML headings.
     *
     * @param string $content Content that may contain markdown
     * @return string Content with HTML headings
     */
    private function convert_markdown_to_html( $content ) {
        // Convert markdown headings to HTML
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
        
        // Convert **bold** to <strong>
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        
        // Convert *italic* to <em>
        $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
        
        // Convert line breaks to paragraphs for better HTML structure
        $content = $this->convert_to_paragraphs( $content );
        
        return $content;
    }

    /**
     * Convert plain text with line breaks to proper HTML paragraphs.
     *
     * @param string $content The content to convert
     * @return string Content with proper paragraph tags
     */
    private function convert_to_paragraphs( $content ) {
        // Remove existing <p> tags to avoid duplication
        $content = preg_replace('/<\/?p[^>]*>/', '', $content);

        // Normalize line endings to \n
        $content = str_replace(array("\r\n", "\r"), "\n", $content);

        // Split into paragraphs on blank lines
        $paragraphs = preg_split('/\n\s*\n/', trim( $content ) );

        $html_content = '';
        foreach ( $paragraphs as $paragraph ) {
            $paragraph = trim( $paragraph );
            if ( ! empty( $paragraph ) ) {
                // If paragraph already starts with an HTML block-level tag, keep as-is
                if ( preg_match('/^<\/(h[1-6]|div|ul|ol|blockquote)|^<(h[1-6]|div|ul|ol|blockquote)(\s|>)/i', $paragraph ) ) {
                    $html_content .= $paragraph . "\n\n";
                } else {
                    $html_content .= '<p>' . $paragraph . '</p>' . "\n\n";
                }
            }
        }

        return trim( $html_content );
    }
}