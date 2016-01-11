<?php

/**
 * @file
 * Contains \Drupal\webform\Access\WebformNodeAccessCheck.
 */

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on webform enabled content type.
 */
class WebformNodeAccessCheck implements AccessInterface {
  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new WebformNodeAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->nodeStorage = $entity_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, NodeInterface $node = NULL) {
    if ($node->bundle() && \Drupal::config('webform.settings')->get('node_' . $node->bundle())) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
