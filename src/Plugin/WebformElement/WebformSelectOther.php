<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'select_other' element.
 *
 * @WebformElement(
 *   id = "webform_select_other",
 *   label = @Translation("Select other"),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformSelectOther extends Select {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + self::getOtherProperties();
  }

}
