/**
 * @file
 * JavaScript behaviors for Chosen integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://harvesthq.github.io/chosen/options.html
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.chosen = Drupal.webform.chosen || {};
  Drupal.webform.chosen.options = Drupal.webform.chosen.options || {};

  /**
   * Initialize Chosen support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformChosen = {
    attach: function (context) {
      if (!$.fn.chosen) {
        return;
      }

      // Add HTML5 required attribute support.
      // Checking for $oldChosen to prevent duplicate workarounds from
      // being applied.
      // @see https://github.com/harvesthq/chosen/issues/515
      if (!$.fn.oldChosen) {
        $.fn.oldChosen = $.fn.chosen;
        $.fn.chosen = function (options) {
          var select = $(this), is_creating_chosen = !!options;
          if (is_creating_chosen && select.css('position') === 'absolute') {
            select.removeAttr('style');
          }
          var ret = select.oldChosen(options);
          if (is_creating_chosen && select.css('display') === 'none') {
            select.attr('style', 'display:visible; position:absolute; width:0px; height: 0px; clip:rect(0,0,0,0)');
            select.attr('tabindex', -1);
          }
          return ret;
        };
      }

      var options = $.extend({width: '100%'}, Drupal.webform.chosen.options);

      $(context)
        .find('select.js-webform-chosen, .js-webform-chosen select')
        .once('webform-chosen')
        .each(function () {
          var $select = $(this);
          // Check for .chosen-enable to prevent the chosen.module and
          // webform.module from both initializing the chosen select element.
          if (!$select.hasClass('chosen-enable')) {
            $select.chosen(options);
          }
        })

    }
  };

})(jQuery, Drupal);
