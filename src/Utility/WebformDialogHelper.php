<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

/**
 * Helper class for dialog methods.
 */
class WebformDialogHelper {

  /**
   * Attach libraries required by (modal) dialogs.
   *
   * @param array $build
   *   A render array.
   */
  public static function attachLibraries(array &$build) {
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';
    // @see \Drupal\webform\Element\WebformHtmlEditor::preRenderWebformHtmlEditor
    if (\Drupal::moduleHandler()->moduleExists('imce') && \Drupal\imce\Imce::access()) {
      $element['#attached']['library'][] = 'imce/drupal.imce.ckeditor';
      $element['#attached']['drupalSettings']['webform']['html_editor']['ImceImageIcon'] = file_create_url(drupal_get_path('module', 'imce') . '/js/plugins/ckeditor/icons/imceimage.png');
    }
  }

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
