<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'text' (field) class.
 */
abstract class TextBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'readonly' => FALSE,
      'size' => '',
      'minlength' => '',
      'maxlength' => '',
      'placeholder' => '',
      'autocomplete' => 'on',
      'pattern' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['counter_message']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Counter.
    if (!empty($element['#counter_type'])
      && (!empty($element['#counter_minimum']) || !empty($element['#counter_maximum']))
      && $this->librariesManager->isIncluded('jquery.textcounter')) {

      // Apply character min/max to min/max length.
      if ($element['#counter_type'] == 'character') {
        if (!empty($element['#counter_minimum'])) {
          $element['#minlength'] = $element['#counter_minimum'];
        }
        if (!empty($element['#counter_maximum'])) {
          $element['#maxlength'] = $element['#counter_maximum'];
        }
      }

      // Set 'data-counter-*' attributes using '#counter_*' properties.
      $data_attributes = [
        'counter_type',
        'counter_minimum',
        'counter_minimum_message',
        'counter_maximum',
        'counter_maximum_message',
      ];
      foreach ($data_attributes as $data_attribute) {
        if (!empty($element['#' . $data_attribute])) {
          $element['#attributes']['data-' . str_replace('_', '-', $data_attribute)] = $element['#' . $data_attribute];
        }
      }

      $element['#attributes']['class'][] = 'js-webform-counter';
      $element['#attributes']['class'][] = 'webform-counter';
      $element['#attached']['library'][] = 'webform/webform.element.counter';

      $element['#element_validate'][] = [get_class($this), 'validateCounter'];
    }

    // Input mask.
    if (!empty($element['#input_mask']) && $this->librariesManager->isIncluded('jquery.inputmask')) {
      // See if the element mask is JSON by looking for 'name':, else assume it
      // is a mask pattern.
      $input_mask = $element['#input_mask'];
      if (preg_match("/^'[^']+'\s*:/", $input_mask)) {
        $element['#attributes']['data-inputmask'] = $input_mask;
      }
      else {
        $element['#attributes']['data-inputmask-mask'] = $input_mask;
      }

      $element['#attributes']['class'][] = 'js-webform-input-mask';
      $element['#attached']['library'][] = 'webform/webform.element.inputmask';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Input mask.
    $form['form']['input_mask'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Input masks'),
      '#description' => $this->t('An <a href=":href">inputmask</a> helps the user with the element by ensuring a predefined format.', [':href' => 'https://github.com/RobinHerbots/jquery.inputmask']),
      '#other__option_label' => $this->t('Custom…'),
      '#other__placeholder' => $this->t('Enter input mask…'),
      '#other__description' => $this->t('(9 = numeric; a = alphabetical; * = alphanumeric)'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        'Basic' => [
          "'alias': 'currency'" => $this->t('Currency - @format', ['@format' => '$ 9.99']),
          "'alias': 'mm/dd/yyyy'" => $this->t('Date - @format', ['@format' => 'mm/dd/yyyy']),
          "'alias': 'decimal'" => $this->t('Decimal - @format', ['@format' => '1.234']),
          "'alias': 'email'" => $this->t('Email - @format', ['@format' => 'example@example.com']),
          "'alias': 'percentage'" => $this->t('Percentage - @format', ['@format' => '99%']),
          '(999) 999-9999' => $this->t('Phone - @format', ['@format' => '(999) 999-9999']),
          '99999[-9999]' => $this->t('Zip code - @format', ['@format' => '99999[-9999]']),
        ],
        'Advanced' => [
          "'alias': 'ip'" => $this->t('IP address - @format', ['@format' => '255.255.255.255']),
          '[9-]AAA-999' => $this->t('License plate - @format', ['@format' => '[9-]AAA-999']),
          "'alias': 'mac'" => $this->t('MAC addresses - @format', ['@format' => '99-99-99-99-99-99']),
          '999-99-9999' => $this->t('SSN - @format', ['@format' => '999-99-9999']),
          "'alias': 'vin'" => 'VIN (Vehicle identification number)',
        ],
      ],
    ];
    if ($this->librariesManager->isExcluded('jquery.inputmask')) {
      $form['form']['input_mask']['#access'] = FALSE;
    }

    // Pattern.
    $form['validation']['pattern'] = [
      '#type' => 'webform_checkbox_value',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('A <a href=":href">regular expression</a> that the element\'s value is checked against.', [':href' => 'http://www.w3schools.com/js/js_regexp.asp']),
      '#value__title' => $this->t('Pattern regular expression'),
    ];

    // Counter.
    $form['validation']['counter_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Count'),
      '#description' => $this->t('Limit entered value to a maximum number of characters or words.'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        'character' => $this->t('Characters'),
        'word' => $this->t('Words'),
      ],
    ];
    $form['validation']['counter_container'] = $this->getFormInlineContainer();
    $form['validation']['counter_container']['#states'] = [
      'invisible' => [
        ':input[name="properties[counter_type]"]' => ['value' => ''],
      ],
    ];
    $form['validation']['counter_container']['counter_minimum'] = [
      '#type' => 'number',
      '#title' => $this->t('Count minimum'),
      '#min' => 1,
      '#states' => [
        'required' => [
          ':input[name="properties[counter_type]"]' => ['!value' => ''],
          ':input[name="properties[counter_maximum]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['validation']['counter_container']['counter_maximum'] = [
      '#type' => 'number',
      '#title' => $this->t('Count maximum'),
      '#min' => 1,
      '#states' => [
        'required' => [
          ':input[name="properties[counter_type]"]' => ['!value' => ''],
          ':input[name="properties[counter_minimum]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['validation']['counter_message_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="properties[counter_type]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['validation']['counter_message_container']['counter_minimum_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count minimum message'),
      '#description' => $this->t('Defaults to: %value', ['%value' => $this->t('%d characters/word(s) entered')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[counter_minimum]"]' => ['!value' => ''],
          ':input[name="properties[counter_maximum]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['validation']['counter_message_container']['counter_maximum_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count maximum message'),
      '#description' => $this->t('Defaults to: %value', ['%value' => $this->t('%d characters/word(s) remaining')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[counter_maximum]"]' => ['!value' => ''],
        ],
      ],
    ];
    if ($this->librariesManager->isExcluded('jquery.textcounter')) {
      $form['validation']['counter_type']['#access'] = FALSE;
      $form['validation']['counter_container']['#access'] = FALSE;
      $form['validation']['counter_message_container']['#access'] = FALSE;
    }

    if (isset($form['form']['maxlength'])) {
      $form['form']['maxlength']['#description'] .= ' ' . $this->t('If character counter is enabled, maxlength will automatically be set to the count maximum.');
      $form['form']['maxlength']['#states'] = [
        'invisible' => [
          ':input[name="properties[counter_type]"]' => ['value' => 'character'],
        ],
      ];
    }

    return $form;
  }

  /**
   * Form API callback. Validate (word/character) counter.
   */
  public static function validateCounter(array &$element, FormStateInterface $form_state) {
    $name = $element['#name'];
    $value = $form_state->getValue($name);
    if ($value === '') {
      return;
    }

    $type = $element['#counter_type'];
    $max = (!empty($element['#counter_maximum'])) ? $element['#counter_maximum'] : NULL;
    $min = (!empty($element['#counter_minimum'])) ? $element['#counter_minimum'] : NULL;

    // Display error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $t_args = [
      '@type' => ($type == 'character') ? t('characters') : t('words'),
      '@name' => $element['#title'],
      '%max' => $max,
      '%min' => $min,
    ];

    // Get character/word count.
    if ($type === 'character') {
      $length = Unicode::strlen($value);
      $t_args['%length'] = $length;
    }
    // Validate word count.
    elseif ($type === 'word') {
      $length = str_word_count($value);
      $t_args['%length'] = $length;
    }

    // Validate character/word count.
    if ($max && $length > $max) {
      $form_state->setError($element, t('@name cannot be longer than %max @type but is currently %length @type long.', $t_args));
    }
    elseif ($min && $length < $min) {
      $form_state->setError($element, t('@name must be longer than %min @type but is currently %length @type long.', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $properties = $this->getConfigurationFormProperties($form, $form_state);

    // Validate #pattern's regular expression.
    // @see \Drupal\Core\Render\Element\FormElement::validatePattern
    // @see http://stackoverflow.com/questions/4440626/how-can-i-validate-regex
    if (!empty($properties['#pattern'])) {
      set_error_handler('_webform_entity_element_validate_rendering_error_handler');
      if (preg_match('{^(?:' . $properties['#pattern'] . ')$}', NULL) === FALSE) {
        $form_state->setErrorByName('pattern', t('Pattern %pattern is not a valid regular expression.', ['%pattern' => $properties['#pattern']]));
      }
      set_error_handler('_drupal_error_handler');
    }

    // Validate #counter_maximum.
    if (!empty($properties['#counter_type']) && empty($properties['#counter_maximum'])) {
      $form_state->setErrorByName('counter_maximum', t('Counter maximum is required.'));
    }
  }

}
