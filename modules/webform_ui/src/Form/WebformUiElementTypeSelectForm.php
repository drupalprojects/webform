<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;

/**
 * Provides a select element type webform for a webform element.
 */
class WebformUiElementTypeSelectForm extends WebformUiElementTypeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_ui_element_type_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $parent = $this->getRequest()->query->get('parent');

    $elements = $this->elementManager->getInstances();
    $definitions = $this->getDefinitions();
    $category_index = 0;
    $categories = [];

    $form['#attached']['library'][] = 'webform/webform.form';
    $form['#attached']['library'][] = 'webform/webform.tooltip';

    $form['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by element name'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-ui-element-type-table',
        'title' => $this->t('Enter a part of the element name to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    foreach ($definitions as $plugin_id => $plugin_definition) {
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $elements[$plugin_id];

      // Skip hidden plugins.
      if ($webform_element->isHidden()) {
        continue;
      }

      // Skip wizard page which has a dedicated URL.
      if ($plugin_id == 'webform_wizard_page') {
        continue;
      }

      $route_parameters = ['webform' => $webform->id(), 'type' => $plugin_id];
      $route_options = ($parent) ? ['query' => ['parent' => $parent]] : [];
      $row = [];
      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $plugin_definition['label'],
        '#url' => Url::fromRoute('entity.webform_ui.element.add_form', $route_parameters, $route_options),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        '#prefix' => '<div class="webform-form-filter-text-source">',
        '#suffix' => '</div>',
      ];
      $row['operations']['data'] = [
        '#type' => 'link',
        '#title' => $this->t('Add element'),
        '#url' => Url::fromRoute('entity.webform_ui.element.add_form', $route_parameters, $route_options),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button-action', 'button--primary', 'button--small']),
      ];

      // Issue #2741877 Nested modals don't work: when using CKEditor in a
      // modal, then clicking the image button opens another modal,
      // which closes the original modal.
      // @todo Remove the below workaround once this issue is resolved.
      if ($webform_element->getPluginId() == 'processed_text') {
        unset($row['title']['data']['#attributes']);
        unset($row['operations']['data']['#attributes']);
        if (isset($row['operations'])) {
          $row['operations']['data']['#attributes']['class'] = ['button', 'button-action', 'button--primary', 'button--small'];
        }
      }

      $row['title']['data']['#attributes']['class'][] = 'js-webform-tooltip-link';
      $row['title']['data']['#attributes']['class'][] = 'webform-tooltip-link';
      $row['title']['data']['#attributes']['title'] = $plugin_definition['description'];

      $category_name = (string) $plugin_definition['category'];
      if (!isset($categories[$category_name])) {
        $categories[$category_name] = $category_index++;
        $category_id = $categories[$category_name];
        $form[$category_id] = [
          '#type' => 'details',
          '#title' => $plugin_definition['category'],
          '#open' => TRUE,
          '#attributes' => ['data-webform-element-id' => 'webform-ui-element-type-' . $category_id],
        ];
        $form[$category_id]['elements'] = [
          '#type' => 'table',
          '#header' => $this->getHeader(),
          '#rows' => [],
          '#empty' => $this->t('No element available.'),
          '#attributes' => [
            'class' => ['webform-ui-element-type-table'],
          ],
        ];
      }
      else {
        $category_id = $categories[$category_name];
      }

      $form[$category_id]['elements']['#rows'][] = $row;
    }

    return $form;
  }

}
