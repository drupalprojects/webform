<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'textfield' element.
 *
 * @WebformElement(
 *   id = "textfield",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textfield.php/class/Textfield",
 *   label = @Translation("Text field"),
 *   category = @Translation("Basic elements"),
 * )
 */
class TextField extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Form display.
      'input_mask' => '',
      // Form validation.
      'counter_type' => '',
      'counter_maximum' => '',
      'counter_message' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);
    $element['#maxlength'] = (!isset($element['#maxlength'])) ? 255 : $element['#maxlength'];
  }

}
