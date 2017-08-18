<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;

/**
 * Defines the custom access control handler for the webform source entities.
 */
class WebformSourceEntityAccess {

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
}

