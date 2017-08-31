/**
 * @file
 * JavaScript behaviors for webform UI element.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Monitor the element's key (aka machine name).
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformUiElementKey = {
    attach: function (context) {
      if (!$(context).find(':input[name="key"]').length || !drupalSettings.webform_ui || !drupalSettings.webform_ui.reserved_keys) {
        return;
      }

      // Monitor the machine name and display a warning when a reserved word is
      // being used.
      // There is no way to capture changes to the key val.
      // @see core/misc/machine-name.js.
      setInterval(function () {
        var value = $(':input[name="key"]').val();
        if ($.inArray(value, drupalSettings.webform_ui.reserved_keys) !== -1) {
          // Customize and display the warning message.
          $('[data-drupal-selector="edit-key-warning"]').html(
            Drupal.t("Please avoid using the reserved word '@key' as the element's key.", {'@key': value})
          ).show();
        }
        else {
          // Hide the warning message.
          $('[data-drupal-selector="edit-key-warning"]').hide();
        }
      }, 300);

    }
  };

  /**
   * Webform UI tabs using jQuery UI.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for jQuery UI tabs.
   */
  Drupal.behaviors.webformUiElementTabs = {
    attach: function (context, settings) {
      $(context).find('div.webform-ui-element-tabs').once('webform-ui-element-tabs').tabs({
        hide: true,
        show: true
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
