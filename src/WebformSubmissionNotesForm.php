<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for webform submission notes.
 */
class WebformSubmissionNotesForm extends ContentEntityForm {

  use WebformDialogTrait;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, WebformRequestInterface $request_handler) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    list($webform_submission, $source_entity) = $this->requestHandler->getWebformSubmissionEntities();

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
