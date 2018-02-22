<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform_image_select\Entity\WebformImageSelectImages;

/**
 * Defines a class to build a listing of webform image select images entities.
 *
 * @see \Drupal\webform_image_select\Entity\WebformImageSelectImages
 */
class WebformImageSelectImagesListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Display info.
    if ($total = $this->getStorage()->getQuery()->count()->execute()) {
      $build['info'] = [
        '#markup' => $this->formatPlural($total, '@total images', '@total images', ['@total' => $total]),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build += parent::render();

    $build['#attached']['library'][] = 'webform/webform.tooltip';
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('ID');
    $header['category'] = $this->t('Category');
    $header['images'] = [
      'data' => $this->t('Images'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $entity */
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    $row['id'] = $entity->id();
    $row['category'] = $entity->get('category');

    $element = ['#images' => $entity->id()];
    $images = WebformImageSelectImages::getElementImages($element);
    if ($images) {
      $build = [];
      foreach ($images as $key => $image) {
        $title = $image['text'] . ($key != $image ? ' (' . $key . ')' : '');
        $build[] = [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => $image['src'],
            'alt' => $title,
            'title' => $title,
            'class' => ['js-webform-tooltip-link'],
            'style' => 'max-height:60px',
          ],
        ];
      }
      $row['images'] = ['data' => $build];
    }
    else {
      $row['images'] = '';
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 23,
        'url' => Url::fromRoute('entity.webform_image_select_images.duplicate_form', ['webform_image_select_images' => $entity->id()]),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }
    return $operations;
  }

}
