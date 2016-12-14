/**
 * @file
 * Javascript behaviors for jquery.inputmask integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize input masks.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformElementMask = {
    attach: function (context) {
      $(context).find('input.js-webform-element-mask').once('webform-element-mask').inputmask();
    }
  };

})(jQuery, Drupal);
