<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a trait for webform composite form elements.
 *
 * Any form element that is comprised of several distinct parts can use this
 * trait to add support for a composite title or description.
 *
 * The Webform overrides any element that is using the CompositeFormElementTrait
 * and applies the below pre renderer which adds support for
 * #wrapper_attributes and additional some classes.
 *
 * @see \Drupal\Core\Render\Element\CompositeFormElementTrait
 * @see \Drupal\webform\Plugin\WebformElementBase::prepareCompositeFormElement
 */
trait WebformCompositeFormElementTrait {

  /**
   * Adds form element theming to an element if its title or description is set.
   *
   * This is used as a pre render function for checkboxes and radios.
   */
  public static function preRenderWebformCompositeFormElement($element) {
    // Set attributes.
    if (!isset($element['#attributes'])) {
      $element['#attributes'] = [];
    }

    // Apply wrapper attributes to fieldset.
    if (isset($element['#wrapper_attributes'])) {
      $element['#attributes'] = NestedArray::mergeDeep($element['#attributes'], $element['#wrapper_attributes']);
    }

    // Set the element's title attribute to show #title as a tooltip, if needed.
    if (isset($element['#title']) && $element['#title_display'] == 'attribute') {
      $element['#attributes']['title'] = $element['#title'];
      if (!empty($element['#required'])) {
        // Append an indication that this field is required.
        $element['#attributes']['title'] .= ' (' . t('Required') . ')';
      }
    }

    if (isset($element['#title']) || isset($element['#description'])) {
      // @see #type 'fieldgroup'
      $element['#theme_wrappers'][] = 'fieldset';
      if (!isset($element['#attributes']['id'])) {
        $element['#attributes']['id'] = $element['#id'] . '--wrapper';
      }
      $element['#attributes']['class'][] = Html::getClass($element['#type']) . '--wrapper';
      $element['#attributes']['class'][] = 'fieldgroup';
      $element['#attributes']['class'][] = 'form-composite';
    }

    // Add hidden and visible title class to fix composite fieldset
    // top/bottom margins.
    if (isset($element['#title'])) {
      if (!empty($element['#title_display']) && in_array($element['#title_display'], ['invisible', 'attribute'])) {
        $element['#attributes']['class'][] = 'webform-composite-hidden-title';
      }
      else {
        $element['#attributes']['class'][] = 'webform-composite-visible-title';
      }
    }

    // Add composite library.
    $element['#attached']['library'][] = 'webform/webform.composite';

    return $element;
  }

}
