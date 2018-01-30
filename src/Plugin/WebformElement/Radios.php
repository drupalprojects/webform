<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'radios' element.
 *
 * @WebformElement(
 *   id = "radios",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios",
 *   label = @Translation("Radios"),
 *   description = @Translation("Provides a form element for a set of radio buttons."),
 *   category = @Translation("Options elements"),
 * )
 */
class Radios extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Form display.
      'options_display' => 'one_column',
      'options_description_display' => 'description',
      // iCheck settings.
      'icheck' => '',
    ] + parent::getDefaultProperties();
  }

}
