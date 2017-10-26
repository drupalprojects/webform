/**
 * @file
 * JavaScript behaviors for webform wizard.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Append the wizard page to the form's acction.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the webform previous and next submit button.
   */
  Drupal.behaviors.webformWizardPage = {
    attach: function (context) {
      $(':button[data-webform-wizard-page], :submit[data-webform-wizard-page]', context).once('webform-wizard-page').on('click', function() {
        var page = $(this).attr('data-webform-wizard-page');
        this.form.action = this.form.action.replace(/\?.+$/, '') + '?page=' + page;
      });
    }
  };


})(jQuery, Drupal);
