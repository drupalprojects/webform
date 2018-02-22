<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "webform_image_select_images" configuration entities.
 */
class WebformImageSelectImagesStorage extends ConfigEntityStorage implements WebformImageSelectImagesStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $webform_images = $this->loadMultiple();
    $categories = [];
    foreach ($webform_images as $webform_image) {
      if ($category = $webform_image->get('category')) {
        $categories[$category] = $category;
      }
    }
    ksort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getImages() {
    $webform_images = $this->loadMultiple();
    @uasort($webform_images, [$this->entityType->getClass(), 'sort']);

    $uncategorized_images = [];
    $categorized_images = [];
    foreach ($webform_images as $id => $webform_image) {
      if ($category = $webform_image->get('category')) {
        $categorized_images[$category][$id] = $webform_image->label();
      }
      else {
        $uncategorized_images[$id] = $webform_image->label();
      }
    }
    return $uncategorized_images + $categorized_images;
  }

}
