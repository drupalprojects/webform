<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_video_file' element.
 *
 * @WebformElement(
 *   id = "webform_video_file",
 *   label = @Translation("Video file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformVideoFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    $formats = parent::getFormats();
    $formats['file'] = $this->t('HTML5 Video player (MP4 only)');
    return $formats;
  }

}
