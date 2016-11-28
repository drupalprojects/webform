<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformHandlerMessageInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the custom access control handler for the webform entities.
 */
class WebformAccess {

  /**
   * Check whether the user has 'administer webform' or 'administer webform submission' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAdminAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer webform') || $account->hasPermission('administer webform submission'));
  }

  /**
   * Check whether the user can view submissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkSubmissionAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer webform') || $account->hasPermission('administer webform submission') || $account->hasPermission('view any webform submission'));
  }

  /**
   * Check whether the user has 'administer' or 'overview' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkOverviewAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer webform') || $account->hasPermission('administer webform submission') || $account->hasPermission('access webform overview'));
  }

  /**
   * Check that webform submission has email and the user can update any webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkEmailAccess(WebformSubmissionInterface $webform_submission, AccountInterface $account) {
    $webform = $webform_submission->getWebform();
    if ($webform->access('submission_update_any')) {
      $handlers = $webform->getHandlers();
      foreach ($handlers as $handler) {
        if ($handler instanceof WebformHandlerMessageInterface) {
          return AccessResult::allowed();
        }
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * Check whether the user can access an entity's webform results.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkEntityResultsAccess(EntityInterface $entity, AccountInterface $account) {
    return AccessResult::allowedIf($entity->access('update') && $entity->hasField('webform') && $entity->webform->entity);
  }

}
