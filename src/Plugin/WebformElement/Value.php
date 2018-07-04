<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformInterface;

/**
 * Provides a 'value' element.
 *
 * @WebformElement(
 *   id = "value",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Value.php/class/Value",
 *   label = @Translation("Value"),
 *   description = @Translation("Provides a form element for storage of internal information."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Value extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Element settings.
      'title' => '',
      'value' => '',
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
  public function getElementSelectorOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Value elements should never get a test value.
    return NULL;
  }

}
