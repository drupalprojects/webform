/**
 * @file
 * JavaScript behaviors to fix jQuery UI dialogs.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Ensure that ckeditor has focus when displayed inside of jquery-ui dialog widget
   *
   * @see http://stackoverflow.com/questions/20533487/how-to-ensure-that-ckeditor-has-focus-when-displayed-inside-of-jquery-ui-dialog
   */
  var _allowInteraction = $.ui.dialog.prototype._allowInteraction;
  $.ui.dialog.prototype._allowInteraction = function (event) {
    if ($(event.target).closest('.cke_dialog').length) {
      return true;
    }
    return _allowInteraction.apply(this, arguments);
  };

  /**
   * Never focus dialog on first link.
   */
  $.ui.dialog.prototype._focusTabbable = function() {
    var hasFocus = this.element.find("[autofocus]");
    if (!hasFocus.length) {
      hasFocus = this.element.find(":tabbable:not(a.webform-element-help)");
    }
    if (!hasFocus.length) {
      hasFocus = this.uiDialogButtonPane.find(":tabbable:not(a.webform-element-help)");
    }
    if (!hasFocus.length) {
      hasFocus = this.uiDialogTitlebarClose.filter(":tabbable:not(a.webform-element-help)");
    }
    if (!hasFocus.length) {
      hasFocus = this.uiDialog;
    }
    hasFocus.eq(0).focus();
  };

})(jQuery, Drupal);
