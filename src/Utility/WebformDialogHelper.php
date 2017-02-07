<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Serialization\Json;

/**
 * Helper class for dialog methods.
 */
class WebformDialogHelper {

  /**
   * Get modal dialog attributes.
   *
   * @param int $width
   *   Width of the modal dialog.
   * @param array $class
   *   Additional class names to be included in the dialog's attributes.
   *
   * @return array
   *   Modal dialog attributes.
   */
  public static function getModalDialogAttributes($width = 800, array $class = []) {
    if (\Drupal::config('webform.settings')->get('ui.dialog_disabled')) {
      return $class ? ['class' => $class] : [];
    }
    else {
      $class[] = 'use-ajax';
      if (WebformDialogHelper::useOffCanvas()) {
        return [
          'class' => $class,
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'offcanvas',
          'data-dialog-options' => Json::encode([
            'width' => ($width > 480) ? 480 : $width,
          ]),
        ];
      }
      else {
        return [
          'class' => $class,
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => $width,
          ]),
        ];
      }
    }
  }

  /**
   * Use outside-in off-canvas system tray instead of dialogs.
   *
   * @return bool
   *   TRUE if outside_in.module is enabled and system trays are not disabled.
   */
  public static function useOffCanvas() {
    return ((floatval(\Drupal::VERSION) >= 8.3) && \Drupal::moduleHandler()->moduleExists('outside_in') && !\Drupal::config('webform.settings')->get('ui.offcanvas_disabled')) ? TRUE : FALSE;
  }

}
