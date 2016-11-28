<?php

namespace Drupal\webform;

/**
 * Defines the interface for webform handlers that send messages.
 *
 * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler
 */
interface WebformHandlerMessageInterface extends WebformHandlerInterface {

  /**
   * Get a fully populated email for a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An array containing message parameters.
   */
  public function getMessage(WebformSubmissionInterface $webform_submission);

  /**
   * Sends and logs a webform submission message.
   *
   * @param array $message
   *   An array of message parameters.
   */
  public function sendMessage(array $message);

  /**
   * Build resend message webform.
   *
   * @param array $message
   *   An array of message parameters.
   *
   * @return array
   *   A webform to edit a message.
   */
  public function resendMessageForm(array $message);

  /**
   * Build message summary.
   *
   * @param array $message
   *   An array of message parameters.
   *
   * @return array
   *   A renderable array representing a message summary.
   */
  public function getMessageSummary(array $message);

}
