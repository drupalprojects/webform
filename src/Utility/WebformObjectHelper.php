<?php

namespace Drupal\webform\Utility;

/**
 * Provides helper to operate on objects.
 */
class WebformObjectHelper {

  /**
   * Sort object by properties.
   *
   * @param object $object
   *   An object.
   *
   * @return object $object
   *   An object.
   */
  public static function sortByProperty($object) {
    $array = (array) $object;
    ksort($array);
    return (object) $array;
  }

}
