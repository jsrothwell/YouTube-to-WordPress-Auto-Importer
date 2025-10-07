<?php
/**
 * Plugin Name: YouTube to WordPress Auto-Importer
 * Description: Automatically imports YouTube videos as WordPress posts with customizable galleries
 * Version: 1.0.2
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class YT_WP_Importer {
    
    private $option_name = 'yt_wp_importer_settings';
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('yt_wp_import_videos', array($this, 'import_videos'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        add_shortcode('youtube_gallery', array($this, 'gallery_shortcode'));
    }
    
    public function activate() {
        if (!wp_next_scheduled('yt_wp_import_videos')) {
            wp_schedule_event(time(), 'hourly', 'yt_wp_import_videos');
        }
        
        $defaults = array(
            'channel_id' => '', 'api_key' => '', 'check_frequency' => 'hourly',
            'post_status' => 'publish', 'post_category' => '', 'post_tags' => '',
            'title_format' => '{title}', 'embed_video' => 'yes', 'include_description' => 'yes',
            'set_featured_image' => 'yes', 'update_existing_posts' => 'no',
            'update_featured_images' => 'yes', 'update_metadata' => 'yes',
            'layout_type' => 'grid', 'gallery_columns' => '3', 'videos_per_page' => '12',
            'show_title' => 'yes', 'show_duration' => 'yes', 'show_date' => 'yes',
            'show_description' => 'excerpt', 'description_length' => '150',
            'card_style' => 'default', 'hover_effect' => 'lift', 'thumbnail_aspect' => '16-9',
            'spacing' => 'medium', 'border_radius' => 'medium', 'showcase_video_id' => '',
            'carousel_autoplay' => 'yes', 'carousel_speed' => '3000'
        );
        
        if (!get_option($this->option_name)) {
            add_option($this->option_name, $defaults);
        }
    }
    
    public function deactivate() {
        $timestamp = wp_next_scheduled('yt_wp_import_videos');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'yt_wp_import_videos');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page('YouTube Importer', 'YouTube Importer', 'manage_options',
            'yt-wp-importer', array($this, 'admin_page'), 'dashicons-video-alt3', 30);
    }
    
    public function register_settings() {
        register_setting('yt_wp_importer_group', $this->option_name);
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_yt-wp-importer') return;
        wp_enqueue_script('yt-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.2', true);
        wp_localize_script('yt-admin-js', 'ytAdmin', array('optionName' => $this->option_name));
    }
    
    public function enqueue_assets() {
        wp_enqueue_style('yt-wp-importer', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.2');
        wp_enqueue_script('yt-wp-importer', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.2', true);
    }
    
    public function admin_page() {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        // Handle actions
        $import_msg = $refresh_msg = '';
        if (isset($_POST['manual_import']) && check_admin_referer('yt_manual_import', 'yt_import_nonce')) {
            $result = $this->import_videos();
            $import_msg = 'Import completed! Added ' . $result['new'] . ' new video(s).';
        }
        if (isset($_POST['refresh_all_posts']) && check_admin_referer('yt_refresh_posts', 'yt_refresh_nonce')) {
            $result = $this->refresh_all_posts();
            $refresh_msg = 'Refresh completed! Updated ' . $result['updated'] . ' post(s).';
        }
        
        include(dirname(__FILE__) . '/admin-interface.php');
    }
    
    public function import_videos() {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        if (empty($g('channel_id')) || empty($g('api_key'))) {
            return array('new' => 0, 'updated' => 0);
        }
        
        $new_count = $updated_count = 0;
        $url = "https://www.googleapis.com/youtube/v3/search?key={$g('api_key')}&channelId={$g('channel_id')}&part=snippet&order=date&maxResults=10&type=video";
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) return array('new' => 0, 'updated' => 0);
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['items'])) return array('new' => 0, 'updated' => 0);
        
        foreach ($data['items'] as $item) {
            $video_id = $item['id']['videoId'];
            
            $existing = get_posts(array(
                'post_type' => 'post',
                'meta_key' => 'yt_video_id',
                'meta_value' => $video_id,
                'posts_per_page' => 1
            ));
            
            $details_url = "https://www.googleapis.com/youtube/v3/videos?key={$g('api_key')}&id={$video_id}&part=snippet,contentDetails";
            $details_response = wp_remote_get($details_url);
            if (is_wp_error($details_response)) continue;
            
            $details_data = json_decode(wp_remote_retrieve_body($details_response), true);
            if (!isset($details_data['items'][0])) continue;
            
            $video_details = $details_data['items'][0];
            $snippet = $video_details['snippet'];
            $content_details = $video_details['contentDetails'];
            
            if (!empty($existing) && $g('update_existing_posts') === 'yes') {
                $this->update_post($existing[0]->ID, $snippet, $content_details, $video_id);
                $updated_count++;
            } elseif (empty($existing)) {
                $post_id = $this->create_post($snippet, $content_details, $video_id);
                if ($post_id) $new_count++;
            }
        }
        
        return array('new' => $new_count, 'updated' => $updated_count);
    }
    
    private function create_post($snippet, $content_details, $video_id) {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        $title = str_replace('{title}', $snippet['title'], $g('title_format', '{title}'));
        $content = '';
        
        if ($g('embed_video') === 'yes') {
            $content .= '<div class="yt-video-embed"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allowfullscreen></iframe></div>';
        }
        if ($g('include_description') === 'yes' && !empty($snippet['description'])) {
            $content .= '<div class="yt-video-description">' . wpautop($snippet['description']) . '</div>';
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $g('post_status', 'publish'),
            'post_type' => 'post',
            'post_date' => date('Y-m-d H:i:s', strtotime($snippet['publishedAt']))
        );
        
        if ($g('post_category')) {
            $post_data['post_category'] = array($g('post_category'));
        }
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            if ($g('post_tags')) wp_set_post_tags($post_id, $g('post_tags'));
            
            update_post_meta($post_id, 'yt_video_id', $video_id);
            update_post_meta($post_id, 'yt_video_url', 'https://www.youtube.com/watch?v=' . $video_id);
            update_post_meta($post_id, 'yt_video_duration', $content_details['duration']);
            update_post_meta($post_id, 'yt_video_thumbnail', $snippet['thumbnails']['high']['url']);
            update_post_meta($post_id, 'yt_video_description', $snippet['description']);
            
            if ($g('set_featured_image') === 'yes') {
                $thumb_url = isset($snippet['thumbnails']['maxres']['url']) ? $snippet['thumbnails']['maxres']['url'] : $snippet['thumbnails']['high']['url'];
                $this->set_featured_image($post_id, $thumb_url, $title);
            }
        }
        
        return $post_id;
    }
    
    private function update_post($post_id, $snippet, $content_details, $video_id) {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        if ($g('update_metadata') === 'yes') {
            $title = str_replace('{title}', $snippet['title'], $g('title_format', '{title}'));
            $content = '';
            
            if ($g('embed_video') === 'yes') {
                $content .= '<div class="yt-video-embed"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allowfullscreen></iframe></div>';
            }
            if ($g('include_description') === 'yes' && !empty($snippet['description'])) {
                $content .= '<div class="yt-video-description">' . wpautop($snippet['description']) . '</div>';
            }
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $title,
                'post_content' => $content
            ));
            
            update_post_meta($post_id, 'yt_video_duration', $content_details['duration']);
            update_post_meta($post_id, 'yt_video_description', $snippet['description']);
        }
        
        if ($g('update_featured_images') === 'yes' && $g('set_featured_image') === 'yes') {
            $old_thumb = get_post_thumbnail_id($post_id);
            if ($old_thumb) wp_delete_attachment($old_thumb, true);
            
            $thumb_url = isset($snippet['thumbnails']['maxres']['url']) ? $snippet['thumbnails']['maxres']['url'] : $snippet['thumbnails']['high']['url'];
            $title = str_replace('{title}', $snippet['title'], $g('title_format', '{title}'));
            $this->set_featured_image($post_id, $thumb_url, $title);
        }
    }
    
    private function set_featured_image($post_id, $image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) return false;
        
        $file_array = array(
            'name' => basename($image_url) . '.jpg',
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        set_post_thumbnail($post_id, $attachment_id);
        return true;
    }
    
    public function refresh_all_posts() {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        if (empty($g('api_key'))) return array('updated' => 0);
        
        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_query' => array(array('key' => 'yt_video_id', 'compare' => 'EXISTS'))
        ));
        
        $updated_count = 0;
        foreach ($posts as $post) {
            $video_id = get_post_meta($post->ID, 'yt_video_id', true);
            if (empty($video_id)) continue;
            
            $details_url = "https://www.googleapis.com/youtube/v3/videos?key={$g('api_key')}&id={$video_id}&part=snippet,contentDetails";
            $response = wp_remote_get($details_url);
            if (is_wp_error($response)) continue;
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($data['items'][0])) continue;
            
            $video_details = $data['items'][0];
            $this->update_post($post->ID, $video_details['snippet'], $video_details['contentDetails'], $video_id);
            $updated_count++;
            
            usleep(100000);
        }
        
        return array('updated' => $updated_count);
    }
    
    public function gallery_shortcode($atts) {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        $atts = shortcode_atts(array(
            'layout' => $g('layout_type', 'grid'),
            'columns' => $g('gallery_columns', '3'),
            'posts_per_page' => $g('videos_per_page', '12')
        ), $atts);
        
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        $query = new WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => $atts['posts_per_page'],
            'paged' => $paged,
            'meta_query' => array(array('key' => 'yt_video_id', 'compare' => 'EXISTS')),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Get style classes
        $classes = array(
            'yt-style-' . $g('card_style', 'default'),
            'yt-hover-' . $g('hover_effect', 'lift'),
            'yt-aspect-' . $g('thumbnail_aspect', '16-9'),
            'yt-spacing-' . $g('spacing', 'medium'),
            'yt-radius-' . $g('border_radius', 'medium')
        );
        
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="yt-gallery yt-layout-' . esc_attr($atts['layout']) . ' yt-gallery-cols-' . esc_attr($atts['columns']) . ' ' . esc_attr(implode(' ', $classes)) . '">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_video_item();
            }
            
            echo '</div>';
            
            if ($query->max_num_pages > 1) {
                echo '<div class="yt-gallery-pagination">';
                echo paginate_links(array('total' => $query->max_num_pages, 'current' => $paged));
                echo '</div>';
            }
        }
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    private function render_video_item() {
        $options = get_option($this->option_name, array());
        $g = function($k, $d = '') use ($options) { return isset($options[$k]) ? $options[$k] : $d; };
        
        $video_id = get_post_meta(get_the_ID(), 'yt_video_id', true);
        $thumbnail = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : get_post_meta(get_the_ID(), 'yt_video_thumbnail', true);
        $duration = get_post_meta(get_the_ID(), 'yt_video_duration', true);
        $description = get_post_meta(get_the_ID(), 'yt_video_description', true);
        
        echo '<div class="yt-gallery-item">';
        echo '<a href="' . get_permalink() . '" class="yt-gallery-link">';
        echo '<div class="yt-gallery-thumbnail">';
        if ($thumbnail) echo '<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr(get_the_title()) . '" />';
        if ($g('show_duration') === 'yes' && $duration) {
            echo '<span class="yt-duration">' . $this->format_duration($duration) . '</span>';
        }
        echo '</div>';
        echo '<div class="yt-gallery-info">';
        if ($g('show_title') === 'yes') echo '<h3 class="yt-gallery-title">' . get_the_title() . '</h3>';
        if ($g('show_date') === 'yes') echo '<span class="yt-gallery-date">' . get_the_date() . '</span>';
        if ($g('show_description') === 'excerpt' && $description) {
            $len = intval($g('description_length', '150'));
            echo '<p class="yt-gallery-description">' . esc_html(substr($description, 0, $len)) . '...</p>';
        }
        echo '</div></a></div>';
    }
    
    private function format_duration($duration) {
        preg_match_all('/(\d+)/', $duration, $parts);
        $time_parts = array_reverse($parts[0]);
        $formatted = '';
        if (isset($time_parts[2])) $formatted = $time_parts[2] . ':';
        $formatted .= isset($time_parts[1]) ? str_pad($time_parts[1], 2, '0', STR_PAD_LEFT) . ':' : '00:';
        $formatted .= isset($time_parts[0]) ? str_pad($time_parts[0], 2, '0', STR_PAD_LEFT) : '00';
        return $formatted;
    }
}

new YT_WP_Importer();
