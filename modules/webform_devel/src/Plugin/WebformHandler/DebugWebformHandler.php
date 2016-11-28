<?php

namespace Drupal\webform_devel\Plugin\WebformHandler;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission debug handler.
 *
 * IMPORTANT: This handler is exactly the same as the one in the
 * webform_test.module. It does not really matter which one is loaded.
 *
 * @WebformHandler(
 *   id = "debug",
 *   label = @Translation("Debug"),
 *   category = @Translation("Development"),
 *   description = @Translation("Debug webform submission."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class DebugWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $build = ['#markup' => 'Submitted values are:<pre>' . Yaml::encode($webform_submission->getData()) . '</pre>'];
    drupal_set_message(\Drupal::service('renderer')->render($build), 'warning');
  }

}
