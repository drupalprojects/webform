<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'tableselect' element.
 *
 * @WebformElement(
 *   id = "tableselect",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tableselect.php/class/Tableselect",
 *   label = @Translation("Table select"),
 *   category = @Translation("Options elements"),
 *   multiple = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class TableSelect extends OptionsBase {

  use WebformTableTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Options settings.
      'multiple' => TRUE,
      // Table settings.
      'js_select' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return $this->getTableSelectElementSelectorOptions($element);
  }

}
