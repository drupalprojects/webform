<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'select' element.
 *
 * @WebformElement(
 *   id = "select",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Select.php/class/Select",
 *   label = @Translation("Select"),
 *   category = @Translation("Options elements"),
 * )
 */
class Select extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Options settings.
      'multiple' => FALSE,
      'empty_option' => '',
      'empty_value' => '',
      'select2' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);
    if (empty($element['#multiple'])) {
      if (!isset($element['#empty_option'])) {
        $element['#empty_option'] = empty($element['#required']) ? $this->t('- Select -') : $this->t('- None -');
      }
    }
    else {
      if (!isset($element['#empty_option'])) {
        $element['#empty_option'] = empty($element['#required']) ? $this->t('- None -') : NULL;
      }
      $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    }

    // Add select2 library and classes.
    if (!empty($element['#select2'])) {
      $element['#attached']['library'][] = 'webform/webform.element.select2';
      $element['#attributes']['class'][] = 'js-webform-select2';
      $element['#attributes']['class'][] = 'webform-select2';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['options']['select2'] = [
      '#title' => $this->t('Select2'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#description' => $this->t('Replace select element with jQuery <a href=":href">Select2</a> box.', [':href' => 'https://select2.github.io/']),
    ];
    return $form;
  }

}
