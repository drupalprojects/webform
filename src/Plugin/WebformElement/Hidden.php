<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformInterface;

/**
 * Provides a 'hidden' element.
 *
 * @WebformElement(
 *   id = "hidden",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Hidden.php/class/Hidden",
 *   label = @Translation("Hidden"),
 *   description = @Translation("Provides a form element for an HTML 'hidden' input element."),
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
      'title' => '',
      'value' => '',
      'prepopulate' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Hidden elements should never get a test value.
    return NULL;
  }

}
