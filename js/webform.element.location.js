/**
 * @file
 * Javascript behaviors for Geocomplete location integration.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Initialize location Geocompletion.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformLocation = {
    attach: function (context) {
      $(context).find('div.js-webform-location').once('webform-location').each(function () {
        var $element = $(this);
        var $geocomplete = $element.find('.webform-location-geocomplete').geocomplete({
          details: $element,
          detailsAttribute: 'data-webform-location-attribute',
          types: ['geocode']
        });

        $geocomplete.on('input', function() {
          // Reset attributes on input.
          $element.find('[data-webform-location-attribute]').val('');
        }).on('blur', function() {
          // Make sure to get attributes on blur.
          if ($element.find('[data-webform-location-attribute="location"]').val() == '') {
            var value = $geocomplete.val();
            if (value) {
              $geocomplete.geocomplete('find', value);
            }
          }
        });


        // If there is default value look up location's attributes, else see if
        // the default value should be set to the browser's current geolocation.
        var value = $geocomplete.val();
        if (value) {
          $geocomplete.geocomplete('find', value);
        }
        else if (navigator.geolocation && $geocomplete.attr('data-webform-location-geolocation')) {
          navigator.geolocation.getCurrentPosition(function (position) {
            $geocomplete.geocomplete('find', position.coords.latitude + ', ' + position.coords.longitude);
          });
        }
      })
    }
  };

})(jQuery, Drupal, drupalSettings);
