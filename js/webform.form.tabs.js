/**
 * @file
 * JavaScript behaviors for form tabs using jQuery UI.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Intialize webform tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for preventing duplicate webform submissions.
   */
  Drupal.behaviors.webformFormTabs = {
    attach: function (context) {
      $(context).find('div.webform-tabs').once('webform-tabs').tabs({
        hide: true,
        show: true
      });
    }
  };

})(jQuery, Drupal);
