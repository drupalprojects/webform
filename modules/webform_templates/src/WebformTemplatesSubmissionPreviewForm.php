<?php

namespace Drupal\webform_templates;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformDialogTrait;
use Drupal\webform\WebformSubmissionForm;

/**
 * Preview webform submission webform.
 */
class WebformTemplatesSubmissionPreviewForm extends WebformSubmissionForm {

  use WebformDialogTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($this->isModalDialog()) {
      // Disable validation.
      $form['#attributes']['novalidate'] = 'novalidate';

      // Display webform title in modal.
      $form['title'] = [
        '#markup' => $this->getWebform()->label(),
        '#prefix' => '<h1>',
        '#suffix' => '</h1>',
        '#weight' => -101,
      ];

      // Remove type from 'actions' and add modal 'actions'.
      unset($form['actions']['#type']);
      $form['modal_actions'] = ['#type' => 'actions'];
      $form['modal_actions']['select'] = [
        '#type' => 'link',
        '#title' => $this->t('Select'),
        '#url' => Url::fromRoute('entity.webform.duplicate_form', ['webform' => $this->getWebform()->id()]),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(640, ['button', 'button--primary']),
      ];
      $form['modal_actions']['close'] = [
        '#type' => 'submit',
        '#value' => $this->t('Close'),
        '#ajax' => [
          'callback' => '::closeDialog',
          'event' => 'click',
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->isModalDialog()) {
      $form_state->clearErrors();
    }
    else {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * Close dialog.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool|\Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that display validation error messages.
   */
  public function closeDialog(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

}
