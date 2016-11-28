<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_tableselect_sort' element.
 *
 * @WebformElement(
 *   id = "webform_tableselect_sort",
 *   label = @Translation("Tableselect sort"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformTableSelectSort extends OptionsBase {

  use WebformTableTrait;

  /**
   * {@inheritdoc}
   */
  protected $exportDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Table settings.
      'js_select' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat() {
    return 'ol';
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return $this->getTableSelectElementSelectorOptions($element, '[checkbox]');
  }

}
