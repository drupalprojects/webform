<?php

namespace Drupal\webform_ui;

use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformEntityForm;

/**
 * Base for controller for webform UI.
 */
class WebformUiEntityForm extends WebformEntityForm {

  /**
   * {@inheritdoc}
   */
  public function editForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    if ($webform->isNew()) {
      return $form;
    }

    $element_dialog_attributes = WebformDialogHelper::getModalDialogAttributes(800);

    // Build table header.
    $header = [];
    $header['title'] = $this->t('Title');
    $header['add'] = [
      'data' => '',
      'class' => [RESPONSIVE_PRIORITY_MEDIUM, 'webform-ui-element-operations'],
    ];
    $header['key'] = [
      'data' => $this->t('Key'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    if ($webform->hasFlexboxLayout()) {
      $header['flex'] = [
        'data' => $this->t('Flex'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    $header['required'] = [
      'data' => $this->t('Required'),
      'class' => ['webform-ui-element-required', RESPONSIVE_PRIORITY_LOW],
    ];
    $header['weight'] = $this->t('Weight');
    $header['parent'] = $this->t('Parent');
    if (!$webform->isNew()) {
      $header['operations'] = [
        'data' => $this->t('Operations'),
        'class' => ['webform-ui-element-operations'],
      ];
    }

    // Build table rows for elements.
    $rows = [];
    $elements = $this->getOrderableElements();
    $delta = count($elements);
    foreach ($elements as $element) {
      $key = $element['#webform_key'];

      $plugin_id = $this->elementManager->getElementPluginId($element);

      /** @var \Drupal\webform\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      $is_container = $webform_element->isContainer($element);
      $is_root = $webform_element->isRoot();

      // If disabled, display warning.
      if ($webform_element->isDisabled()) {
        $webform_element->displayDisabledWarning($element);
      }

      // Get row class names.
      $row_class = ['draggable'];
      if ($is_root) {
        $row_class[] = 'tabledrag-root';
        $row_class[] = 'webform-ui-element-root';
      }
      if (!$is_container) {
        $row_class[] = 'tabledrag-leaf';
      }
      if ($is_container) {
        $row_class[] = 'webform-ui-element-container';
      }
      if (!empty($element['#type'])) {
        $row_class[] = 'webform-ui-element-type-' . $element['#type'];
      }
      $row_class[] = 'webform-ui-element-container';

      $rows[$key]['#attributes']['class'] = $row_class;

      $indentation = NULL;
      if ($element['#webform_depth']) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $element['#webform_depth'],
        ];
      }

      $rows[$key]['title'] = [
        '#markup' => $element['#admin_title'] ?: $element['#title'],
        '#prefix' => !empty($indentation) ? $this->renderer->render($indentation) : '',
      ];
      if ($is_container) {
        $route_parameters = [
          'webform' => $webform->id(),
        ];
        $route_options = ['query' => ['parent' => $key]];
        $rows[$key]['add'] = [
          '#type' => 'link',
          '#title' => $this->t('Add element'),
          '#url' => new Url('entity.webform_ui.element', $route_parameters, $route_options),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button-action', 'button--primary', 'button--small']),
        ];
      }
      else {
        $rows[$key]['add'] = ['#markup' => ''];
      }

      $rows[$key]['name'] = [
        '#markup' => $element['#webform_key'],
      ];

      $rows[$key]['type'] = [
        '#markup' => $webform_element->getPluginLabel(),
      ];

      if ($webform->hasFlexboxLayout()) {
        $rows[$key]['flex'] = [
          '#markup' => (empty($element['#flex'])) ? 1 : $element['#flex'],
        ];
      }

      if ($webform_element->hasProperty('required')) {
        $rows[$key]['required'] = [
          '#type' => 'checkbox',
          '#default_value' => (empty($element['#required'])) ? FALSE : TRUE,
        ];
      }
      else {
        $rows[$key]['required'] = ['#markup' => ''];
      }

      $rows[$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for ID @id', ['@id' => $key]),
        '#title_display' => 'invisible',
        '#default_value' => $element['#weight'],
        '#attributes' => [
          'class' => ['row-weight'],
        ],
        '#delta' => $delta,
      ];

      $rows[$key]['parent']['key'] = [
        '#parents' => ['elements_reordered', $key, 'key'],
        '#type' => 'hidden',
        '#value' => $key,
        '#attributes' => [
          'class' => ['row-key'],
        ],
      ];
      $rows[$key]['parent']['parent_key'] = [
        '#parents' => ['elements_reordered', $key, 'parent_key'],
        '#type' => 'textfield',
        '#size' => 20,
        '#title' => $this->t('Parent'),
        '#title_display' => 'invisible',
        '#default_value' => $element['#webform_parent_key'],
        '#attributes' => [
          'class' => ['row-parent-key'],
          'readonly' => 'readonly',
        ],
      ];

      if (!$webform->isNew()) {
        $rows[$key]['operations'] = [
          '#type' => 'operations',
        ];
        $rows[$key]['operations']['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => new Url('entity.webform_ui.element.edit_form', ['webform' => $webform->id(), 'key' => $key]),
          'attributes' => $element_dialog_attributes,
        ];
        // Issue #2741877 Nested modals don't work: when using CKEditor in a
        // modal, then clicking the image button opens another modal,
        // which closes the original modal.
        // @todo Remove the below workaround once this issue is resolved.
        if ($webform_element->getPluginId() == 'processed_text') {
          unset($rows[$key]['operations']['#links']['edit']['attributes']);
        }
        $rows[$key]['operations']['#links']['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'url' => new Url('entity.webform_ui.element.duplicate_form', [
            'webform' => $webform->id(),
            'key' => $key,
          ]),
          'attributes' => $element_dialog_attributes,
        ];
        $rows[$key]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => new Url('entity.webform_ui.element.delete_form', [
            'webform' => $webform->id(),
            'key' => $key,
          ]),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(640),
        ];
      }
    }

    // Must manually add local actions to the webform because we can't alter local
    // actions and add the needed dialog attributes.
    // @see https://www.drupal.org/node/2585169
    $local_action_attributes = WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button-action', 'button--primary', 'button--small']);
    $form['local_actions'] = [
      '#prefix' => '<div class="webform-ui-local-actions">',
      '#suffix' => '</div>',
    ];
    $form['local_actions']['add_element'] = [
      '#type' => 'link',
      '#title' => $this->t('Add element'),
      '#url' => new Url('entity.webform_ui.element', ['webform' => $webform->id()]),
      '#attributes' => $local_action_attributes,
    ];
    if ($this->elementManager->createInstance('webform_wizard_page')->isEnabled()) {
      $form['local_actions']['add_page'] = [
        '#type' => 'link',
        '#title' => $this->t('Add page'),
        '#url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_wizard_page']),
        '#attributes' => $local_action_attributes,
      ];
    }
    if ($webform->hasFlexboxLayout()) {
      $form['local_actions']['add_layout'] = [
        '#type' => 'link',
        '#title' => $this->t('Add layout'),
        '#url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_flexbox']),
        '#attributes' => $local_action_attributes,
      ];
    }

    $form['elements_reordered'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('Please add elements to this webform.'),
      '#attributes' => [
        'class' => ['webform-ui-elements-table'],
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'row-parent-key',
          'source' => 'row-key',
          'hidden' => TRUE, /* hides the WEIGHT & PARENT tree columns below */
          'limit' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ],
      ],
    ] + $rows;

    // Must preload libraries required by (modal) dialogs.
    $form['#attached']['library'][] = 'webform/webform.admin.dialog';
    $form['#attached']['library'][] = 'webform_ui/webform_ui';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Don't validate new webforms because they don't have any initial
    // elements.
    if ($webform->isNew()) {
      return;
    }

    parent::validateForm($form, $form_state);

    // Get raw flattened elements that will be used to rebuild element's YAML
    // hierarchy.
    $elements_flattened = $webform->getElementsDecodedAndFlattened();

    // Get the reordered elements and sort them by weight.
    $elements_reordered = $form_state->getValue('elements_reordered');
    uasort($elements_reordered, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Make sure the reordered element keys and match the existing element keys.
    if (array_diff_key($elements_reordered, $elements_flattened)) {
      $form_state->setError($form['elements_reordered'], $this->t('The elements have been unexpectedly altered. Please try again'));
    }

    // Validate parent key and add children to ordered elements.
    foreach ($elements_reordered as $key => $table_element) {
      $parent_key = $table_element['parent_key'];

      // Validate the parent key.
      if ($parent_key && !isset($elements_flattened[$parent_key])) {
        $form_state->setError($form['elements_reordered'], $this->t('Parent %parent_key does not exist.', ['%parent_key' => $parent_key]));
        return;
      }

      // Set #required or remove the property.
      if (isset($elements_reordered[$key]['required'])) {
        if (empty($elements_reordered[$key]['required'])) {
          unset($elements_flattened[$key]['#required']);
        }
        else {
          $elements_flattened[$key]['#required'] = TRUE;
        }
      }

      // Add this key to the parent's children.
      $elements_reordered[$parent_key]['children'][$key] = $key;
    }

    // Rebuild elements to reflect new hierarchy.
    $elements_updated = [];
    // Preserve the original elements root properties.
    $elements_original = Yaml::decode($webform->get('elements'));
    foreach ($elements_original as $key => $value) {
      if (Element::property($key)) {
        $elements_updated[$key] = $value;
      }
    }
    $this->buildUpdatedElementsRecursive($elements_updated, '', $elements_reordered, $elements_flattened);

    // Update the webform's elements.
    $webform->setElements($elements_updated);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = ($this->entity->isNew()) ? $this->t('Save') : $this->t('Save elements');
    return $actions;
  }

  /**
   * Build updated elements using the new parent child relationship.
   *
   * @param array $elements
   *   An associative array that will be populated with updated elements
   *   hierarchy.
   * @param string $key
   *   The current element key. The blank empty key represents the elements
   *   root.
   * @param array $elements_reordered
   *   An associative array contain the reordered elements parent child
   *   relationship.
   * @param array $elements_flattened
   *   An associative array containing the raw flattened elements that will
   *   copied into the updated elements hierarchy.
   */
  protected function buildUpdatedElementsRecursive(array &$elements, $key, array $elements_reordered, array $elements_flattened) {
    if (!isset($elements_reordered[$key]['children'])) {
      return;
    }

    foreach ($elements_reordered[$key]['children'] as $key) {
      $elements[$key] = $elements_flattened[$key];
      $this->buildUpdatedElementsRecursive($elements[$key], $key, $elements_reordered, $elements_flattened);
    }
  }

  /**
   * Get webform's elements as an associative array of orderable elements.
   *
   * @return array
   *   An associative array of orderable elements.
   */
  protected function getOrderableElements() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $elements = $webform->getElementsInitializedAndFlattened();
    $weights = [];
    foreach ($elements as &$element) {
      $parent_key = $element['#webform_parent_key'];
      if (!isset($weights[$parent_key])) {
        $element['#weight'] = $weights[$parent_key] = 0;
      }
      else {
        $element['#weight'] = ++$weights[$parent_key];
      }

      if (empty($element['#type'])) {
        if (isset($element['#theme'])) {
          $element['#type'] = $element['#theme'];
        }
        elseif (isset($element['#markup'])) {
          $element['#type'] = 'markup';
        }
        else {
          $element['#type'] = '';
        }
      }

      if (empty($element['#title'])) {
        if (!empty($element['#markup'])) {
          $element['#title'] = Unicode::truncate(strip_tags($element['#markup']), 100, TRUE, TRUE);
        }
        else {
          $element['#title'] = '[' . t('blank') . ']';
        }
      }
    }
    return $elements;
  }

}
