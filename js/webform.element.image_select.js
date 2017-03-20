/**
 * @file
 * Javascript behaviors for webform_image_select and jQuery Image Picker integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://rvera.github.io/image-picker/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.imageSelect = Drupal.webform.imageSelect || {};
  Drupal.webform.imageSelect.options = Drupal.webform.imageSelect.options|| {};

  /**
   * Initialize image select.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformImageSelect = {
    attach: function (context) {
      $('.js-webform-image-select', context).once('webform-image-select').each(function () {
        var $select = $(this);

        // Apply image data to options.
        var images = JSON.parse($select.attr('data-images'));
        for (var value in images) {
          var image = images[value];
          // Escape double quotes in value
          var value = value.toString().replace(/"/g, '\\"');
          $select.find('option[value="' + value + '"]').attr({
            'data-img-src': image.src,
            'data-img-label': image.text,
            'data-img-alt': image.text
          });
        }

        var options = $.extend({
          hide_select : false
        }, Drupal.webform.imageSelect.options);

        if ($select.attr('data-show-label')) {
          options.show_label = true;
        }

        $select.imagepicker(options);
      });
    }
  };


  var entityMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#x60;',
    '=': '&#x3D;'
  };

  /**
   * Escape HTML.
   *
   * @param {string} text
   *   Text that may contain HTML.
   *
   * @return {string}
   *   Text with HTML escaped.
   *
   * @see http://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery
   */
  function escapeHtml(text) {
    return String(text).replace(/[&<>"'`=\/]/g, function (s) {
      return entityMap[s];
    });
  }

})(jQuery, Drupal);
