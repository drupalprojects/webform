<?php

namespace Drupal\webform\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'webform_entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "webform_entity_reference_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (!isset($items[$delta]->status)) {
      $items[$delta]->status = 1;
    }

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Set element 'target_id' default properties.
    $element['target_id'] += [
      '#weight' => 0,
    ];

    // Get weight.
    $weight = $element['target_id']['#weight'];

    $element['default_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Default webform submission data (YAML)'),
      '#description' => $this->t('Enter webform submission data as name and value pairs which will be used to prepopulate the selected webform. You may use tokens.'),
      '#weight' => $weight++,
      '#default_value' => $items[$delta]->default_data,
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['webform', 'webform-submission'],
        '#click_insert' => FALSE,
        '#dialog' => TRUE,
        '#weight' => $weight++,
      ];
    }

    $element['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Webform status'),
      '#description' => $this->t('Closing a webform prevents any further submissions by any users.'),
      '#options' => [
        1 => $this->t('Open'),
        0 => $this->t('Closed'),
      ],
      '#weight' => $weight++,
      '#default_value' => ($items[$delta]->status == 1) ? 1 : 0,
    ];

    return $element;
  }

}
