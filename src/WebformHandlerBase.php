<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform handler.
 *
 * @see \Drupal\webform\WebformHandlerInterface
 * @see \Drupal\webform\WebformHandlerManager
 * @see \Drupal\webform\WebformHandlerManagerInterface
 * @see plugin_api
 */
abstract class WebformHandlerBase extends PluginBase implements WebformHandlerInterface {

  /**
   * The webform .
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform = NULL;

  /**
   * The webform handler ID.
   *
   * @var string
   */
  protected $handler_id;

  /**
   * The webform handler label.
   *
   * @var string
   */
  protected $label;

  /**
   * The webform handler status.
   *
   * @var bool
   */
  protected $status = 1;

  /**
   * The weight of the webform handler.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform')
    );
  }

  /**
   * Initialize webform handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform object.
   *
   * @return $this
   *   This webform handler.
   */
  public function init(WebformInterface $webform) {
    $this->webform = $webform;
    return $this;
  }

  /**
   * Get the webform that this handler is attached to.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'webform_handler_' . $this->pluginId . '_summary',
      '#settings' => $this->configuration,
      '#handler' => [
        'id' => $this->pluginDefinition['id'],
        'handler_id' => $this->getHandlerId(),
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function cardinality() {
    return $this->pluginDefinition['cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerId() {
    return $this->handler_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandlerId($handler_id) {
    $this->handler_id = $handler_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label ?: $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'label' => $this->getLabel(),
      'handler_id' => $this->getHandlerId(),
      'status' => $this->getStatus(),
      'weight' => $this->getWeight(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'handler_id' => '',
      'label' => '',
      'status' => 1,
      'weight' => '',
      'settings' => [],
    ];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    $this->handler_id = $configuration['handler_id'];
    $this->label = $configuration['label'];
    $this->weight = $configuration['weight'];
    $this->status = $configuration['status'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {}

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preCreate(array $values) {}

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {}

}
