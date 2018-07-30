<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'table' trait.
 */
trait WebformTableTrait {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    if ($this->hasMultipleValues($element)) {
      $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    }

    parent::prepare($element, $webform_submission);

    // Add missing element class.
    $element['#attributes']['class'][] = str_replace('_', '-', $element['#type']);

    // Add one column header is not #header is specified.
    if (!isset($element['#header'])) {
      $element['#header'] = [
        (isset($element['#title']) ? $element['#title'] : ''),
      ];
    }

    // Convert associative array of options into one column row.
    if (isset($element['#options'])) {
      foreach ($element['#options'] as $options_key => $options_value) {
        if (is_string($options_value)) {
          $element['#options'][$options_key] = [
            ['value' => $options_value],
          ];
        }
      }
    }

    $element['#attached']['library'][] = 'webform/webform.element.' . $element['#type'];

    // Set table select element's #processr callback so that we can
    // add visually hidden #title to the checkboxes/radios.
    // Note: The Table select sort (webform_table_select_sort) element
    // adds visually hidden #title to all checkboxes.
    // @see \Drupal\webform\Element\WebformTableSelectSort::processWebformTableSelectSort
    if ($this->getPluginId() === 'tableselect') {
      $this->setElementDefaultCallback($element, 'process');
      $element['#process'][] = [get_class($this), 'processTableSelectOptions'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#default_value']) && is_array($element['#default_value'])) {
      $element['#default_value'] = array_combine($element['#default_value'], $element['#default_value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['options']['js_select'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all'),
      '#description' => $this->t('If checked, a select all checkbox will be added to the header.'),
      '#return_value' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTableSelectElementSelectorOptions(array $element, $input_selector = '') {
    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];
    $type = ($this->hasMultipleValues($element) ? $this->t('Checkbox') : $this->t('Radio'));

    $selectors = [];
    foreach ($element['#options'] as $value => $text) {
      if (is_array($text)) {
        $text = $value;
      }
      $selectors[":input[name=\"{$name}[{$value}]$input_selector\"]"] = $text . ' [' . $type . ']';
    }
    return [$title => $selectors];
  }

  /**
   * Process table options and tdd #title to the table options.
   *
   * @param array $element
   *   An associative array containing the properties and children of
   *   the tableselect element
   *
   * @return array
   *   The processed element.
   *
   * @see \Drupal\Core\Render\Element\Tableselect::processTableselect
   */
  public static function processTableSelectOptions(array $element) {
    foreach ($element['#options'] as $key => $choice) {
      if (isset($element[$key]) && empty($element[$key]['#title'])) {
        if ($title = static::getTableSelectOptionTitle($choice)) {
          $element[$key]['#title'] = $title;
          $element[$key]['#title_display'] = 'invisible';
        }
      }
    }
    return $element;
  }

  /**
   * Set process table select element callbacks.
   *
   * @param array $element
   *   An associative array containing the properties and children of
   *   the table select element
   *
   * @see \Drupal\Core\Render\Element\Tableselect::processTableselect
   */
  public static function setProcessTableSelectCallback(array &$element) {
    $class = get_called_class();
    $element['#process'] = [
      ['\Drupal\Core\Render\Element\Tableselect', 'processTableselect'],
      [$class , 'processTableSelectOptions'],
    ];
  }

  /**
   * Get table selection option title/text.
   *
   * Issue #2719453: Tableselect single radio button missing #title attribute
   * and is not accessible
   *
   * @param array $option
   *   A table select option.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface|null
   *   Table selection option title/text.
   *
   * @see https://www.drupal.org/project/drupal/issues/2719453
   */
  public static function getTableSelectOptionTitle(array $option) {
    if (is_array($option) && WebformArrayHelper::isAssociative($option)) {
      // Get first value from custom options.
      $title = reset($option);
      if (is_array($title)) {
        $title = \Drupal::service('renderer')->render($title);
      }
      return $title;
    }
    elseif (is_array($option) && !empty($option[0]['value'])) {
      // Get value from default options.
      // @see \Drupal\webform\Plugin\WebformElement\WebformTableTrait::prepare
      return $option[0]['value'];
    }
    else {
      return NULL;
    }
  }

}
