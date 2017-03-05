<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Provides a webform element for a term select menu.
 *
 * @FormElement("webform_term_select")
 */
class WebformTermSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#vocabulary' => '',
      '#tree_delimiter' => '-',
      '#breadcrumb' => FALSE,
      '#breadcrumb_delimiter' => ' › ',
      '#tree_delimiter' => '-',
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    self::setOptions($element);

    $element = parent::processSelect($element, $form_state, $complete_form);

    // Must convert this element['#type'] to a 'select' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'select';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function setOptions(array &$element) {
    if (!empty($element['#options'])) {
      return;
    }

    if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      return [];
    }

    if (empty($element['#vocabulary'])) {
      $element['#options'] = [];
      return;
    }

    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tree = $taxonomy_storage->loadTree($element['#vocabulary']);

    $options = [];
    if (!empty($element['#breadcrumb'])) {
      // Build term breadcrumbs.
      $element += ['#breadcrumb_delimiter' => ' › '];
      $breadcrumb = [];
      foreach ($tree as $item) {
        $breadcrumb[$item->depth] = $item->name;
        $breadcrumb = array_slice($breadcrumb, 0, $item->depth + 1);
        $options[$item->tid] = implode($element['#breadcrumb_delimiter'], $breadcrumb);
      }
    }
    else {
      $element += ['#tree_delimiter' => '-'];
      // Build hierarchical term tree.
      foreach ($tree as $item) {
        $options[$item->tid] = str_repeat($element['#tree_delimiter'], $item->depth) . $item->name;
      }
    }
    $element['#options'] = $options;
  }
}
