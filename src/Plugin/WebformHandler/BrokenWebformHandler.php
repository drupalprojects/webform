<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\webform\WebformHandlerBase;

/**
 * Defines a fallback plugin for missing webform handler plugins.
 *
 * @WebformHandler(
 *   id = "broken",
 *   label = @Translation("Broken/Missing"),
 *   category = @Translation("Broken"),
 *   description = @Translation("Broken/missing webform handler plugin."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_IGNORED,
 * )
 */
class BrokenWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return FALSE;
  }

}
