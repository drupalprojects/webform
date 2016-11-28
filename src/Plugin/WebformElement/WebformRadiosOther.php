<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'radios_other' element.
 *
 * @WebformElement(
 *   id = "webform_radios_other",
 *   label = @Translation("Radios other"),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformRadiosOther extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + self::getOtherProperties();
  }

}
