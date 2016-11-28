<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_table_sort' element.
 *
 * @WebformElement(
 *   id = "webform_table_sort",
 *   label = @Translation("Table sort"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformTableSort extends OptionsBase {

  use WebformTableTrait;

  /**
   * {@inheritdoc}
   */
  protected $exportDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();
    unset($properties['options_randomize']);
    return $properties;
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
    return [];
  }

}
