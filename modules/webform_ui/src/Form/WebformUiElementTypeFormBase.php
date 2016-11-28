<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a abstract element type webform for a webform element.
 */
abstract class WebformUiElementTypeFormBase extends FormBase {

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformUiElementSelectTypeForm object.
   *
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(WebformElementManagerInterface $element_manager) {
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }

  /**
   * Gets the sorted definition of all WebformElement plugins.
   *
   * @return array
   *   An array of WebformElement plugin definitions. Keys are element types.
   */
  protected function getDefinitions() {
    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);

    // Get definitions with basic and advanced first and uncategorized elements
    // last.
    $no_category = '';
    $basic_category = (string) $this->t('Basic elements');
    $advanced_category = (string) $this->t('Advanced elements');
    $uncategorized = $grouped_definitions[$no_category];

    $sorted_definitions = [];
    $sorted_definitions += $grouped_definitions[$basic_category];
    $sorted_definitions += $grouped_definitions[$advanced_category];
    unset($grouped_definitions[$basic_category], $grouped_definitions[$advanced_category], $grouped_definitions[$no_category]);
    foreach ($grouped_definitions as $grouped_definition) {
      $sorted_definitions += $grouped_definition;
    }
    $sorted_definitions += $uncategorized;

    foreach ($sorted_definitions as &$plugin_definition) {
      if (!isset($plugin_definition['category'])) {
        $plugin_definition['category'] = $this->t('Other elements');
      }
    }

    return $sorted_definitions;
  }

}
