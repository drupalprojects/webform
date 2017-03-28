<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission test log handler.
 *
 * @WebformHandler(
 *   id = "log",
 *   label = @Translation("Log"),
 *   category = @Translation("Logging"),
 *   description = @Translation("Logs when a draft or submission is created, converted, and updated."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_IGNORED,
 * )
 */
class LogWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $t_args = [
      '@title' => $webform_submission->label(),
    ];

    switch ($webform_submission->getState()) {
      case WebformSubmissionInterface::STATE_DRAFT:
        if ($update) {
          $operation = 'draft updated';
          $message = $this->t('@title draft updated.', $t_args);
        }
        else {
          $operation = 'draft created';
          $message = $this->t('@title draft created.', $t_args);
        }
        break;

      case WebformSubmissionInterface::STATE_COMPLETED:
        if ($update) {
          $operation = 'submission completed';
          $message = $this->t('@title completed using saved draft.', $t_args);
        }
        else {
          $operation = 'submission created';
          $message = $this->t('@title created.', $t_args);
        }
        break;

      case WebformSubmissionInterface::STATE_CONVERTED:
        $operation = 'submission converted';
        $message = $this->t('@title converted from anonymous to @user.', $t_args + ['@user' => $webform_submission->getOwner()->label()]);
        break;

      case WebformSubmissionInterface::STATE_UPDATED:
        $operation = 'submission updated';
        $message = $this->t('@title updated.', $t_args);
        break;

      default:
        throw new \Exception('Unexpected webform submission state');
        break;
    }

    $this->log($webform_submission, $operation, $message);
  }

  /**
   * Log a webform handler's submission operation.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $operation
   *   The operation to be logged.
   * @param string $message
   *   The message to be logged.
   * @param array $data
   *   The data to be saved with log record.
   */
  protected function log(WebformSubmissionInterface $webform_submission, $operation, $message, array $data = []) {
    $this->submissionStorage->log($webform_submission, [
      'handler_id' => $this->getHandlerId(),
      'operation' => $operation,
      'message' => $message,
      'data' => $data,
    ]);
  }

}
