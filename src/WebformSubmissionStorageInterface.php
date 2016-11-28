<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for webform submission classes.
 */
interface WebformSubmissionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Return status for saving of YAML forb submission when saving results is disabled.
   */
  const SAVED_DISABLED = 0;

  /**
   * Get webform submission entity field definitions.
   *
   * The helper method is generally used for exporting results.
   *
   * @see \Drupal\webform\Element\WebformExcludedColumns
   * @see \Drupal\webform\Controller\WebformResultsExportController
   *
   * @return array
   *   An associative array of field definition key by field name containing
   *   title, name, and datatype.
   */
  public function getFieldDefinitions();

  /**
   * Returns a webform's max serial number.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int
   *   The next serial number.
   */
  public function getMaxSerial(WebformInterface $webform);

  /**
   * Delete all webform submissions.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) The webform to delete the submissions from.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param int $limit
   *   (optional) Number of submissions to be deleted.
   * @param int $max_sid
   *   (optional) Maximum webform submission id.
   *
   * @return int
   *   The number of webform submissions deleted.
   */
  public function deleteAll(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, $limit = NULL, $max_sid = NULL);

  /**
   * Get webform submission draft.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   */
  public function loadDraft(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get the total number of submissions.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) A webform. If set the total number of submissions for the
   *   Webform will be returned.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   *
   * @return int
   *   Total number of submissions.
   */
  public function getTotal(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get the maximum sid.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) A webform. If set the total number of submissions for the
   *   Webform will be returned.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   *
   * @return int
   *   Total number of submissions.
   */
  public function getMaxSubmissionId(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get a webform's first submission.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform's first submission.
   */
  public function getFirstSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get a webform's last submission.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform's last submission.
   */
  public function getLastSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get a webform submission's previous sibling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission's previous sibling.
   */
  public function getPreviousSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get a webform submission's next sibling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission's next sibling.
   */
  public function getNextSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Get webform submission source entity types.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An array of entity types that the webform has been submitted from.
   */
  public function getSourceEntityTypes(WebformInterface $webform);

  /**
   * Get customized submission columns used to display custom table.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getCustomColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get default submission columns used to display results.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getDefaultColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get submission columns used to display results table.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get customize setting.
   *
   * @param string $name
   *   Custom settings name.
   * @param mixed $default
   *   Custom settings default value.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   *
   * @return mixed
   *   Custom setting.
   */
  public function getCustomSetting($name, $default, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL);

  /**
   * Invoke a webform submission's webform's handlers method.
   *
   * @param string $method
   *   The webform handler method to be invoked.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   */
  public function invokeWebformHandlers($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL);

  /**
   * Invoke a webform submission's webform's elements method.
   *
   * @param string $method
   *   The webform element method to be invoked.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   */
  public function invokeWebformElements($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL);

}
