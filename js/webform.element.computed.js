/**
 * @file
 * JavaScript behaviors for computed elements.
 */

(function ($, Drupal) {

  'use strict';

  // @see http://qwertypants.github.io/jQuery-Word-and-Character-Counter-Plugin/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.computed = Drupal.webform.computed || {};
  Drupal.webform.computed.delay = Drupal.webform.computed.delay || 500;

  /**
   * Initialize computed elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformComputed = {
    attach: function (context) {
      $(context).find('.js-webform-computed').once('webform-computed').each(function () {
        // Get computed element and parent form.
        var $element = $(this);
        var $form = $element.closest('form');

        // Get elements that are used by the computed element.
        var elementKeys = $(this).data('webform-element-keys').split(',');
        if (!elementKeys) {
          return;
        }

        // Add event handler to elements that are used by the computed element.
        $.each(elementKeys, function( i, key) {
          $form.find(':input[name^="' + key + '"]')
            .on('keyup change', setUpdate);
        });

        // Initialize computed element update which refreshes the displayed
        // value and accounts for any changes to the #default_value for a
        // computed element.
        triggerUpdate();

        // Update timer
        var timer;

        function setUpdate() {
          window.clearTimeout(timer);
          timer = window.setTimeout(triggerUpdate, Drupal.webform.computed.delay);
        }

        function triggerUpdate() {
          $element.find('.js-form-submit').mousedown();
        }
      });
    }
  };

})(jQuery, Drupal);
