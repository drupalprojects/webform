<?php

namespace Drupal\webform;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "webform" configuration entities.
 */
class WebformEntityStorage extends ConfigEntityStorage {

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a WebformEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, Connection $database) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Config entities are not cached and there is no easy way to enable static
   * caching. See: Issue #1885830: Enable static caching for config entities.
   *
   * Overriding just EntityStorageBase::load is much simpler
   * than completely re-writting EntityStorageBase::loadMultiple. It is also
   * worth noting that EntityStorageBase::resetCache() does purge all cached
   * webform config entities.
   *
   * Webforms need to be cached when they are being loading via
   * a webform submission, which requires a webform's elements and meta data to be
   * initialized via Webform::initElements().
   *
   * @see https://www.drupal.org/node/1885830
   * @see \Drupal\Core\Entity\EntityStorageBase::resetCache()
   * @see \Drupal\webform\Entity\Webform::initElements()
   */
  public function load($id) {
    if (isset($this->entities[$id])) {
      return $this->entities[$id];
    }

    $this->entities[$id] = parent::load($id);
    return $this->entities[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    parent::delete($entities);
    if ($entities) {
      return;
    }

    // Delete all webform submission log entries.
    $webform_ids = [];
    foreach ($entities as $entity) {
      $webform_ids[$entity->id()] = $entity;
    }
    $this->database->delete('webform_submission_log')
      ->condition('webform_ids', $webform_ids, 'IN')
      ->execute();
  }
}
