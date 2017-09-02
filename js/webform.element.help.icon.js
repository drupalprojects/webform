/**
 * @file
 * JavaScript behaviors for element help icon (tooltip).
 */

(function ($, Drupal) {

  'use strict';

  // @see http://api.jqueryui.com/tooltip/
  Drupal.webform = Drupal.webform || {};

  Drupal.webform.elementHelpIcon = Drupal.webform.elementHelpIcon || {};
  Drupal.webform.elementHelpIcon.options = Drupal.webform.elementHelpIcon.options || {};

  /**
   * Element help icon.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformElementHelpIcon = {
    attach: function (context) {
      $(context).find('.webform-element-help-icon').once('webform-element-help-icon').each(function () {
        var $link = $(this);

        var options = $.extend({}, Drupal.webform.elementHelpIcon.options);

        $link.tooltip(options).on('click', function (event) {
          event.preventDefault();
        });
      });
    }
  };

})(jQuery, Drupal);
