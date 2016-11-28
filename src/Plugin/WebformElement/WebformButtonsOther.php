<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'buttons_other' element.
 *
 * @WebformElement(
 *   id = "webform_buttons_other",
 *   label = @Translation("Buttons other"),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformButtonsOther extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + self::getOtherProperties();
  }

}
