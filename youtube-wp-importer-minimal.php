<?php
/**
 * Plugin Name: YouTube WP Importer (Minimal Test)
 * Description: Minimal version for debugging
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class YT_WP_Importer_Minimal {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'YouTube Test',
            'YouTube Test',
            'manage_options',
            'yt-wp-test',
            array($this, 'admin_page'),
            'dashicons-video-alt3'
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>YouTube Importer - Test Version</h1>
            <p>If you can see this page, the basic plugin structure works.</p>
            <p>The 500 error was likely caused by a syntax error in the full version.</p>
        </div>
        <?php
    }
}

new YT_WP_Importer_Minimal();
