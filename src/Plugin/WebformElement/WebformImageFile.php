<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_image_file' element.
 *
 * @WebformElement(
 *   id = "webform_image_file",
 *   label = @Translation("Image file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformImageFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    $formats = parent::getFormats();
    $formats['file'] = $this->t('Image');
    return $formats;
  }

}
