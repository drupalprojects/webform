/**
 * @file
 * JavaScript behaviors for jQuery UI buttons (checkboxradio) element integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Create jQuery UI buttons (checkboxradio) element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformButtonsCheckboxRadio = {
    attach: function (context) {
      $(context).find('.js-webform-buttons .form-radios, .js-webform-buttons.form-radios, .js-webform-buttons .js-webform-radios').once('webform-buttons').each(function () {
        var $buttons = $(this);

        // Remove classes around radios and labels and move to main element.
        $buttons.find('input[type="radio"], label').each(function() {
          $buttons.append($(this).removeAttr('class'));
        });

        // Remove all empty div wrappers.
        $buttons.find('div').remove();

        // Must reset $buttons since the contents have changed.
        $buttons = $(this);

        // Get radios.
        var $input = $buttons.find('input[type="radio"]');

        // Create checkboxradio.
        $input.checkboxradio({'icon': false});

        // Disable checkboxradio.
        $input.checkboxradio('option', 'disabled', $input.is(':disabled'));

        // Turn checkboxradio off/on when the input is disabled/enabled.
        // @see webform.states.js
        $input.on('webform:disabled', function () {
          $input.checkboxradio('option', 'disabled', $input.is(':disabled'));
        });
      });
    }
  };

})(jQuery, Drupal);
