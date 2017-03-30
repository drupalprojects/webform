<?php

namespace Drupal\webform_test_handler\Plugin\WebformHandler;

use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission test log handler.
 *
 * @WebformHandler(
 *   id = "test_log",
 *   label = @Translation("Test log"),
 *   category = @Translation("Testing"),
 *   description = @Translation("Tests webform submission handler logging."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_IGNORED,
 * )
 */
class TestLogWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->invokeLog($webform_submission, $update ? 'updated' : 'inserted', $webform_submission->toArray(TRUE));
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $operation
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function invokeLog(WebformSubmissionInterface $webform_submission, $operation) {
    drupal_set_message($this->t('Logged: @operation', ['@operation' => $operation]));
    $this->log($webform_submission, $operation, $this->t('Logged: @operation', ['@operation' => $operation]));
  }

}
