(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.landingPage = {
    attach: function (context, settings) {
      // Handle copy sections without images
      once('landingPageCopy', '.paragraph--type--landing-page-copy', context).forEach(function (element) {
        const $copySection = $(element);
        const hasImage = $copySection.find('.field--name-field-copy-image').length > 0;

        if (!hasImage) {
          $copySection.find('.landing-page-copy-text').addClass('remove-margin-lp');
        }
      });

      // Fit text to CTA buttons
      once('landingPageButtonFit', '.landing-page-cta-primary, .landing-page-cta-secondary', context).forEach(function (element) {
        fitTextToButton(element);
      });

      // Enhanced sticky behavior for landing page header content
      once('landingPageSticky', '.landing-page-sdc__field--field-landing-page-header__content-left', context).forEach(function (element) {
        const $stickyElement = $(element);
        const $contentContainer = $stickyElement.closest('.landing-page-sdc__field--field-landing-page-header__content');
        const $rightColumn = $contentContainer.find('.landing-page-sdc__field--field-landing-page-header__content-right');

        // Only apply enhanced behavior on desktop
        if (window.innerWidth > 768) {
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

  // Function to fit text to button
  function fitTextToButton(button) {
    const $button = $(button);
    const $text = $button.contents().filter(function() {
      return this.nodeType === 3; // Text nodes only
    }).first();

    if ($text.length === 0) return;

    const maxWidth = $button.outerWidth() - 48; // Account for padding (24px each side)
    const maxHeight = $button.outerHeight() - 16; // Account for padding (8px each side)

    // Create a temporary span to measure text
    const $tempSpan = $('<span>').text($text.text()).css({
      position: 'absolute',
      visibility: 'hidden',
      whiteSpace: 'nowrap',
      fontSize: $button.css('font-size')
    });

    $('body').append($tempSpan);

    let fontSize = parseInt($button.css('font-size'));
    const minFontSize = 10; // Minimum font size in pixels

    $tempSpan.css('fontSize', fontSize + 'px');

    // Reduce font size until text fits
    while (($tempSpan.outerWidth() > maxWidth || $tempSpan.outerHeight() > maxHeight) && fontSize > minFontSize) {
      fontSize--;
      $tempSpan.css('fontSize', fontSize + 'px');
    }

    // Apply the calculated font size to the button
    $button.css('font-size', fontSize + 'px');

    // Clean up
    $tempSpan.remove();
  }

  // Handle window resize for button text fitting
  $(window).on('resize.landingPageButtonFit', function() {
    $('.landing-page-cta-primary, .landing-page-cta-secondary').each(function() {
      fitTextToButton(this);
    });
  });

})(jQuery, Drupal);