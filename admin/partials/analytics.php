<?php
/**
 * Analytics partial for AI Content Studio
 * Minimal placeholder that can be expanded later.
 * Expects to be included from the `ACS_Admin` class so `$this` is available.
 */
$generated_count = method_exists( $this, 'get_generated_posts_count' ) ? $this->get_generated_posts_count() : 0;
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="card">
        <h2>Quick Analytics</h2>
        <p>Total generated posts: <strong><?php echo intval( $generated_count ); ?></strong></p>
        <p>Recent generated posts:</p>
        <?php if ( method_exists( $this, 'display_recent_generated_posts' ) ) { $this->display_recent_generated_posts(); } else { echo '<p>No recent posts helper available.</p>'; } ?>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Traffic & Engagement</h2>
        <p>This is a placeholder for analytics (pageviews, CTR, avg. time on page). Integrate tracking/analytics provider later.</p>
    </div>
</div>
