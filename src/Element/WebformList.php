<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element to assist in creation of lists.
 *
 * @FormElement("webform_list")
 */
class WebformList extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#label' => t('item'),
      '#labels' => t('items'),
      '#empty_items' => 5,
      '#add_more' => 1,
      '#process' => [
        [$class, 'processList'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return (isset($element['#default_value'])) ? $element['#default_value'] : [];
    }
    elseif (is_array($input) && isset($input['items'])) {
      return self::convertValuesToItems($input['items']);
    }
    else {
      return NULL;
    }
  }

  /**
   * Process items and build list widget.
   */
  public static function processList(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Add validate callback that extracts the array of items.
    $element['#element_validate'] = [[get_called_class(), 'validateList']];

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    // Get unique key used to store the current number of items.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');

    // Store the number of items which is the number of
    // #default_values + number of empty_items.
    if ($form_state->get($number_of_items_storage_key) === NULL) {
      if (empty($element['#default_value']) || !is_array($element['#default_value'])) {
        $number_of_default_values = 0;
        $number_of_empty_items = (int) $element['#empty_items'];
      }
      else {
        $number_of_default_values = count($element['#default_value']);
        $number_of_empty_items = 1;
      }

      $form_state->set($number_of_items_storage_key, $number_of_default_values + $number_of_empty_items);
    }

    $number_of_items = $form_state->get($number_of_items_storage_key);

    $table_id = implode('_', $element['#parents']) . '_table';
    // DEBUG: Disable AJAX callback by commenting out the below callback and
    // wrapper.
    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxCallback'],
      'wrapper' => $table_id,
    ];

    // Build header.
    $header = [
      '',
      '',
      '',
    ];

    // Build rows.
    $row_index = 0;
    $weight = 0;
    $rows = [];
    if (!$form_state->isRebuilding() && isset($element['#default_value']) && is_array($element['#default_value'])) {
      foreach ($element['#default_value'] as $item) {
        $rows[$row_index] = self::buildItemRow($table_id, $row_index, $item, $weight++, $ajax_settings);
        $row_index++;
      }
    }
    while ($row_index < $number_of_items) {
      $rows[$row_index] = self::buildItemRow($table_id, $row_index, '', $weight++, $ajax_settings);
      $row_index++;
    }

    // Build table.
    $element['items'] = [
      '#prefix' => '<div id="' . $table_id . '" class="webform-list-table">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'webform-list-sort-weight',
        ],
      ],
    ] + $rows;

    // Build add items actions.
    $element['add'] = [
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
    ];
    $element['add']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add'),
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'addItemsSubmit']],
      '#ajax' => $ajax_settings,
      '#name' => $table_id . '_add',
    ];
    $element['add']['more_items'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 100,
      '#default_value' => $element['#add_more'],
      '#field_suffix' => t('more @labels', ['@labels' => $element['#labels']]),
    ];

    $element['#attached']['library'][] = 'webform/webform.element.list';

    return $element;
  }

  /**
   * Build an items's row that contains the value and weight.
   *
   * @param string $table_id
   *   The item element's table id.
   * @param int $row_index
   *   The item's row index.
   * @param string $item
   *   The item's value.
   * @param int $weight
   *   The item's weight.
   * @param array $ajax_settings
   *   An array containing AJAX callback settings.
   *
   * @return array
   *   A render array containing inputs for an item's value, and weight.
   */
  public static function buildItemRow($table_id, $row_index, $item, $weight, array $ajax_settings) {
    $row = [];
    $row['item'] = [
      '#type' => 'textfield',
      '#title' => t('Item value'),
      '#title_display' => 'invisible',
      '#size' => 25,
      '#placeholder' => t('Enter value'),
      '#default_value' => $item,
    ];
    $row['weight'] = [
      '#type' => 'weight',
      '#delta' => 1000,
      '#title' => t('Item weight'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => ['webform-list-sort-weight'],
      ],
      '#default_value' => $weight,
    ];

    $row['operations'] = [];
    $row['operations']['add'] = [
      '#type' => 'image_button',
      '#src' => 'core/misc/icons/787878/plus.svg',
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'addItemSubmit']],
      '#ajax' => $ajax_settings,
      // Issue #1342066 Document that buttons with the same #value need a unique
      // #name for the webform API to distinguish them, or change the webform API to
      // assign unique #names automatically.
      '#row_index' => $row_index,
      '#name' => $table_id . '_add_' . $row_index,
    ];

    $row['operations']['remove'] = [
      '#type' => 'image_button',
      '#src' => 'core/misc/icons/787878/ex.svg',
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'removeItemSubmit']],
      '#ajax' => $ajax_settings,
      // Issue #1342066 Document that buttons with the same #value need a unique
      // #name for the webform API to distinguish them, or change the webform API to
      // assign unique #names automatically.
      '#row_index' => $row_index,
      '#name' => $table_id . '_remove_' . $row_index,
    ];

    $row['#weight'] = $weight;
    $row['#attributes']['class'][] = 'draggable';
    return $row;
  }

  /****************************************************************************/
  // Callbacks.
  /****************************************************************************/

  /**
   * Webform submission handler for adding more items.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addItemsSubmit(array &$form, FormStateInterface $form_state) {
    // Get the webform list element by going up two levels.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Add more items to the number of items.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $more_items = (int) $element['add']['more_items']['#value'];
    $form_state->set($number_of_items_storage_key, $number_of_items + $more_items);

    // Reset values.
    $element['items']['#value'] = array_values($element['items']['#value']);
    $form_state->setValueForElement($element['items'], $element['items']['#value']);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $element['items']['#value']);

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission handler for adding an item.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addItemSubmit(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    // Add item.
    $values = [];
    foreach ($element['items']['#value'] as $row_index => $value) {
      $values[] = $value;
      if ($row_index == $button['#row_index']) {
        $values[] = ['item' => '', 'text' => ''];
      }
    }

    // Add one item to the 'number of items'.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $form_state->set($number_of_items_storage_key, $number_of_items + 1);

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission handler for removing an item.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));
    $values = $element['items']['#value'];

    // Remove item.
    unset($values[$button['#row_index']]);
    $values = array_values($values);

    // Remove one item from the 'number of items'.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    // Never allow the number of items to be less than 1.
    if ($number_of_items != 1) {
      $form_state->set($number_of_items_storage_key, $number_of_items - 1);
    }

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission AJAX callback the returns the list table.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parent_length = (isset($button['#row_index'])) ? -4 : -2;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, $parent_length));
    return $element['items'];
  }

  /**
   * Validates webform list element.
   */
  public static function validateList(&$element, FormStateInterface $form_state, &$complete_form) {
    // Collect the item values in a sortable array.
    $values = [];
    foreach (Element::children($element['items']) as $child_key) {
      $row = $element['items'][$child_key];
      $values[] = [
        'item' => $row['item']['#value'],
        'weight' => $row['weight']['#value'],
      ];
    }

    // Sort the item values.
    uasort($values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Convert values to items.
    $items = self::convertValuesToItems($values);

    // Validate required items.
    if (!empty($element['#required']) && empty($items)) {
      if (isset($element['#required_error'])) {
        $form_state->setError($element, $element['#required_error']);
      }
      elseif (isset($element['#title'])) {
        $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
      }
      else {
        $form_state->setError($element);
      }
      return;
    }

    // Clear the element's value by setting it to NULL.
    $form_state->setValueForElement($element, NULL);

    // Now, set the sorted items as the element's value.
    $form_state->setValueForElement($element, $items);
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Get unique key used to store the number of items for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   A unique key used to store the number of items for an element.
   */
  public static function getStorageKey(array $element, $name) {
    return 'webform_list__' . $element['#name'] . '__' . $name;
  }

  /**
   * Convert an array containing of values (item and weight) to an array of items.
   *
   * @param array $values
   *   An array containing of item and weight.
   *
   * @return array
   *   An array of items.
   */
  public static function convertValuesToItems(array $values = []) {
    // Sort the item values.
    uasort($values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Now build the associative array of items.
    $items = [];
    foreach ($values as $value) {
      $value['item'] = trim($value['item']);
      if ($value['item']) {
        $items[] = $value['item'];
      }
    }

    return $items;
  }

}
