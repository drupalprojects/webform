/**
 * @file
 * JavaScript behaviors for Webform UI.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Remove .button-primary class from .action-links .button-secondary.
   *
   * The seven.theme adds the .button-primary class to all actions.
   *
   * @type {Drupal~behavior}
   *
   * @see webform_ui_preprocess_menu_local_action()
   * @see seven_preprocess_menu_local_action()
   * @see webform_ui.module.css
   */
  Drupal.behaviors.webformUiElementsActionsSecondary = {
    attach: function (context, settings) {
      $(context).find('.action-links .button--secondary').once('webform-ui-elements-webform-actions-secondary').each(function () {
        $(this).removeClass('button--primary');
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
