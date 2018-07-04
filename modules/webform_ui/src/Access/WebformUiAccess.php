<?php

namespace Drupal\webform_ui\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformOptionsInterface;

/**
 * Defines the custom access control handler for the webform UI.
 */
class WebformUiAccess {

  /**
   * Check that webform source can be updated by a user.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformSourceAccess(WebformInterface $webform, AccountInterface $account) {
    return AccessResult::allowedIf($webform->access('update', $account) && $account->hasPermission('edit webform source'));
  }

  /**
   * Check that webform option source can be updated by a user.
   *
   * @param \Drupal\webform\WebformOptionsInterface $webform_options
   *   A webform options entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformOptionSourceAccess(WebformOptionsInterface $webform_options, AccountInterface $account) {
    return AccessResult::allowedIf($webform_options->access('update', $account) && $account->hasPermission('edit webform source'));
  }

  /**
   * Check that webform can be updated by a user.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformEditAccess(WebformInterface $webform, AccountInterface $account) {
    return AccessResult::allowedIf($webform->access('update', $account));
  }

  /**
   * Check that webform element type can be added by a user.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $type
   *   An element type.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformElementAccess(WebformInterface $webform, $type, AccountInterface $account) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_definitions = $element_manager->getDefinitions();
    $element_definitions = $element_manager->removeExcludeDefinitions($element_definitions);
    $has_update = $webform->access('update', $account);
    return AccessResult::allowedIf($has_update && isset($element_definitions[$type]))
      ->addCacheTags(['config:webform.settings'])
      ->addCacheableDependency($webform);
  }

}
