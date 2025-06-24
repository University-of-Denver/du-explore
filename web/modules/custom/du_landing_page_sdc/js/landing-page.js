(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.landingPage = {
    attach: function (context, settings) {
      console.log('Landing Page JS loaded');

      // Handle copy sections without images
      once('landingPageCopy', '.paragraph--type--landing-page-copy', context).forEach(function (element) {
        console.log('Found copy section:', element);
        const $copySection = $(element);
        const hasImage = $copySection.find('.field--name-field-copy-image').length > 0;
        console.log('Has image:', hasImage);

        if (!hasImage) {
          console.log('Adding remove-margin-top class');
          $copySection.find('.landing-page-copy-text').addClass('remove-margin-top');
        }
      });

      // Enhanced sticky behavior for landing page header content
      once('landingPageSticky', '.landing-page-sdc__field--field-landing-page-header__content-left', context).forEach(function (element) {
        const $stickyElement = $(element);
        const $contentContainer = $stickyElement.closest('.landing-page-sdc__field--field-landing-page-header__content');
        const $rightColumn = $contentContainer.find('.landing-page-sdc__field--field-landing-page-header__content-right');

        // Only apply enhanced behavior on desktop
        if (window.innerWidth > 768) {
          console.log('Enhanced sticky behavior initialized');

          // Add scroll event listener for fine-tuned control
          $(window).on('scroll.landingPageSticky', function() {
            const scrollTop = $(window).scrollTop();
            const containerTop = $contentContainer.offset().top;
            const containerHeight = $contentContainer.outerHeight();
            const windowHeight = $(window).height();

            // Calculate when sticky should activate
            const stickyThreshold = 100; // Same as CSS top value
            const shouldBeSticky = scrollTop > (containerTop + stickyThreshold);

            if (shouldBeSticky) {
              $stickyElement.addClass('is-sticky');

              // Ensure right column doesn't overlap
              const rightColumnHeight = $rightColumn.outerHeight();
              if (rightColumnHeight > windowHeight - stickyThreshold) {
                $stickyElement.css('max-height', (windowHeight - stickyThreshold - 20) + 'px');
                $stickyElement.css('overflow-y', 'auto');
              }
            } else {
              $stickyElement.removeClass('is-sticky');
              $stickyElement.css('max-height', '');
              $stickyElement.css('overflow-y', '');
            }
          });

          // Handle window resize
          $(window).on('resize.landingPageSticky', function() {
            if (window.innerWidth <= 768) {
              $stickyElement.removeClass('is-sticky');
              $stickyElement.css('max-height', '');
              $stickyElement.css('overflow-y', '');
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal);