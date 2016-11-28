<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkboxes' element.
 *
 * @WebformElement(
 *   id = "checkboxes",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Checkboxes"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 * )
 */
class Checkboxes extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Options settings.
      'options_display' => 'one_column',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#options'];
    foreach ($selectors as &$text) {
      $text .= ' [' . $this->t('Checkbox') . ']';
    }
    return $selectors;
  }

}
