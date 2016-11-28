<?php

namespace Drupal\webform;

use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Defines the webform message (and login) manager.
 */
class WebformMessageManager implements WebformMessageManagerInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * A webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * A webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * Constructs a WebformMessageManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, Token $token, LoggerInterface $logger, WebformRequestInterface $request_handler) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->token = $token;
    $this->logger = $logger;
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebform(WebformInterface $webform = NULL) {
    $this->webform = $webform;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity = NULL) {
    $this->sourceEntity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    if ($webform_submission && empty($this->webform)) {
      $this->webform = $webform_submission->getWebform();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function display($key, $type = 'status') {
    if ($build = $this->build($key)) {
      drupal_set_message(\Drupal::service('renderer')->renderPlain($build), $type);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build($key) {
    if ($message = $this->get($key)) {
      return [
        '#markup' => $message,
        '#allowed_tags' => Xss::getAdminTagList(),
      ];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $webform_settings = ($this->webform) ? $this->webform->getSettings() : [];
    if (!empty($webform_settings[$key])) {
      return $this->replaceTokens($webform_settings[$key]);
    }

    $default_settings = $this->configFactory->get('webform.settings')->get('settings');
    if (!empty($default_settings['default_' . $key])) {
      return $this->replaceTokens($default_settings['default_' . $key]);
    }

    $webform = $this->webform;
    $source_entity = $this->sourceEntity;

    $t_args = [
      '%form' => ($source_entity) ? $source_entity->label() : $webform->label(),
      ':handlers_href' => $webform->toUrl('handlers-form')->toString(),
      ':settings_href' => $webform->toUrl('settings-form')->toString(),
      ':duplicate_href' => $webform->toUrl('duplicate-form')->toString(),
    ];

    switch ($key) {
      case WebformMessageManagerInterface::ADMIN_ACCESS:
        return $this->t('This webform is <a href=":settings_href">closed</a>. Only submission administrators are allowed to access this webform and create new submissions.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_DEFAULT_CONFIRMATION:
        return $this->t('New submission added to %form.', $t_args);

      case WebformMessageManagerInterface::FORM_SAVE_EXCEPTION:
        return $this->t('This webform is currently not saving any submitted data. Please enable the <a href=":settings_href">saving of results</a> or add a <a href=":handlers_href">submission handler</a> to the webform.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_PREVIOUS:
        $webform_submission = $this->entityStorage->getLastSubmission($webform, $source_entity, $this->currentUser);
        $submission_route_name = $this->requestHandler->getRouteName($webform_submission, $source_entity, 'webform.user.submission');
        $submission_route_parameters = $this->requestHandler->getRouteParameters($webform_submission, $source_entity);
        $t_args[':submission_href'] = Url::fromRoute($submission_route_name, $submission_route_parameters)->toString();

        return $this->t('You have already submitted this webform.') . ' ' . $this->t('<a href=":submission_href">View your previous submission</a>.', $t_args);

      case WebformMessageManagerInterface::SUBMISSIONS_PREVIOUS:
        $submissions_route_name = $this->requestHandler->getRouteName($webform, $source_entity, 'webform.user.submissions');
        $submissions_route_parameters = $this->requestHandler->getRouteParameters($webform, $source_entity);
        $t_args[':submissions_href'] = Url::fromRoute($submissions_route_name, $submissions_route_parameters)->toString();

        return $this->t('You have already submitted this webform.') . ' ' . $this->t('<a href=":submissions_href">View your previous submissions</a>.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_UPDATED:
        return $this->t('Submission updated in %form.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_TEST;
        return $this->t("The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>.", $t_args);

      case WebformMessageManagerInterface::TEMPLATE_PREVIEW;
        return $this->t('You are previewing the below template, which can be used to <a href=":duplicate_href">create a new webform</a>. <strong>Submitted data will be ignored</strong>.', $t_args);

      default:
        return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($key, $type = 'warning') {
    $webform = $this->webform;
    $context = [
      'link' => $webform->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];

    switch ($key) {
      case WebformMessageManagerInterface::FORM_FILE_UPLOAD_EXCEPTION:
        $message = 'To support file uploads the saving of submission must be enabled. <strong>All uploaded load files would be lost</strong> Please either uncheck \'Disable saving of submissions\' or remove all the file upload elements.';
        break;

      case WebformMessageManagerInterface::FORM_SAVE_EXCEPTION:
        $context['%form'] = $webform->label();
        $message = '%form is not saving any submitted data and has been disabled.';
        break;
    }

    $this->logger->$type($message, $context);
  }

  /**
   * Replace tokens in text.
   *
   * @param string $text
   *   A string of text that main contain tokens.
   *
   * @return string
   *   Text will tokens replaced.
   */
  protected function replaceTokens($text) {
    // Most strings won't contain tokens so lets check and return ASAP.
    if (!is_string($text) || strpos($text, '[') === FALSE) {
      return $text;
    }

    $token_data = [
      'webform' => $this->webform,
      'webform-submission' => $this->webformSubmission,
    ];
    $token_options = ['clear' => TRUE];

    return $this->token->replace($text, $token_data, $token_options);
  }

}
