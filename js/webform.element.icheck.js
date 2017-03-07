/**
 * @file
 * Javascript behaviors for iCheck integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Enhance checkboxes and radios using iCheck.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformICheck = {
    attach: function (context) {
      $('[data-webform-icheck]', context).each(function () {
        var icheck = $(this).attr('data-webform-icheck');
        $(this).find('input').addClass('js-webform-icheck')
          .iCheck({
            checkboxClass: 'icheckbox_' + icheck,
            radioClass: 'iradio_' + icheck
          })
          // @see https://github.com/fronteed/iCheck/issues/244
          .on('ifChecked', function (e) {
            $(e.target).attr('checked', 'checked').change();
          })
          .on('ifUnchecked', function (e) {
            $(e.target).removeAttr('checked').click();
          })
      });
    }
  };

  /**
   * Enhance table select checkall.
   *
   * ISSUE: Select all is not sync'd with checkboxes because iCheck overrides all existing event handlers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformICheckTableSelectAll = {
    attach: function(context) {
      $('table[data-webform-icheck] th.select-all').bind('DOMNodeInserted', function() {
        $(this).unbind('DOMNodeInserted');
        $(this).find('input[type="checkbox"]').each(function() {
            var icheck = $(this).closest('table[data-webform-icheck]').attr('data-webform-icheck');
            $(this).iCheck({
              checkboxClass: 'icheckbox_' + icheck,
              radioClass: 'iradio_' + icheck
            })
          })
          .on('ifChanged', function() {
            var _index = $(this).parents('th').index() + 1;
            $(this).parents('thead').next('tbody').find('tr td:nth-child(' + _index + ') input')
              .iCheck(!$(this).is(':checked') ? 'check' : 'uncheck')
              .iCheck($(this).is(':checked') ? 'check' : 'uncheck');
          });
      });
    }
  };

  /**
   * Sync iCheck element when checkbox/radio is enabled/disabled via the #states.
   *
   * @see core/misc/states.js
   */
  $(document).on('state:disabled', function (e) {
    if ($(e.target).hasClass('.js-webform-icheck')) {
      $(e.target).iCheck(e.value ? 'disable' : 'enable');
    }

    $(e.target).iCheck(e.value ? 'disable' : 'enable');
  });

})(jQuery, Drupal);
