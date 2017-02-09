<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Drupal\webform\WebformHandlerMessageInterface;
use Drupal\webform\WebformInterface;
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
    if ($webform->access('submission_update_any', $account)) {
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
    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($entity);
    return AccessResult::allowedIf($entity->access('update', $account) && $webform_field_name && $entity->$webform_field_name->entity);
  }

  /**
   * Check whether the webform has wizard pages.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\webform\WebformSubmissionForm::buildForm
   * @see \Drupal\webform\Entity\Webform::getPages
   */
  public static function checkWebformWizardPagesAccess(WebformInterface $webform) {
    $elements = $webform->getElementsInitialized();
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] == 'webform_wizard_page') {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
