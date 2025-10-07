(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize all carousels
        $('.yt-layout-carousel').each(function() {
            initCarousel($(this));
        });
    });
    
    function initCarousel($carousel) {
        const $track = $carousel.find('.yt-carousel-track');
        const $slides = $track.find('.yt-carousel-slide');
        const $prev = $carousel.find('.yt-carousel-prev');
        const $next = $carousel.find('.yt-carousel-next');
        const $dotsContainer = $carousel.find('.yt-carousel-dots');
        
        if ($slides.length === 0) return;
        
        let currentIndex = 0;
        const autoplay = $carousel.data('autoplay') === 'yes';
        const speed = parseInt($carousel.data('speed')) || 3000;
        let autoplayInterval;
        
        // Calculate visible slides based on screen width
        function getVisibleSlides() {
            const width = $(window).width();
            if (width <= 640) return 1;
            if (width <= 1024) return 2;
            return 3;
        }
        
        let visibleSlides = getVisibleSlides();
        const totalPages = Math.ceil($slides.length / visibleSlides);
        
        // Create dots
        function createDots() {
            $dotsContainer.empty();
            for (let i = 0; i < totalPages; i++) {
                const $dot = $('<button class="yt-carousel-dot"></button>');
                if (i === 0) $dot.addClass('active');
                $dot.on('click', function() {
                    goToPage(i);
                });
                $dotsContainer.append($dot);
            }
        }
        
        createDots();
        
        // Go to specific page
        function goToPage(pageIndex) {
            if (pageIndex < 0) pageIndex = totalPages - 1;
            if (pageIndex >= totalPages) pageIndex = 0;
            
            currentIndex = pageIndex;
            
            const slideWidth = $slides.first().outerWidth(true);
            const offset = -(slideWidth * pageIndex * visibleSlides);
            
            $track.css('transform', `translateX(${offset}px)`);
            
            // Update dots
            $dotsContainer.find('.yt-carousel-dot').removeClass('active');
            $dotsContainer.find('.yt-carousel-dot').eq(pageIndex).addClass('active');
            
            // Reset autoplay
            if (autoplay) {
                clearInterval(autoplayInterval);
                startAutoplay();
            }
        }
        
        // Previous button
        $prev.on('click', function() {
            goToPage(currentIndex - 1);
        });
        
        // Next button
        $next.on('click', function() {
            goToPage(currentIndex + 1);
        });
        
        // Autoplay
        function startAutoplay() {
            if (!autoplay) return;
            
            autoplayInterval = setInterval(function() {
                goToPage(currentIndex + 1);
            }, speed);
        }
        
        startAutoplay();
        
        // Pause on hover
        $carousel.on('mouseenter', function() {
            if (autoplay) {
                clearInterval(autoplayInterval);
            }
        });
        
        $carousel.on('mouseleave', function() {
            if (autoplay) {
                startAutoplay();
            }
        });
        
        // Handle window resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const newVisibleSlides = getVisibleSlides();
                if (newVisibleSlides !== visibleSlides) {
                    visibleSlides = newVisibleSlides;
                    currentIndex = 0;
                    createDots();
                    goToPage(0);
                }
            }, 250);
        });
        
        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;
        
        $track.on('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
        });
        
        $track.on('touchmove', function(e) {
            touchEndX = e.touches[0].clientX;
        });
        
        $track.on('touchend', function() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next
                    goToPage(currentIndex + 1);
                } else {
                    // Swipe right - previous
                    goToPage(currentIndex - 1);
                }
            }
        });
    }
    
})(jQuery);
