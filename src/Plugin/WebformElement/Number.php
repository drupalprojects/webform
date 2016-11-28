<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'number' element.
 *
 * @WebformElement(
 *   id = "number",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Number.php/class/Number",
 *   label = @Translation("Number"),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Number extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Number settings.
      'min' => '',
      'max' => '',
      'step' => '',
    ];
  }

}
