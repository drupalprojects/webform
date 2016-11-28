<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "webform" configuration entities.
 */
class WebformEntityStorage extends ConfigEntityStorage {

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

}
