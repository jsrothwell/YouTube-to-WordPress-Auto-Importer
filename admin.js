jQuery(document).ready(function($) {
    var optionName = ytAdmin.optionName;
    
    // Tab switching
    $('.yt-tab-button').on('click', function() {
        var tab = $(this).data('tab');
        $('.yt-tab-button').removeClass('active');
        $(this).addClass('active');
        $('.yt-tab-content').removeClass('active');
        $('.yt-tab-content[data-tab="' + tab + '"]').addClass('active');
    });
    
    // Show/hide excerpt length based on description setting
    function toggleExcerptLength() {
        if ($('#description_select').val() === 'excerpt') {
            $('#excerpt_length_setting').show();
        } else {
            $('#excerpt_length_setting').hide();
        }
    }
    toggleExcerptLength();
    $('#description_select').on('change', toggleExcerptLength);
    
    // Show/hide update settings based on toggle
    function toggleUpdateSettings() {
        if ($('#update_existing_toggle').is(':checked')) {
            $('#update_settings').show();
        } else {
            $('#update_settings').hide();
        }
    }
    toggleUpdateSettings();
    $('#update_existing_toggle').on('change', toggleUpdateSettings);
    
    // Show/hide layout-specific settings
    function toggleLayoutSettings() {
        var layout = $('input[name="' + optionName + '[layout_type]"]:checked').val();
        
        // Hide all first
        $('.yt-carousel-settings, .yt-showcase-settings, .yt-columns-setting').hide();
        
        // Show relevant ones
        if (layout === 'carousel' || layout === 'showcase-carousel') {
            $('.yt-carousel-settings').show();
        }
        if (layout === 'showcase' || layout === 'showcase-carousel') {
            $('.yt-showcase-settings').show();
        }
        if (layout !== 'list' && layout !== 'carousel') {
            $('.yt-columns-setting').show();
        }
    }
    toggleLayoutSettings();
    $('input[name="' + optionName + '[layout_type]"]').on('change', toggleLayoutSettings);
});
