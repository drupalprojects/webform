<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'hidden' element.
 *
 * @WebformElement(
 *   id = "hidden",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Hidden.php/class/Hidden",
 *   label = @Translation("Hidden"),
 *   category = @Translation("Basic elements"),
 * )
 */
class Hidden extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Element settings.
      'value' => '',
    ];
  }

}
