<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'toggles' element.
 *
 * @WebformElement(
 *   id = "webform_toggles",
 *   label = @Translation("Toggles"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 * )
 */
class WebformToggles extends OptionsBase {

  use WebformToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'toggle_theme' => 'light',
      'toggle_size' => 'medium',
      'on_text' => '',
      'off_text' => '',
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
      $text .= ' [' . $this->t('Toggle') . ']';
    }
    return $selectors;
  }

}
