<?php

/**
 * @file
 * Generate test elements with Flexbox wrappers.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\webform\WebformInterface;


module_load_include('inc', 'webform_test', 'includes/webform_test.test_element_format');

/**
 * Generate test element formats with multiple values.
 *
 * @return array
 *   An array containing test elements formats.
 */
function webform_test_test_element_format_multiple(WebformInterface $webform) {
  return webform_test_test_element_format($webform, TRUE);
  $multiple_elements = [
    'select',
    'checkboxes',
    'webform_select_other',
    'webform_checkboxes_other',
  ];

  /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
  $submission_generate = \Drupal::service('webform_submission.generate');

  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $elements = $element_manager->getInstances();
  $data = [];
  foreach ($multiple_elements as $element_type) {
    $webform_element = $elements[$element_type];
    $single_formats = $webform_element->getItemFormats();
    $multiple_formats = $webform_element->getItemsFormats();
    $element = _webform_test_get_example_element($element_type, ['issues' => FALSE]);
    if (!$element || !$webform_element->isInput($element)) {
      continue;
    }

    // Set element #multiple or #tags property.
    $multiple_properties = ['multiple', 'tags'];
    foreach ($multiple_properties  as $multiple_property) {
      if ($webform_element->hasProperty($multiple_property)) {
        $element["#$multiple_property"] = TRUE;
      }
    }

    // Set default (test) value.
    $element['#default_value'] = $submission_generate->getTestValue($webform, $element_type, $element, ['random' => FALSE]);

    $data[$element_type] = [
      '#type' => 'details',
      '#title' => $element['#title'],
      '#open' => TRUE,
    ];
    foreach ($single_formats as $single_format_name => $single_format_title) {
      foreach ($multiple_formats as $multiple_format_name => $multiple_format_title) {
        $format_element_type = $element_type . '_' . $single_format_name . '_' . $multiple_format_name;

        $format_element = $element;
        $format_element['#title'] = $element['#title'] . ' (' . $single_format_title . ' - ' . $multiple_format_title . ')';
        $format_element['#format'] = $single_format_name;
        $format_element['#format_multiple'] = $multiple_format_name;
        $data[$element_type][$format_element_type] = $format_element;
      }

    }
  }

  return $data;
}

