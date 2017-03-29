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
    $other_group = (string) $this->t('Other');

    $webform_options = $this->loadMultiple();
    @uasort($webform_options, [$this->entityType->getClass(), 'sort']);
    $options = [];
    foreach ($webform_options as $id => $webform_option) {
      $options[$webform_option->get('category') ?: $other_group][$id] = $webform_option->label();
    }

    // Move 'Other' options last.
    if (isset($options[$other_group])) {
      $other_options = $options[$other_group];
      unset($options[$other_group]);
      $options[$other_group] = $other_options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getLikerts() {
    $webform_options = $this->loadMultiple();
    @uasort($webform_options, [$this->entityType->getClass(), 'sort']);
    foreach ($webform_options as $id => $webform_option) {
      if (strpos($id, 'likert_') === 0) {
        $options[$id] = str_replace(t('Likert') . ': ', '', $webform_option->label());
      }
    }
    return $options;
  }

}
