/**
 * @file
 * JavaScript behaviors for signature pad integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://github.com/szimek/signature_pad#options
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.signaturePad = Drupal.webform.signaturePad || {};
  Drupal.webform.signaturePad.options = Drupal.webform.signaturePad.options || {};

  /**
   * Initialize signature element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformSignature = {
    attach: function (context) {
      if (!window.SignaturePad) {
        return;
      }


      $(context).find('input.js-webform-signature').once('webform-signature').each(function () {
        var $input = $(this);
        var value = $input.val();
        var $wrapper = $input.parent();
        var $canvas = $wrapper.find('canvas');
        var $button = $wrapper.find(':button, :submit');
        var canvas = $canvas[0];

        var calculateDimensions = function () {
          $canvas.attr('width', $wrapper.width());
          $canvas.attr('height', $wrapper.width() / 3);
        };

        // Set height.
        $canvas.attr('width', $wrapper.width());
        $canvas.attr('height', $wrapper.width() / 3);
        $(window).resize(function () {
          calculateDimensions();

          // Resizing clears the canvas so we need to reset the signature pad.
          signaturePad.clear();
          var value = $input.val();
          if (value) {
            signaturePad.fromDataURL(value);
          }
        });

        // Initialize signature canvas.
        var options = $.extend({
          onEnd: function () {
            $input.val(signaturePad.toDataURL());
          }
        }, Drupal.webform.signaturePad.options);
        var signaturePad = new SignaturePad(canvas, options);

        // Set value.
        if (value) {
          signaturePad.fromDataURL(value);
        }

        // Set reset handler.
        $button.on('click', function () {
          signaturePad.clear();
          $input.val();
          this.blur();
          return false;
        });

        // Input onchange clears signature pad if value is empty.
        // Onchage events handlers are triggered when a webform is
        // hidden or shown.
        // @see webform.states.js
        // @see triggerEventHandlers()
        $input.on('change', function () {
          if (!$input.val()) {
            signaturePad.clear();
          }
          setTimeout(function () {
            calculateDimensions();
          }, 1);
        });

        // Turn signature pad off/on when the input is disabled/enabled.
        // @see webform.states.js
        $input.on('webform:disabled', function () {
          if ($input.is(':disabled')) {
            signaturePad.off();
          }
          else {
            signaturePad.on();
          }
        });

      });
    }
  };

})(jQuery, Drupal);
