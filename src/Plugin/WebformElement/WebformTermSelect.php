<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformTermSelect as TermSelectElement;

/**
 * Provides a 'webform_term_select' element.
 *
 * @WebformElement(
 *   id = "webform_term_select",
 *   label = @Translation("Term select"),
 *   description = @Translation("Provides a form element to select a single or multiple terms displayed as hierarchical tree or as breadcrumbs using a select menu."),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class WebformTermSelect extends Select implements WebformEntityReferenceInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      'vocabulary' => '',
      'breadcrumb' => FALSE,
      'breadcrumb_delimiter' => ' › ',
      'tree_delimiter' => '-',
    ];

    unset($properties['options']);
    unset($properties['options_randomize']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['term_reference'] = [
      '#type' => 'fieldset',
      '#title' => t('Term reference settings'),
      '#weight' => -40,
    ];
    $form['term_reference']['vocabulary'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Vocabulary'),
      '#target_type' => 'taxonomy_vocabulary',
      '#selection_handler' => 'default:taxonomy_vocabulary',
    ];
    $form['term_reference']['breadcrumb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display term hierarchy using breadcrumbs.'),
      '#return_value' => TRUE,
    ];
    $form['term_reference']['breadcrumb_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Breadcrumb delimiter'),
      '#size' => 10,
      '#states' => [
        'visible' => [
          [':input[name="properties[breadcrumb]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="properties[format]"]' => ['value' => 'breadcrumb']],
        ],
        'required' => [
          [':input[name="properties[breadcrumb]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="properties[format]"]' => ['value' => 'breadcrumb']],
        ],
      ],
    ];
    $form['term_reference']['tree_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tree delimiter'),
      '#size' => 10,
      '#states' => [
        'visible' => [
          ':input[name="properties[breadcrumb]"]' => [
            'checked' => FALSE,
          ],
        ],
        'required' => [
          ':input[name="properties[breadcrumb]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Overrides: \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::validateConfigurationForm.
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetType(array $element) {
    return 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptions(array &$element) {
    TermSelectElement::setOptions($element);
  }

}
