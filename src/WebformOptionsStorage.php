<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "webform_options" configuration entities.
 */
class WebformOptionsStorage extends ConfigEntityStorage implements WebformOptionsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $webform_options = $this->loadMultiple();
    $categories = [];
    foreach ($webform_options as $webform_option) {
      if ($category = $webform_option->get('category')) {
        $categories[$category] = $category;
      }
    }
    ksort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $webform_options = $this->loadMultiple();
    @uasort($webform_options, array($this->entityType->getClass(), 'sort'));
    foreach ($webform_options as $id => $webform_option) {
      $options[$webform_option->get('category')][$id] = $webform_option->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getLikerts() {
    $webform_options = $this->loadMultiple();
    @uasort($webform_options, array($this->entityType->getClass(), 'sort'));
    foreach ($webform_options as $id => $webform_option) {
      if (strpos($id, 'likert_') === 0) {
        $options[$id] = str_replace(t('Likert') . ': ', '', $webform_option->label());
      }
    }
    return $options;
  }

}
