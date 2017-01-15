<?php

/**
 * @file
 * Generate test elements with Flexbox wrappers.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\webform\WebformInterface;
/**
 * Generate test elements formats.
 *
 * @return array
 *   An array containing test elements formats.
 */
function webform_test_test_element_formats(WebformInterface $webform) {
  // Elements to be ignored.
  $skipped_elements = [
    'hidden',
    'language_select',
    'managed_file',
    'text_format_raw',
    'text_format_value',
    'webform_audio_file',
    'webform_document_file',
    'webform_element',
    'webform_image_file',
    'webform_video_file',
    'webform_signature_raw',
    'webform_test',
  ];

  /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
  $submission_generate = \Drupal::service('webform_submission.generate');

  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $definitions = $element_manager->getDefinitions();
  $definitions = $element_manager->getSortedDefinitions($definitions);
  $elements = $element_manager->getInstances();

  $data = [
    'basic_elements' => [],
    'advanced_elements' => [],
  ];
  foreach ($definitions as $definition) {
    $element_type = $definition['id'];
    if (in_array($element_type, $skipped_elements)) {
      continue;
    }

    $webform_element = $elements[$element_type];
    $formats = $webform_element->getFormats();
    $element = _webform_test_get_example_element($element_type, ['issues' => FALSE]);
    if (!$element || !$webform_element->isInput($element)) {
      continue;
    }

    // Set default (test) value.
    $element['#default_value'] = $submission_generate->getTestValue($webform, $element_type, $element, ['random' => FALSE]);

    // Set element category.
    $category_name = (string) $webform_element->getPluginDefinition()['category'] ?: 'Other elements';
    $category_id = preg_replace('/[^a-zA-Z0-9]+/', '_', Unicode::strtolower($category_name));
    if (empty($data[$category_id])) {
      $data[$category_id] = [
        '#type' => 'details',
        '#title' => $category_name,
        '#open' => TRUE,
      ];
    }
    $data[$category_id][$element_type] = [
      '#type' => 'details',
      '#title' => $element['#title'],
    ];
    foreach ($formats as $format_name => $format_title) {
      // Skip unsupported element type formats.
      $format_element_type = $element_type . '_' . $format_name;
      if (in_array($format_element_type, $skipped_elements)) {
        continue;
      }

      $format_element = $element;
      $format_element['#title'] = $element['#title'] . ' (' . $format_title . ')';
      $format_element['#format'] = $format_name;
      $data[$category_id][$element_type][$format_element_type] = $format_element;
    }
  }

  // Move other elements last.
  if (isset($data['other_elements'])) {
    $other_elements = $data['other_elements'];
    unset($data['other_elements']);
    $data['other_elements'] = $other_elements;
  }

  return $data;
}

