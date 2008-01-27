// $Id$

/**
 * Webform node form interface enhancments.
 */

if (Drupal.jsEnabled) {
  $(document).ready(function() {

    // Default values script.
    var $fields = $('.webform-default-value');
    var $forms = $('.webform-default-value').parents('form:first');
    $fields.each(function() {
      this.defaultValue = this.value;
      $(this).focus(function() {
        if (this.value == this.defaultValue) {
          this.value = '';
          $(this).removeClass('webform-default-value');
        }
      });
      $(this).blur(function() {
        if (this.value == '') {
          $(this).addClass('webform-default-value');
          this.value = this.defaultValue;
        }
      });
    });

    // Clear all the form elements before submission.
    $forms.submit(function() {
      $fields.focus();
    });
  });
}