<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Element\WebformComputedToken as WebformComputedTokenElement;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_computed_token' element.
 *
 * @WebformElement(
 *   id = "webform_computed_token",
 *   label = @Translation("Computed token"),
 *   description = @Translation("Provides an item to display computed webform submission values using tokens."),
 *   category = @Translation("Computed"),
 * )
 */
class WebformComputedToken extends WebformComputedBase {

}
