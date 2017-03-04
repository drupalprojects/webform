<?php

namespace Drupal\webform;

/**
 * Defines an interface for libraries classes.
 */
interface WebformLibrariesManagerInterface {

  /**
   * Get third party libraries status for hook_requirements and drush.
   *
   * @return array
   *    An associative array of third party libraries keyed by library name.
   */
  public function requirements();

  /**
   * Get library information.
   *
   * @param string $name
   *   The name of the library.
   *
   * @return array
   *   An associative array containing an library.
   */
  public function getLibrary($name);

  /**
   * Get libraries.
   *
   * @return array
   *   An associative array of libraries.
   */
  public function getLibraries();

}
