<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'radios' element.
 *
 * @WebformElement(
 *   id = "radios",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios",
 *   label = @Translation("Radios"),
 *   category = @Translation("Options elements"),
 * )
 */
class Radios extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Form display.
      'options_display' => 'one_column',
    ];
  }

}
