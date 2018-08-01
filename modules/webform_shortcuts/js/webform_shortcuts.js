/**
 * @file
 * JavaScript behaviors for webform builder shortcuts.
 *
 * @see webform_shortcuts_preprocess_block()
 */

(function ($) {

  'use strict';

  // Add element.
  $(document).bind('keydown', 'ctrl+e', function () {
    $('#webform-ui-add-element').click();
  });

  // Add page.
  $(document).bind('keydown', 'ctrl+p', function () {
    $('#webform-ui-add-page').focus().click();
  });

  // Add layout.
  $(document).bind('keydown', 'ctrl+l', function () {
    $('#webform-ui-add-layout').click();
  });

  // Save element or elements.
  $(document).bind('keydown', 'ctrl+s', function () {
    var $dialogSubmit = $('form.webform-ui-element-form [data-drupal-selector="edit-submit"]');
    if ($dialogSubmit.length) {
      $dialogSubmit.click();
    }
    else {
      $('form.webform-edit-form [data-drupal-selector="edit-submit"]').click();
    }
  });

  // Reset elements.
  $(document).bind('keydown', 'ctrl+r', function () {
    $('form.webform-edit-form [data-drupal-selector="edit-reset"]').click();
  });

  // Toggle weight.
  $(document).bind('keydown', 'ctrl+w', function () {
    $('.tabledrag-toggle-weight').eq(0).click();
  });

})(jQuery);
