<?php

namespace Drupal\webform_ui;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformOptions;
use Drupal\webform\WebformOptionsForm;

/**
 * Base for controller for webform option UI.
 */
class WebformUiOptionsForm extends WebformOptionsForm {

  /**
   * {@inheritdoc}
   */
  public function editForm(array $form, FormStateInterface $form_state) {
    $form['options'] = [
      '#type' => 'webform_options',
      '#mode' => 'yaml',
      '#title' => $this->t('Options'),
      '#title_display' => 'invisible',
      '#empty_options' => 10,
      '#add_more' => 10,
      '#required' => TRUE,
      '#default_value' => $this->getOptions($form, $form_state),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // @todo Determine why options.options and options.add are being included.
    // Best guess is the webform_options element has not been validated.
    if (isset($values['options']['options'])) {
      $options = (is_array($values['options']['options'])) ? WebformOptions::convertValuesToOptions($values['options']['options']) : [];
    }
    elseif (isset($values['options'])) {
      $options = (is_array($values['options'])) ? $values['options'] : [];
    }
    else {
      $options = [];
    }
    $entity->set('options', Yaml::encode($options));
    unset($values['options']);

    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }

}
