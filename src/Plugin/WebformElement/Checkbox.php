<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkbox' element.
 *
 * @WebformElement(
 *   id = "checkbox",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation("Provides a form element for a single checkbox."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Checkbox extends BooleanBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'title_display' => 'after',
      // Checkbox.
      'exclude_empty' => FALSE,
      // iCheck settings.
      'icheck' => '',
    ] + parent::getDefaultProperties();
    unset($properties['unique'], $properties['unique_entity'], $properties['unique_user'], $properties['unique_error']);
    return $properties;
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $options += [
      'exclude_empty_checkbox' => FALSE,
    ];

    $exclude_empty = $this->getElementProperty($element, 'exclude_empty') ?: $options['exclude_empty_checkbox'];
    if ($exclude_empty && !$this->getValue($element, $webform_submission, $options)) {
      return NULL;
    }
    else {
      return parent::build($format, $element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['display']['exclude_empty'] = [
      '#title' => $this->t('Exclude unselected checkbox'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
    ];

    return $form;
  }
}
