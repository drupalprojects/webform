<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'checkbox' element.
 *
 * @WebformElement(
 *   id = "checkbox",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox",
 *   label = @Translation("Checkbox"),
 *   category = @Translation("Basic elements"),
 * )
 */
class Checkbox extends BooleanBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title_display' => 'after',
    ] + parent::getDefaultProperties();
  }

}
