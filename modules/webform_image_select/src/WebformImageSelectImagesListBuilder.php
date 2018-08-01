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
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;

    // Attachments.
    $build['#attached']['library'][] = 'webform/webform.tooltip';
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getStorage()->getQuery()->count()->execute();
    if (!$total) {
      return [];
    }

    return [
      '#markup' => $this->formatPlural($total, '@total images', '@total images', ['@total' => $total]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['category'] = $this->t('Category');
    $header['images'] = [
      'data' => $this->t('Images'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['used_by'] = [
      'data' => $this->t('Used by'),
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
    $row['category'] = $entity->get('category');
    $row['images'] = $this->buildImages($entity);
    $row['used_by'] = $this->buildUsedBy($entity);
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

  /**
   * Build images for a webform image select images entity.
   *
   * @param \Drupal\webform_image_select\WebformImageSelectImagesInterface $entity
   *   A webform image select images entity.
   *
   * @return array
   *   Images for a webform image select images entity.
   */
  protected function buildImages(WebformImageSelectImagesInterface $entity) {
    $element = ['#images' => $entity->id()];
    $images = WebformImageSelectImages::getElementImages($element);
    if (!$images) {
      return [];
    }

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

    return ['data' => $build];
  }

  /**
   * Build list of webforms that the webform images is used by.
   *
   * @param \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images
   *   A webform image select images entity.
   *
   * @return array
   *   Table data containing list of webforms that the webform images is used by.
   */
  protected function buildUsedBy(WebformImageSelectImagesInterface $webform_images) {
    $links = [];
    $webforms = $this->getStorage()->getUsedByWebforms($webform_images);
    foreach ($webforms as $id => $title) {
      $links[] = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromRoute('entity.webform.canonical', ['webform' => $id]),
        '#suffix' => '</br>',
      ];
    }
    return [
      'nowrap' => TRUE,
      'data' => $links,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];
  }

}
