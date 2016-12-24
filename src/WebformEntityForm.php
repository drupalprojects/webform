<?php

namespace Drupal\webform;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for controller for webform.
 */
class WebformEntityForm extends BundleEntityFormBase {

  use WebformDialogTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidator
   */
  protected $elementsValidator;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a new WebformUiElementFormBase.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidator $elements_validator
   *   Webform element validator.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(RendererInterface $renderer, WebformElementManagerInterface $element_manager, WebformEntityElementsValidator $elements_validator, WebformTokenManagerInterface $token_manager) {
    $this->renderer = $renderer;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
    $this->tokenManager = $token_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }

    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Customize title for duplicate webform.
    if ($this->operation == 'duplicate') {
      // Display custom title.
      $form['#title'] = $this->t("Duplicate '@label' form", ['@label' => $webform->label()]);
      // If template, clear template's description and remove template flag.
      if ($webform->isTemplate()) {
        $webform->set('description', '');
        $webform->set('template', FALSE);
      }
    }

    $form = parent::buildForm($form, $form_state);
    $form = $this->buildDialog($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Only display id, title, and description for new webforms.
    // Once a webform is created this information is moved to the webform's settings
    // tab.
    if ($webform->isNew()) {
      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $webform->id(),
        '#machine_name' => [
          'exists' => '\Drupal\webform\Entity\Webform::load',
          'source' => ['title'],
        ],
        '#maxlength' => 32,
        '#disabled' => (bool) $webform->id() && $this->operation != 'duplicate',
        '#required' => TRUE,
      ];

      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#maxlength' => 255,
        '#default_value' => $webform->label(),
        '#required' => TRUE,
        '#id' => 'title',
        '#attributes' => [
          'autofocus' => 'autofocus',
        ],
      ];
      $form['description'] = [
        '#type' => 'webform_html_editor',
        '#title' => $this->t('Administrative description'),
        '#default_value' => $webform->get('description'),
      ];
      $form = $this->protectBundleIdElement($form);
    }

    // Call the isolated edit webform that can be overridden by the
    // webform_ui.module.
    $form = $this->editForm($form, $form_state);

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
   * Edit webform element's source code webform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform structure.
   */
  protected function editForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $t_args = [
      ':form_api_href' => 'https://www.drupal.org/node/37775',
      ':render_api_href' => 'https://www.drupal.org/developing/api/8/render',
      ':yaml_href' => 'https://en.wikipedia.org/wiki/YAML',
    ];
    $form['elements'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Elements (YAML)'),
      '#description' => $this->t('Enter a <a href=":form_api_href">Form API (FAPI)</a> and/or a <a href=":render_api_href">Render Array</a> as <a href=":yaml_href">YAML</a>.', $t_args) . '<br/>' .
        '<em>' . $this->t('Please note that comments are not supported and will be removed.') . '</em>',
      '#default_value' => $webform->get('elements') ,
      '#required' => TRUE,
    ];
    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate elements YAML.
    if ($messages = $this->elementsValidator->validate($this->getEntity())) {
      $form_state->setErrorByName('elements');
      foreach ($messages as $message) {
        drupal_set_message($message, 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($response = $this->validateDialog($form, $form_state)) {
      return $response;
    }

    parent::submitForm($form, $form_state);

    if ($this->isModalDialog()) {
      return $this->redirectForm($form, $form_state, Url::fromRoute('entity.webform.edit_form', ['webform' => $this->getEntity()->id()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $is_new = $webform->isNew();
    $webform->save();

    if ($is_new) {
      $this->logger('webform')->notice('Webform @label created.', ['@label' => $webform->label()]);
      drupal_set_message($this->t('Webform %label created.', ['%label' => $webform->label()]));
    }
    else {
      $this->logger('webform')->notice('Webform @label elements saved.', ['@label' => $webform->label()]);
      drupal_set_message($this->t('Webform %label elements saved.', ['%label' => $webform->label()]));
    }
  }

}
