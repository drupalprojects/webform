<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the webform submission entity type.
 *
 * @see \Drupal\webform\Entity\WebformSubmission.
 */
class WebformSubmissionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow users with 'view any webform submission' to view all submissions.
    if ($operation == 'view' && $account->hasPermission('view any webform submission')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $webform = $entity->getWebform();
    if ($webform->access('update') || $webform->checkAccessRules($operation, $account, $entity)) {
      return AccessResult::allowed();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
