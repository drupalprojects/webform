<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the webform filter webform.
 */
class WebformEntityFilterForm extends WebformFilterFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $state = NULL, $state_options = []) {
    $form = parent::buildForm($form, $form_state, $search, $state, $state_options);
    $form['filter']['#title'] = $this->t('Filter webforms');
    $form['filter']['search']['#title'] = $this->t('Filter by title, description, or elements');
    $form['filter']['search']['#autocomplete_route_name'] = 'entity.webform.autocomplete';
    $form['filter']['search']['#placeholder'] = $this->t('Filter by title, description, or elements');
    return $form;
  }

}
