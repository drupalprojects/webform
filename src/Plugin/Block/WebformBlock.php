<?php

namespace Drupal\webform\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "webform_block",
 *   admin_label = @Translation("Webform"),
 *   category = @Translation("Webform")
 * )
 */
class WebformBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a HelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'webform_id' => '',
      'default_data' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['webform_id'] = [
      '#title' => $this->t('Webform'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'webform',
      '#required' => TRUE,
      '#default_value' => $this->getWebform(),
    ];
    $form['default_data'] = [
      '#title' => $this->t('Default webform submission data (YAML)'),
      '#description' => $this->t('Enter webform submission data as name and value pairs which will be used to prepopulate the selected webform. You may use tokens.'),
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#default_value' => $this->configuration['default_data'],
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['webform', 'webform-submission'],
        '#click_insert' => FALSE,
        '#dialog' => TRUE,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['webform_id'] = $form_state->getValue('webform_id');
    $this->configuration['default_data'] = $form_state->getValue('default_data');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = ['data' => $this->configuration['default_data']];
    return $this->getWebform()->getSubmissionForm($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $webform = $this->getWebform();
    if (!$webform || !$webform->access('submission_create', $account)) {
      return AccessResult::forbidden();
    }
    else {
      return parent::blockAccess($account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Caching strategy is handled by the webform.
    return 0;
  }

  /**
   * Get this block instance webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform or NULL.
   */
  protected function getWebform() {
    return Webform::load($this->configuration['webform_id']);
  }

}
