<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for webform submission notes.
 */
class WebformSubmissionNotesForm extends ContentEntityForm {

  use WebformDialogTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    list($webform_submission, $source_entity) = $request_handler->getWebformSubmissionEntities();

    $form['navigation'] = [
      '#theme' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
      '#access' => $this->isModalDialog() ? FALSE : TRUE,
    ];
    $form['information'] = [
      '#theme' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
      '#access' => $this->isModalDialog() ? FALSE : TRUE,
    ];

    $form['notes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Administrative notes'),
      '#description' => $this->t('Enter notes about this submission. These notes are only visible to submission administrators.'),
      '#default_value' => $webform_submission->getNotes(),
    ];
    $form['sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Star/flag the status of this submission.'),
      '#default_value' => $webform_submission->isSticky(),
      '#return_value' => TRUE,
      '#access' => $this->isModalDialog() ? FALSE : TRUE,
    ];
    $form['#attached']['library'][] = 'webform/webform.admin';
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Submission @sid notes saved.', ['@sid' => '#' . $this->entity->id()]));
  }

}
