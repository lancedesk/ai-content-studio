<?php
/**
 * Dashboard partial for AI Content Studio
 * Expects to be included from the `ACS_Admin` class so `$this` is available.
 */
$settings = get_option( 'acs_settings', array() );
$api_key = $settings['providers']['groq']['api_key'] ?? '';
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php if ( empty( $api_key ) ) : ?>
        <div class="notice notice-warning">
            <p><strong>Welcome to AI Content Studio!</strong></p>
            <p>To get started, please configure your Groq API key in the settings.</p>
            <p><a href="<?php echo esc_url( admin_url('admin.php?page=acs-settings') ); ?>" class="button button-primary">Configure API Key</a></p>
        </div>
    <?php else : ?>
        <div class="notice notice-success">
            <p><strong>Ready to generate content!</strong></p>
            <p>Your API is configured and ready. Start creating amazing blog posts with AI.</p>
            <p><a href="<?php echo esc_url( admin_url('admin.php?page=acs-generate') ); ?>" class="button button-primary">Generate Content</a></p>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; margin: 20px 0;">
        <div style="flex: 1;">
            <div class="card">
                <h2>Quick Actions</h2>
                <p><a href="<?php echo esc_url( admin_url('admin.php?page=acs-generate') ); ?>" class="button button-secondary"<?php echo empty( $api_key ) ? ' disabled' : ''; ?>>Generate New Post</a></p>
                <p><a href="<?php echo esc_url( admin_url('admin.php?page=acs-settings') ); ?>" class="button button-secondary">Plugin Settings</a></p>
                <p><a href="<?php echo esc_url( admin_url('edit.php?meta_key=_acs_generated&meta_value=1') ); ?>" class="button button-secondary">View Generated Posts</a></p>
            </div>
        </div>

        <div style="flex: 1;">
            <div class="card">
                <h2>Recent Generated Posts</h2>
                <?php if ( method_exists( $this, 'display_recent_generated_posts' ) ) { $this->display_recent_generated_posts(); } else { echo '<p>No helper available.</p>'; } ?>
            </div>
        </div>
    </div>

    <div class="acs-dashboard-stats">
        <h2>System Status</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Core Plugin</td>
                    <td><span style="color: green;">✓ Active</span></td>
                </tr>
                <tr>
                    <td>API Configuration</td>
                    <td><?php echo method_exists( $this, 'check_api_status' ) ? $this->check_api_status() : '<span style="color: orange;">⚠ Unknown</span>'; ?></td>
                </tr>
                <tr>
                    <td>WordPress Version</td>
                    <td><?php echo get_bloginfo('version'); ?> (Required: 5.8+)</td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo PHP_VERSION; ?> (Required: 7.4+)</td>
                </tr>
                <tr>
                    <td>Generated Posts</td>
                    <td><?php echo method_exists( $this, 'get_generated_posts_count' ) ? $this->get_generated_posts_count() . ' posts' : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
