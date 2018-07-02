<?php

namespace Drupal\webform\Utility;

/**
 * Helper class webform accessiblity methods.
 */
class WebformAccessibilityHelper {

  /**
   * Visually hide text using .visually-hidden class.
   *
   * @param $title
   *   Text that should be visually hidden
   *
   * @return array
   *   A renderable array with the text wrapped in
   *   <span class="visually-hidden">
   */
  public static function buildVisuallyHidden($title) {
    return [
      '#markup' => $title,
      '#prefix' => '<span class="visually-hidden">',
      '#suffix' => '</span>',
    ];
  }

}

