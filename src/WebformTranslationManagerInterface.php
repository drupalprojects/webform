<?php

namespace Drupal\webform;

/**
 * Defines an interface for webform element translation classes.
 */
interface WebformTranslationManagerInterface {

  /**
   * Get webform elements for specific language.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $langcode
   *   The language code for the webform elements.
   * @param bool $reset
   *   (optional) Whether to reset the translated config cache. Defaults to
   *   FALSE.
   *
   * @return array
   *   A webform's translated elements.
   */
  public function getConfigElements(WebformInterface $webform, $langcode, $reset = FALSE);

  /**
   * Get base webform elements from the site's default language.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   Base webform elements as a flattened associative array.
   */
  public function getBaseElements(WebformInterface $webform);

  /**
   * Get flattened associative array of translated element properties.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A associative array of translated element properties.
   */
  public function getSourceElements(WebformInterface $webform);

  /**
   * Get flattened associative array of translated element properties.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A associative array of translated element properties.
   */
  public function getTranslationElements(WebformInterface $webform, $langcode);

}
