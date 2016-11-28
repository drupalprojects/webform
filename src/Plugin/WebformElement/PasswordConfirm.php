<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'password_confirm' element.
 *
 * @WebformElement(
 *   id = "password_confirm",
 *   label = @Translation("Password confirm"),
 *   category = @Translation("Advanced elements"),
 *   states_wrapper = TRUE,
 * )
 */
class PasswordConfirm extends Password {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validate'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return [
      'pass1' => $this->getAdminLabel($element) . ' 1 [' . $this->t('Password') . ']',
      'pass2' => $this->getAdminLabel($element) . ' 2 [' . $this->t('Password') . ']',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#default_value'])) {
      $element['#default_value'] = [
        'pass1' => $element['#default_value'],
        'pass2' => $element['#default_value'],
      ];
    }
  }

  /**
   * Webform API callback. Convert password confirm array to single value.
   */
  public static function validate(array &$element, FormStateInterface $form_state) {
    $name = $element['#name'];
    $value = $form_state->getValue($name);
    $form_state->setValue($name, $value['pass1']);
  }

}
