<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\WebformSourceEntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the access control handler for the webform entity type.
 *
 * @see \Drupal\webform\Entity\Webform.
 */
class WebformEntityAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Webform source entity plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformSourceEntityManagerInterface
   */
  protected $webformSourceEntityManager;

  /**
   * WebformEntityAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\Plugin\WebformSourceEntityManagerInterface $webform_source_entity_manager
   *   Webform source entity plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, WebformSourceEntityManagerInterface $webform_source_entity_manager) {
    parent::__construct($entity_type);

    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->webformSourceEntityManager = $webform_source_entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.webform.source_entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission('create webform')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    else {
      return parent::checkCreateAccess($account, $context, $entity_bundle);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform\WebformInterface $entity */
    // Check 'view' using 'create' custom webform submission access rules.
    // Viewing a webform is the same as creating a webform submission.
    if ($operation == 'view') {
      return AccessResult::allowed();
    }

    $uid = $entity->getOwnerId();
    $is_owner = ($account->isAuthenticated() && $account->id() == $uid);
    // Check if 'update' or 'delete' of 'own' or 'any' webform is allowed.
    if ($account->isAuthenticated()) {
      $has_administer = $entity->checkAccessRules('administer', $account);
      switch ($operation) {
        case 'update':
          if ($has_administer || $account->hasPermission('edit any webform') || ($account->hasPermission('edit own webform') && $is_owner)) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

        case 'duplicate':
          if ($has_administer || $account->hasPermission('create webform') && ($entity->isTemplate() || ($account->hasPermission('edit any webform') || ($account->hasPermission('edit own webform') && $is_owner)))) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;

        case 'delete':
          if ($has_administer || $account->hasPermission('delete any webform') || ($account->hasPermission('delete own webform') && $is_owner)) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
          break;
      }
    }

    // Check submission_* operation.
    if (strpos($operation, 'submission_') === 0) {
      // Allow users with 'view any webform submission' or
      // 'administer webform submission' to view all submissions.
      if ($operation == 'submission_view_any' && ($account->hasPermission('view any webform submission') || $account->hasPermission('administer webform submission'))) {
        return AccessResult::allowed();
      }

      // Allow users with 'view own webform submission' to view own submissions.
      if ($account->hasPermission('view own webform submission') && $is_owner) {
        return AccessResult::allowed();
      }

      // Allow users with 'view own webform submission' to view own submissions.
      if ($operation == 'submission_view_own' && $account->hasPermission('view own webform submission')) {
        return AccessResult::allowed();
      }

      // Allow (secure) token to bypass submission page and create access controls.
      if (in_array($operation, ['submission_page', 'submission_create'])) {
        $token = $this->requestStack->getCurrentRequest()->query->get('token');
        if ($token && $entity->isOpen()) {
          /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
          $submission_storage = $this->entityTypeManager->getStorage('webform_submission');

          $source_entity = $this->webformSourceEntityManager->getSourceEntity(['webform']);
          if ($submission_storage->loadFromToken($token, $entity, $source_entity)) {
            return AccessResult::allowed();
          }
        }
      }

      // Completely block access to a template if the user can't create new
      // Webforms.
      if ($operation == 'submission_page' && $entity->isTemplate() && !$entity->access('create')) {
        return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
      }

      // Check custom webform submission access rules.
      if ($this->checkAccess($entity, 'update', $account)->isAllowed() || $entity->checkAccessRules(str_replace('submission_', '', $operation), $account)) {
        return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
      }
    }

    $access_result = parent::checkAccess($entity, $operation, $account);
    // Make sure the webform is added as a cache dependency.
    $access_result->addCacheableDependency($entity);
    return $access_result;
  }

}
