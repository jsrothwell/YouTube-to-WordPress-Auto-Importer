<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap yt-admin-wrapper">
    <h1>YouTube to WordPress Importer</h1>
    
    <?php if ($import_msg): ?>
        <div class="notice notice-success"><p><strong>✓ <?php echo esc_html($import_msg); ?></strong></p></div>
    <?php endif; ?>
    
    <?php if ($refresh_msg): ?>
        <div class="notice notice-success"><p><strong>✓ <?php echo esc_html($refresh_msg); ?></strong></p></div>
    <?php endif; ?>
    
    <div class="yt-admin-tabs">
        <button class="yt-tab-button active" data-tab="connection">Connection</button>
        <button class="yt-tab-button" data-tab="import">Import Settings</button>
        <button class="yt-tab-button" data-tab="gallery">Gallery Display</button>
        <button class="yt-tab-button" data-tab="actions">Actions</button>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('yt_wp_importer_group'); ?>
        
        <!-- CONNECTION TAB -->
        <div class="yt-tab-content active" data-tab="connection">
            <div class="yt-settings-card">
                <h2>🔗 YouTube Connection</h2>
                <table class="form-table">
                    <tr>
                        <th>YouTube Channel ID *</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name; ?>[channel_id]" 
                                   value="<?php echo esc_attr($g('channel_id')); ?>" class="regular-text" />
                            <p class="description">Find in YouTube Studio → Settings → Channel → Advanced</p>
                        </td>
                    </tr>
                    <tr>
                        <th>YouTube API Key *</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name; ?>[api_key]" 
                                   value="<?php echo esc_attr($g('api_key')); ?>" class="regular-text" />
                            <p class="description">Get from <a href="https://console.developers.google.com/" target="_blank">Google Developers Console</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Check Frequency</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[check_frequency]">
                                <option value="hourly" <?php selected($g('check_frequency', 'hourly'), 'hourly'); ?>>Every Hour</option>
                                <option value="twicedaily" <?php selected($g('check_frequency', 'hourly'), 'twicedaily'); ?>>Twice Daily</option>
                                <option value="daily" <?php selected($g('check_frequency', 'hourly'), 'daily'); ?>>Once Daily</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- IMPORT TAB -->
        <div class="yt-tab-content" data-tab="import">
            <div class="yt-settings-card">
                <h2>📥 Post Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Post Status</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[post_status]">
                                <option value="publish" <?php selected($g('post_status', 'publish'), 'publish'); ?>>Publish</option>
                                <option value="draft" <?php selected($g('post_status', 'publish'), 'draft'); ?>>Draft</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Category</th>
                        <td>
                            <?php wp_dropdown_categories(array(
                                'name' => $this->option_name . '[post_category]',
                                'selected' => $g('post_category'),
                                'show_option_none' => 'None',
                                'option_none_value' => ''
                            )); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Tags</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name; ?>[post_tags]" 
                                   value="<?php echo esc_attr($g('post_tags')); ?>" class="regular-text" />
                            <p class="description">Comma-separated</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Title Format</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name; ?>[title_format]" 
                                   value="<?php echo esc_attr($g('title_format', '{title}')); ?>" class="regular-text" />
                            <p class="description">Use {title} as placeholder</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Options</th>
                        <td>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[embed_video]" value="yes" <?php checked($g('embed_video'), 'yes'); ?>> Embed video in post</label><br>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[include_description]" value="yes" <?php checked($g('include_description'), 'yes'); ?>> Include description</label><br>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[set_featured_image]" value="yes" <?php checked($g('set_featured_image'), 'yes'); ?>> Set featured image</label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="yt-settings-card">
                <h2>🔄 Update Existing Posts</h2>
                <table class="form-table">
                    <tr>
                        <th>Auto-Update</th>
                        <td>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[update_existing_posts]" value="yes" <?php checked($g('update_existing_posts'), 'yes'); ?>> Enable automatic updates</label>
                            <p class="description">Refresh existing posts during scheduled checks</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Update Options</th>
                        <td>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[update_featured_images]" value="yes" <?php checked($g('update_featured_images'), 'yes'); ?>> Update featured images</label><br>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[update_metadata]" value="yes" <?php checked($g('update_metadata'), 'yes'); ?>> Update titles & descriptions</label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- GALLERY TAB -->
        <div class="yt-tab-content" data-tab="gallery">
            <div class="yt-settings-card">
                <h2>🎨 Gallery Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Layout Type</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[layout_type]">
                                <option value="grid" <?php selected($g('layout_type', 'grid'), 'grid'); ?>>Grid</option>
                                <option value="gallery" <?php selected($g('layout_type', 'grid'), 'gallery'); ?>>Gallery (Masonry)</option>
                                <option value="cards" <?php selected($g('layout_type', 'grid'), 'cards'); ?>>Cards</option>
                                <option value="list" <?php selected($g('layout_type', 'grid'), 'list'); ?>>List</option>
                                <option value="carousel" <?php selected($g('layout_type', 'grid'), 'carousel'); ?>>Carousel</option>
                                <option value="showcase" <?php selected($g('layout_type', 'grid'), 'showcase'); ?>>Showcase</option>
                                <option value="showcase-carousel" <?php selected($g('layout_type', 'grid'), 'showcase-carousel'); ?>>Showcase Carousel</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Columns</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[gallery_columns]">
                                <option value="2" <?php selected($g('gallery_columns', '3'), '2'); ?>>2</option>
                                <option value="3" <?php selected($g('gallery_columns', '3'), '3'); ?>>3</option>
                                <option value="4" <?php selected($g('gallery_columns', '3'), '4'); ?>>4</option>
                                <option value="5" <?php selected($g('gallery_columns', '3'), '5'); ?>>5</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Videos Per Page</th>
                        <td>
                            <input type="number" name="<?php echo $this->option_name; ?>[videos_per_page]" 
                                   value="<?php echo esc_attr($g('videos_per_page', '12')); ?>" min="1" max="50" />
                        </td>
                    </tr>
                    <tr>
                        <th>Show Elements</th>
                        <td>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[show_title]" value="yes" <?php checked($g('show_title'), 'yes'); ?>> Title</label><br>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[show_duration]" value="yes" <?php checked($g('show_duration'), 'yes'); ?>> Duration</label><br>
                            <label><input type="checkbox" name="<?php echo $this->option_name; ?>[show_date]" value="yes" <?php checked($g('show_date'), 'yes'); ?>> Date</label>
                        </td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[show_description]">
                                <option value="none" <?php selected($g('show_description', 'excerpt'), 'none'); ?>>None</option>
                                <option value="excerpt" <?php selected($g('show_description', 'excerpt'), 'excerpt'); ?>>Excerpt</option>
                                <option value="full" <?php selected($g('show_description', 'excerpt'), 'full'); ?>>Full</option>
                            </select>
                            <input type="number" name="<?php echo $this->option_name; ?>[description_length]" 
                                   value="<?php echo esc_attr($g('description_length', '150')); ?>" 
                                   min="50" max="500" style="width: 80px; margin-left: 10px;" /> chars
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="yt-settings-card">
                <h2>✨ Visual Style</h2>
                <table class="form-table">
                    <tr>
                        <th>Card Style</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[card_style]">
                                <option value="default" <?php selected($g('card_style', 'default'), 'default'); ?>>Default</option>
                                <option value="minimal" <?php selected($g('card_style', 'default'), 'minimal'); ?>>Minimal</option>
                                <option value="elevated" <?php selected($g('card_style', 'default'), 'elevated'); ?>>Elevated</option>
                                <option value="bordered" <?php selected($g('card_style', 'default'), 'bordered'); ?>>Bordered</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Hover Effect</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[hover_effect]">
                                <option value="lift" <?php selected($g('hover_effect', 'lift'), 'lift'); ?>>Lift</option>
                                <option value="zoom" <?php selected($g('hover_effect', 'lift'), 'zoom'); ?>>Zoom</option>
                                <option value="fade" <?php selected($g('hover_effect', 'lift'), 'fade'); ?>>Fade</option>
                                <option value="none" <?php selected($g('hover_effect', 'lift'), 'none'); ?>>None</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Aspect Ratio</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[thumbnail_aspect]">
                                <option value="16-9" <?php selected($g('thumbnail_aspect', '16-9'), '16-9'); ?>>16:9</option>
                                <option value="4-3" <?php selected($g('thumbnail_aspect', '16-9'), '4-3'); ?>>4:3</option>
                                <option value="1-1" <?php selected($g('thumbnail_aspect', '16-9'), '1-1'); ?>>1:1</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Spacing</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[spacing]">
                                <option value="compact" <?php selected($g('spacing', 'medium'), 'compact'); ?>>Compact</option>
                                <option value="medium" <?php selected($g('spacing', 'medium'), 'medium'); ?>>Medium</option>
                                <option value="spacious" <?php selected($g('spacing', 'medium'), 'spacious'); ?>>Spacious</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Border Radius</th>
                        <td>
                            <select name="<?php echo $this->option_name; ?>[border_radius]">
                                <option value="none" <?php selected($g('border_radius', 'medium'), 'none'); ?>>None</option>
                                <option value="small" <?php selected($g('border_radius', 'medium'), 'small'); ?>>Small</option>
                                <option value="medium" <?php selected($g('border_radius', 'medium'), 'medium'); ?>>Medium</option>
                                <option value="large" <?php selected($g('border_radius', 'medium'), 'large'); ?>>Large</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- ACTIONS TAB -->
        <div class="yt-tab-content" data-tab="actions">
            <div class="yt-settings-card">
                <h2>🚀 Quick Actions</h2>
                <p>
                    <button type="button" class="button button-primary" onclick="document.getElementById('manual_import_form').submit();">
                        Import New Videos Now
                    </button>
                    <button type="button" class="button button-secondary" onclick="if(confirm('Update all posts?')) document.getElementById('refresh_posts_form').submit();" style="margin-left: 10px;">
                        Refresh All Posts
                    </button>
                </p>
            </div>
            
            <div class="yt-settings-card">
                <h2>📖 Usage</h2>
                <p><strong>Basic:</strong> <code>[youtube_gallery]</code></p>
                <p><strong>With layout:</strong> <code>[youtube_gallery layout="carousel"]</code></p>
                <p><strong>With options:</strong> <code>[youtube_gallery layout="cards" columns="4"]</code></p>
            </div>
        </div>
        
        <p class="submit">
            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
        </p>
    </form>
    
    <form method="post" id="manual_import_form" style="display:none;">
        <input type="hidden" name="manual_import" value="1" />
        <?php wp_nonce_field('yt_manual_import', 'yt_import_nonce'); ?>
    </form>
    
    <form method="post" id="refresh_posts_form" style="display:none;">
        <input type="hidden" name="refresh_all_posts" value="1" />
        <?php wp_nonce_field('yt_refresh_posts', 'yt_refresh_nonce'); ?>
    </form>
    
    <style>
        .yt-admin-wrapper { max-width: 1000px; }
        .yt-admin-tabs { border-bottom: 2px solid #ddd; margin: 20px 0 0; }
        .yt-tab-button { background: none; border: none; padding: 12px 20px; cursor: pointer; font-size: 14px; font-weight: 600; color: #666; border-bottom: 3px solid transparent; }
        .yt-tab-button.active { color: #2271b1; border-bottom-color: #2271b1; }
        .yt-tab-content { display: none; padding: 20px 0; }
        .yt-tab-content.active { display: block; }
        .yt-settings-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .yt-settings-card h2 { margin-top: 0; }
    </style>
</div>
