<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformElementManagerInterface;
use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformHandlerMessageInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "email",
 *   label = @Translation("Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class EmailWebformHandler extends WebformHandlerBase implements WebformHandlerMessageInterface {

  /**
   * Other option value.
   */
  const OTHER_OPTION = '_other_';

  /**
   * Default option value.
   */
  const EMPTY_OPTION = '_empty_';

  /**
   * Default option value.
   */
  const DEFAULT_OPTION = '_default_';

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Cache of default configuration values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform.email'),
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $settings = $this->getEmailConfiguration();
    // Simplify the [webform_submission:values:.*] tokens.
    array_walk($settings, function(&$value, $key) {
      if (is_string($value)) {
        $value = preg_replace('/\[webform_submission:values:([^:]+):(?:raw|value)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_submission:/', '[', $value);
      }
    });
    return [
        '#settings' => $settings,
      ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'to_mail' => 'default',
      'to_options' => [],
      'cc_mail' => '',
      'cc_options' => [],
      'bcc_mail' => '',
      'bcc_options' => [],
      'from_mail' => 'default',
      'from_options' => [],
      'from_name' => 'default',
      'subject' => 'default',
      'body' => 'default',
      'excluded_elements' => [],
      'html' => TRUE,
      'attachments' => FALSE,
      'debug' => FALSE,
    ];
  }

  /**
   * Get configuration default values.
   *
   * @return array
   *   Configuration default values.
   */
  protected function getDefaultConfigurationValues() {
    if (isset($this->defaultValues)) {
      return $this->defaultValues;
    }

    $webform_settings = $this->configFactory->get('webform.settings');
    $site_settings = $this->configFactory->get('system.site');
    $body_format = ($this->configuration['html']) ? 'html' : 'text';
    $default_mail = $webform_settings->get('mail.default_to_mail') ?: $site_settings->get('mail') ?: ini_get('sendmail_from');

    $this->defaultValues = [
      'to_mail' => $default_mail,
      'to_options' => [],
      'cc_mail' => $default_mail,
      'cc_options' => [],
      'bcc_mail' => $default_mail,
      'bcc_options' => [],
      'from_mail' => $default_mail,
      'from_options' => [],
      'from_name' => $webform_settings->get('mail.default_from_name') ?: $site_settings->get('name'),
      'subject' => $webform_settings->get('mail.default_subject') ?: 'Webform submission from: [webform_submission:source-entity]',
      'body' => $this->getBodyDefaultValues($body_format),
    ];

    return $this->defaultValues;
  }

  /**
   * Get configuration default value.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return string|array
   *   Configuration default value.
   */
  protected function getDefaultConfigurationValue($name) {
    $default_values = $this->getDefaultConfigurationValues();
    return $default_values[$name];
  }

  /**
   * Get mail configuration values.
   *
   * @return array
   *   An associative array containing email configuration values.
   */
  protected function getEmailConfiguration() {
    $configuration = $this->getConfiguration();
    $email = [];
    foreach ($configuration['settings'] as $key => $value) {
      $email[$key] = ($value === 'default') ? $this->getDefaultConfigurationValue($key) : $value;
    }
    return $email;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateSettingsToConfiguration($form_state);

    // Get options, mail, and text elements as options (text/value).
    $options_element_options = [];
    $mail_element_options = [];
    $text_element_options = [];
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      $title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]) : $key;
      $text_element_options["[webform_submission:values:$key:value]"] = $title;
      if (isset($element['#options'])) {
        $options_element_options["[webform_submission:values:$key:raw]"] = $title;
      }
      elseif (isset($element['#type']) && in_array($element['#type'], ['email', 'hidden', 'value', 'select', 'radios', 'textfield', 'webform_email_multiple', 'webform_email_confirm'])) {
        $mail_element_options["[webform_submission:values:$key:raw]"] = $title;
      }
    }

    // Disable client-side HTML5 validation which is having issues with hidden
    // element validation.
    // @see http://stackoverflow.com/questions/22148080/an-invalid-form-control-with-name-is-not-focusable
    $form['#attributes']['novalidate'] = 'novalidate';

    // To.
    $form['to'] = [
      '#type' => 'details',
      '#title' => $this->t('Send to'),
      '#open' => TRUE,
    ];
    $form['to'] += $this->buildElement($form_state, 'to_mail', $this->t('To email'), $this->t('To email address'), $mail_element_options, $options_element_options, TRUE);
    $form['to'] += $this->buildElement($form_state, 'cc_mail', $this->t('CC email'), $this->t('CC email address'), $mail_element_options, $options_element_options, FALSE);
    $form['to'] += $this->buildElement($form_state, 'bcc_mail', $this->t('BCC email'), $this->t('Bcc email address'), $mail_element_options, $options_element_options, FALSE);

    // From.
    $form['from'] = [
      '#type' => 'details',
      '#title' => $this->t('Send from'),
      '#open' => TRUE,
    ];
    $form['from'] += $this->buildElement($form_state, 'from_mail', $this->t('From email'), $this->t('From email address'), $mail_element_options, NULL, TRUE);
    $form['from'] += $this->buildElement($form_state, 'from_name', $this->t('From name'), $this->t('From name'), $text_element_options);

    // Message.
    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
    ];
    $form['message'] += $this->buildElement($form_state, 'subject', $this->t('Subject'), $this->t('subject'), $text_element_options);

    // Message: Body.
    // Building a custom select other element that toggles between
    // HTML (CKEditor) and Plain text (CodeMirror) custom body elements.
    $body_options = [
      WebformSelectOther::OTHER_OPTION => $this->t('Custom body...'),
      'default' => $this->t('Default'),
      (string) $this->t('Elements') => $text_element_options,
    ];

    $body_default_format = ($this->configuration['html']) ? 'html' : 'text';
    $body_default_values = $this->getBodyDefaultValues();
    if (isset($body_options[$this->configuration['body']])) {
      $body_default_value = $this->configuration['body'];
      $body_custom_default_value = $body_default_values[$body_default_format];
    }
    else {
      $body_default_value = WebformSelectOther::OTHER_OPTION;
      $body_custom_default_value = $this->configuration['body'];
    }
    $form['message']['body'] = [
      '#type' => 'select',
      '#title' => $this->t('Body'),
      '#options' => $body_options,
      '#required' => TRUE,
      '#parents' => ['settings', 'body'],
      '#default_value' => $body_default_value,
    ];
    foreach ($body_default_values as $format => $default_value) {
      // Custom body.
      $custom_default_value = ($format === $body_default_format) ? $body_custom_default_value : $default_value;
      if ($format == 'html') {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'webform_html_editor',
        ];
      }
      else {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'webform_codemirror',
          '#mode' => $format,
        ];
      }
      $form['message']['body_custom_' . $format] += [
        '#title' => $this->t('Body custom value (@format)', ['@label' => $format]),
        '#title_display' => 'hidden',
        '#parents' => ['settings', 'body_custom_' . $format],
        '#default_value' => $custom_default_value,
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => WebformSelectOther::OTHER_OPTION],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
          'required' => [
            ':input[name="settings[body]"]' => ['value' => WebformSelectOther::OTHER_OPTION],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];

      // Default body.
      $form['message']['body_default_' . $format] = [
        '#type' => 'webform_codemirror',
        '#mode' => $format,
        '#title' => $this->t('Body default value (@format)', ['@label' => $format]),
        '#title_display' => 'hidden',
        '#default_value' => $default_value,
        '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled'],
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => 'default'],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];
    }
    $form['message']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included email values'),
      '#open' => $this->configuration['excluded_elements'] ? TRUE : FALSE,
    ];
    $form['elements']['excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#description' => $this->t('The selected elements will be included in the [webform_submission:values] token. Individual values may still be printed if explicitly specified as a [webform_submission:values:?] in the email body template.'),
      '#webform_id' => $this->webform->id(),
      '#default_value' => $this->configuration['excluded_elements'],
      '#parents' => ['settings', 'excluded_elements'],
    ];

    // Settings.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
    ];
    $form['settings']['html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email as HTML'),
      '#return_value' => TRUE,
      '#access' => $this->supportsHtml(),
      '#parents' => ['settings', 'html'],
      '#default_value' => $this->configuration['html'],
    ];
    $form['settings']['attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include files as attachments'),
      '#return_value' => TRUE,
      '#access' => $this->supportsAttachments(),
      '#parents' => ['settings', 'attachments'],
      '#default_value' => $this->configuration['attachments'],
    ];
    $form['settings']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, sent emails will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#parents' => ['settings', 'debug'],
      '#default_value' => $this->configuration['debug'],
    ];

    // ISSUE: TranslatableMarkup is breaking the #ajax.
    // WORKAROUND: Convert all Render/Markup to strings.
    WebformElementHelper::convertRenderMarkupToStrings($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    // Set custom body based on the selected format.
    if ($values['body'] === WebformSelectOther::OTHER_OPTION) {
      $body_format = ($values['html']) ? 'html' : 'text';
      $values['body'] = $values['body_custom_' . $body_format];
    }
    unset(
      $values['body_custom_text'],
      $values['body_default_html']
    );

    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $is_results_disabled = $webform_submission->getWebform()->getSetting('results_disabled');
    $is_completed = ($webform_submission->getState() == WebformSubmissionInterface::STATE_COMPLETED);
    if ($is_results_disabled || $is_completed) {
      $message = $this->getMessage($webform_submission);
      $this->sendMessage($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(WebformSubmissionInterface $webform_submission) {
    $token_data = [
      'webform-submission-options' => [
        'email' => TRUE,
        'excluded_elements' => $this->configuration['excluded_elements'],
        'html' => ($this->configuration['html'] && $this->supportsHtml()),
      ],
    ];

    $message = [];

    // Copy configuration to $message.
    foreach ($this->configuration as $configuration_key => $configuration_value) {
      // Get configuration name (to, cc, bcc, from, name, subject, mail) and type (mail, options, or text).
      list($configuration_name, $configuration_type) = (strpos($configuration_key, '_') !== FALSE) ? explode('_', $configuration_key) : [$configuration_key, 'text'];

      // Set options and continue.
      if ($configuration_type == 'options') {
        $message[$configuration_key] = $configuration_value;
        continue;
      }

      // Set default value.
      if ($configuration_value === 'default') {
        $configuration_value = $this->getDefaultConfigurationValue($configuration_key);
      }

      // Set email addresses.
      if ($configuration_type == 'mail') {
        $emails = $this->getMessageEmails($webform_submission, $configuration_name, $configuration_value);
        $configuration_value = implode(',', array_unique($emails));
      }

      // Set message key.
      $message[$configuration_key] = $this->tokenManager->replace($configuration_value, $webform_submission, $token_data);
    }

    // Trim the message body.
    $message['body'] = trim($message['body']);

    // Alter body based on the mail system sender.
    if ($this->configuration['html'] && $this->supportsHtml()) {
      switch ($this->getMailSystemSender()) {
        case 'swiftmailer':
          // SwiftMailer requires that the body be valid Markup.
          $message['body'] = Markup::create($message['body']);
          break;
      }
    }
    else {
      // Since Drupal might be rendering a token into the body as markup
      // we need to decode all HTML entities which are being sent as plain text.
      $message['body'] = html_entity_decode($message['body']);
    }

    // Add attachments.
    $message['attachments'] = $this->getMessageAttachments($webform_submission);

    // Add webform submission.
    $message['webform_submission'] = $webform_submission;

    return $message;
  }

  /**
   * Get message to, cc, bcc, and from email addresses.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $configuration_name
   *   The email configuration name. (ie to, cc, bcc, or from)
   * @param $configuration_value
   *   The email configuration value.
   *
   * @return array
   *   An array of email addresses and/or tokens.
   */
  protected function getMessageEmails(WebformSubmissionInterface $webform_submission, $configuration_name, $configuration_value) {
    $emails = [];

    // Get element from token and make sure the element has #options.
    $element_name = $this->getElementNameFromToken($configuration_value);
    $element = ($element_name) ? $this->webform->getElement($element_name) : NULL;
    $element_has_options = ($element && isset($element['#options'])) ? TRUE : FALSE;

    // Check that email handle configuration has email #options.
    $email_has_options = (!empty($this->configuration[$configuration_name . '_options'])) ? TRUE : FALSE;

    // Get emails from options.
    if ($element_has_options && $email_has_options) {
      $email_options = $this->configuration[$configuration_name . '_options'];

      // Set default email address.
      if (!empty($email_options[self::DEFAULT_OPTION])) {
        $emails[] = $email_options[self::DEFAULT_OPTION];
      }

      // Get submission email addresseses as an array.
      $options_element_value = $webform_submission->getData($element_name);
      if (is_array($options_element_value)) {
        $options_values = $options_element_value;
      }
      elseif ($options_element_value) {
        $options_values = [$options_element_value];
      }

      // Set empty email address.
      if (empty($options_values)) {
        if (!empty($email_options[self::EMPTY_OPTION])) {
          $emails[] = $email_options[self::EMPTY_OPTION];
        }
      }
      // Loop through options values and collect email addresses.
      else {
        foreach ($options_values as $option_value) {
          if (!empty($email_options[$option_value])) {
            $emails[] = $email_options[$option_value];
          }
          // Set other email address.
          elseif (!empty($email_options[self::OTHER_OPTION])) {
            $emails[] = $email_options[self::OTHER_OPTION];
          }
        }
      }
    }
    else {
      $emails[] = $configuration_value;
    }

    // Implode and resplit emails to make sure that allow emails are unique.
    $emails = preg_split('/\s*,\s*/', implode(',', $emails));

    // @todd Add role based email addresses.

    return array_unique($emails);
  }

  /**
   * Get message file attachments.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A array of file attachments.
   */
  protected function getMessageAttachments(WebformSubmissionInterface $webform_submission) {
    if (empty($this->configuration['attachments']) || !$this->supportsAttachments()) {
      return [];
    }

    $attachments = [];
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $configuration_key => $element) {
      $element_handler = $this->elementManager->getElementInstance($element);
      // Only elements that extend the 'Managed file' element can add
      // file attachments.
      if (!($element_handler instanceof WebformManagedFileBase)) {
        continue;
      }

      // Check if the element is excluded and should not attach any files.
      if (isset($this->configuration['excluded_elements'][$configuration_key])) {
        continue;
      }

      // Get file ids.
      $fids = $webform_submission->getData($configuration_key);
      if (empty($fids)) {
        continue;
      }

      /** @var \Drupal\file\FileInterface[] $files */
      $files = File::loadMultiple(is_array($fids) ? $fids : [$fids]);
      foreach ($files as $file) {
        $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());
        $attachments[] = [
          'filecontent' => file_get_contents($filepath),
          'filename' => $file->getFilename(),
          'filemime' => $file->getMimeType(),
          // Add URL to be used by resend webform.
          'file' => $file,
        ];
      }
    }
    return $attachments;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $message) {
    $to = $message['to_mail'];
    $from = $message['from_mail'] . (($message['from_name']) ? ' <' . $message['from_name'] . '>' : '');
    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Don't send the message if To, Cc, and Bcc and empty.
    if (empty($message['to_mail']) && empty($message['cc_mail']) && empty($message['bcc_mail'])) {
      if ($this->configuration['debug']) {
        $t_args = ['%subject' => $message['subject']];
        drupal_set_message($this->t('Message <b>not sent</b> %subject because a <em>To</em>, <em>Cc</em>, or <em>Bcc</em> email was not provided.', $t_args), 'warning');
      }
      return ;
    }

    // Send message.
    $this->mailManager->mail('webform', 'email.' . $this->getHandlerId(), $to, $current_langcode, $message, $from);

    // Log message.
    $context = [
      '@form' => $this->getWebform()->label(),
      '@title' => $this->label(),
    ];
    $this->logger->notice('@form webform sent @title email.', $context);

    // Debug by displaying send email onscreen.
    if ($this->configuration['debug']) {
      $t_args = [
        '%from_name' => $message['from_name'],
        '%from_mail' => $message['from_mail'],
        '%to_mail' => $message['to_mail'],
        '%subject' => $message['subject'],
      ];
      $build = [];
      $build['message'] = [
        '#markup' => $this->t('%subject sent to %to_mail from %from_name [%from_mail].', $t_args),
        '#prefix' => '<b>',
        '#suffix' => '</b>',
      ];
      if ($message['html']) {
        $build['body'] = [
          '#markup' => $message['body'],
          '#allowed_tags' => Xss::getAdminTagList(),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
      else {
        $build['body'] = [
          '#markup' => $message['body'],
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ];
      }
      drupal_set_message(\Drupal::service('renderer')->render($build), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resendMessageForm(array $message) {
    $element = [];
    $element['to_mail'] = [
      '#type' => 'webform_email_multiple',
      '#title' => $this->t('To email'),
      '#default_value' => $message['to_mail'],
    ];
    $element['from_mail'] = [
      '#type' => 'webform_email_multiple',
      '#title' => $this->t('From email'),
      '#required' => TRUE,
      '#default_value' => $message['from_mail'],
    ];
    $element['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From name'),
      '#required' => TRUE,
      '#default_value' => $message['from_name'],
    ];
    $element['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $message['subject'],
    ];
    $element['body'] = [
      '#type' => ($message['html']) ? 'webform_html_editor' : 'webform_codemirror',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => $message['body'],
    ];
    $element['html'] = [
      '#type' => 'value',
      '#value' => $message['html'],
    ];
    $element['attachments'] = [
      '#type' => 'value',
      '#value' => $message['attachments'],
    ];

    // Display attached files.
    if ($message['attachments']) {
      $file_links = [];
      foreach ($message['attachments'] as $attachment) {
        $file_links[] = [
          '#theme' => 'file_link',
          '#file' => $attachment['file'],
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
      $element['files'] = [
        '#type' => 'item',
        '#title' => $this->t('Attachments'),
        '#markup' => \Drupal::service('renderer')->render($file_links),
      ];
    }

    // Preload HTML Editor and CodeMirror so that they can be properly
    // initialized when loaded via AJAX.
    $element['#attached']['library'][] = 'webform/webform.element.html_editor';
    $element['#attached']['library'][] = 'webform/webform.element.codemirror.text';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageSummary(array $message) {
    return [
        '#settings' => $message,
      ] + parent::getSummary();
  }

  /**
   * Check that HTML emails are supported.
   *
   * @return bool
   *   TRUE if HTML email is supported.
   */
  protected function supportsHtml() {
    return TRUE;
  }

  /**
   * Check that emailing files as attachments is supported.
   *
   * @return bool
   *   TRUE if emailing files as attachments is supported.
   */
  protected function supportsAttachments() {
    // If 'system.mail.interface.default' is 'test_mail_collector' allow
    // email attachments during testing.
    if (\Drupal::configFactory()->get('system.mail')->get('interface.default') == 'test_mail_collector') {
      return TRUE;
    }
    return \Drupal::moduleHandler()->moduleExists('mailsystem');
  }

  /**
   * Get the Mail System's sender module name.
   *
   * @return string
   *   The Mail System's sender module name.
   */
  protected function getMailSystemSender() {
    $mailsystem_config = $this->configFactory->get('mailsystem.settings');
    $mailsystem_sender = $mailsystem_config->get('webform.sender') ?: $mailsystem_config->get('defaults.sender');
    return $mailsystem_sender;
  }

  /**
   * Get message body default values, which can be formatted as text or html.
   *
   * @param string $format
   *   If a format (text or html) is provided the default value for the
   *   specified format is return. If no format is specified an associative
   *   array containing the text and html default body values will be returned.
   *
   * @return string|array
   *   A single (text or html) default body value or an associative array
   *   containing both the text and html default body values.
   */
  protected function getBodyDefaultValues($format = NULL) {
    $webform_settings = $this->configFactory->get('webform.settings');
    $formats = [
      'text' => $webform_settings->get('mail.default_body_text') ?: '[webform_submission:values]',
      'html' => $webform_settings->get('mail.default_body_html') ?: '[webform_submission:values]',
    ];
    return ($format === NULL) ? $formats : $formats[$format];
  }

  /**
   * Build A select other element for email addresss and names.
   *
   * @param string $name
   *   The element's key
   * @param $title
   *   The element's title
   * @param $label
   *   The element's label
   * @param array $element_options
   *   The element options.
   * @param array $options_options
   *   The options options.
   * @param bool $required
   *   TRUE if the element is required.
   *
   * @return array
   *   A select other element.
   */
  protected function buildElement(FormStateInterface $form_state, $name, $title, $label, array $element_options, array $options_options = NULL, $required = FALSE) {
    list($element_name, $element_type) = (strpos($name, '_') !== FALSE) ? explode('_', $name) : [$name, 'text'];

    $options = [];
    $options[WebformSelectOther::OTHER_OPTION] = $this->t('Custom @label...', ['@label' => $label]);
    $options[(string) $this->t('Default')] = ['default' => $this->getDefaultConfigurationValue($name)];
    if ($element_options) {
      $options[(string) $this->t('Elements')] = $element_options;
    }
    if ($options_options) {
      $options[(string) $this->t('Options')] = $options_options;
    }

    $element = [];

    $element[$name] = [
      '#type' => 'webform_select_other',
      '#title' => $title,
      '#options' => $options,
      '#empty_option' => (!$required) ? '' : NULL,
      '#other__placeholder' => $this->t('Enter @label...', ['@label' => $label]),
      '#other__type' => ($element_type == 'mail') ? 'webform_email_multiple' : 'textfield',
      '#other__allow_tokens' => TRUE,
      '#required' =>  $required,
      '#parents' => ['settings', $name],
      '#default_value' => $this->configuration[$name],
    ];

    // If no options options are defined return the element.
    if (!$options_options) {
      return $element;
    }

    $options_name = $element_name . '_options';
    $options_id = 'webform-email-handler-' . $options_name;

    // Add Ajax callback.
    $element[$name]['#ajax'] = [
      'callback' => [get_class($this), 'ajaxCallback'],
      'wrapper' => $options_id,
    ];

    if (isset($options_options[$this->configuration[$name]]) && ($token_element_name = $this->getElementNameFromToken($this->configuration[$name]))) {
      // Get options element.
      $options_element = $this->webform->getElement($token_element_name);

      // Set mapping options.
      $mapping_options = $options_element['#options'];
      array_walk($mapping_options, function(&$value, $key) {
        $value = '<b>' . $value . '</b>';
      });
      if (preg_match('/_other$/', $options_element['#type'])) {
        $mapping_options[self::OTHER_OPTION] = $this->t("Other (Used when 'other' value is entered)");
      }
      if (empty($options_element['#required'])) {
        $mapping_options[self::EMPTY_OPTION] = $this->t('Empty (Used when no option is selected)');
      }
      $mapping_options[self::DEFAULT_OPTION] = $this->t('Default (This email address will always be included)');

      $element[$options_name] = [
        '#type' => 'webform_mapping',
        '#title' => $this->t('@title options', ['@title' => $title]),
        '#description' => $this->t('The selected element has multiple options. You may enter e-mail addresses for each choice. When that choice is selected, an e-mail will be sent to the corresponding addresses. If a field is left blank, no e-mail will be sent for that option. You may use tokens.') . '<br/><br/>',
        '#description_display' => 'before',
        '#required' => TRUE,
        '#parents' => ['settings', $options_name],
        '#default_value' => $this->configuration[$options_name],

        '#source' => $mapping_options,
        '#source__title' => $this->t('Option'),

        '#destination__type' => 'webform_email_multiple',
        '#destination__allow_tokens' => TRUE,
        '#destination__title' => $this->t('E-mail addresses'),
        '#destination__description' => NULL,
        '#destination__placeholder' => 'example@example.com, other@other.com, [site:mail]',

        '#prefix' => '<div id="' . $options_id  . '">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $element[$options_name] = [
        '#type' => 'value',
        '#value' => [],
        '#parents' => ['settings', $options_name],
        '#prefix' => '<div id="' . $options_id  . '">',
        '#suffix' => '</div>',
      ];
    }

    return $element;
  }

  /**
   * AJAX callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing entity reference details element.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();

    // Get options name from trigger element which is the select_other[select].
    end($trigger_element['#array_parents']);
    $name = prev($trigger_element['#array_parents']);
    $options_name = strtok($name, '_') . '_options';

    $target_parents =  array_slice($trigger_element['#array_parents'], 0, -2);
    $target_parents[] = $options_name;

    $element = NestedArray::getValue($form, $target_parents);
    return $element;
  }

  /**
   * Get element name from webform token.
   *
   * @param string $token
   *   The token.
   * @param string $format
   *   The element format.
   *
   * @return string|null
   *   The element name or NULL if token can not be parsed.
   */
  protected function getElementNameFromToken($token, $format = 'raw') {
    if (preg_match('/\[webform_submission:values:([^:]+):' . $format . '\]/', $token, $match)) {
      return $match[1];
    }
    else {
      return NULL;
    }
  }

}
