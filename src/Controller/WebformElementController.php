<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for webform element.
 */
class WebformElementController extends ControllerBase {

  /**
   * Returns response for the element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $key
   *   Webform element key.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, WebformInterface $webform, $key) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';
    if ($q == '') {
      return new JsonResponse([]);
    }

    // Get the initialized webform element.
    $element = $webform->getElement($key);
    if (!$element) {
      return new JsonResponse([]);
    }

    // Set default autocomplete properties.
    $element += [
      '#autocomplete_existing' => FALSE,
      '#autocomplete_options' => [],
      '#autocomplete_match' => 3,
      '#autocomplete_limit' => 10,
      '#autocomplete_match_operator' => 'CONTAINS',
    ];

    // Check minimum number of characters.
    if (Unicode::strlen($q) < (int) $element['#autocomplete_match']) {
      return new JsonResponse([]);
    }

    if (!empty($element['#autocomplete_existing'])) {
      $matches = $this->getMatchesFromExistingValues($q, $webform->id(), $key, $element['#autocomplete_match_operator'], $element['#autocomplete_limit']);
      return new JsonResponse($matches);
    }
    elseif (!empty($element['#autocomplete_options'])) {
      // Get the element's webform options.
      $element['#options'] = $element['#autocomplete_options'];
      $options = WebformOptions::getElementOptions($element);

      $matches = $this->getMatchesFromOptions($q, $options, $element['#autocomplete_match_operator'], $element['#autocomplete_limit']);
      return new JsonResponse($matches);
    }
    else {
      return new JsonResponse([]);
    }
  }

  /**
   * Get matches from existing submission values.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param string $webform_id
   *   The webform id.
   * @param string $key
   *   The element's key.
   * @param string $operator
   *   Match operator either CONTAINS or STARTS_WITH.
   * @param int $limit
   *   Limit number of matches.
   *
   * @return array
   *   An array of matches.
   */
  protected function getMatchesFromExistingValues($q, $webform_id, $key, $operator = 'CONTAINS', $limit = 10) {
    // Query webform submission for existing values.
    $query = Database::getConnection()->select('webform_submission_data')
      ->fields('webform_submission_data', ['value'])
      ->condition('webform_id', $webform_id)
      ->condition('name', $key)
      ->condition('value', ($operator == 'START_WITH') ? "$q%" : "%$q%", 'LIKE')
      ->orderBy('value');
    if ($limit) {
      $query->range(0, $limit);
    }

    // Convert query results values to matches array.
    $values = $query->execute()->fetchCol();
    $matches = [];
    foreach ($values as $value) {
      $matches[] = ['value' => $value, 'label' => $value];
    }
    return $matches;
  }

  /**
   * Get matches from options.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param array $options
   *   An associative array of webform options.
   * @param string $operator
   *   Match operator either CONTAINS or STARTS_WITH.
   * @param int $limit
   *   Limit number of matches.
   *
   * @return array
   *   An array of matches sorted by label.
   */
  protected function getMatchesFromOptions($q, array $options, $operator = 'CONTAINS', $limit = 10) {
    // Make sure options are populated.
    if (empty($options)) {
      return [];
    }

    $matches = [];

    // Filter and convert options to autocomplete matches.
    $this->getMatchesFromOptionsRecursive($q, $options, $matches, $operator);

    // Sort matches.
    ksort($matches);

    // Apply match limit.
    if ($limit) {
      $matches = array_slice($matches, 0, $limit);
    }

    return array_values($matches);
  }

  /**
   * Get matches from options recursive.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param array $options
   *   An associative array of webform options.
   * @param array $matches
   *   An associative array of autocomplete matches.
   * @param string $operator
   *   Match operator either CONTAINS or STARTS_WITH.
   */
  protected function getMatchesFromOptionsRecursive($q, array $options, array &$matches, $operator = 'CONTAINS') {
    foreach ($options as $value => $label) {
      if (is_array($label)) {
        $this->getMatchesFromOptionsRecursive($q, $label, $matches, $operator);
        continue;
      }

      // Cast TranslatableMarkup to string.
      $label = (string) $label;

      if ($operator == 'STARTS_WITH' && stripos($label, $q) === 0) {
        $matches[$label] = [
          'value' => $value,
          'label' => $label,
        ];
      }
      // Default to CONTAINS even when operator is empty.
      elseif (stripos($label, $q) !== FALSE) {
        $matches[$label] = [
          'value' => $value,
          'label' => $label,
        ];
      }

    }
  }

}
