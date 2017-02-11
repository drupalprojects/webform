<?php

namespace Drupal\webform;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Webform add-ons manager.
 */
class WebformAddonsManager implements WebformAddonsManagerInterface {

  use StringTranslationTrait;

  /**
   * Projects that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $projects;

  /**
   * Constructs a WebformAddOnsManager object.
   */
  public function __construct() {
    $this->projects = $this->initProjects();
  }

  /**
   * {@inheritdoc}
   */
  public function getProject($name) {
    return $this->projects[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects($category = NULL) {
    $projects = $this->projects;
    if ($category) {
      foreach ($projects as $project_name => $project) {
        if ($project['category'] != $category) {
          unset($projects[$project_name]);
        }
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings() {
    $projects = $this->projects;
    foreach ($projects as $project_name => $project) {
      if (empty($project['third_party_settings'])) {
        unset($projects[$project_name]);
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = [];
    $categories['config'] = [
      'title' => $this->t('Configuration management'),
    ];
    $categories['element'] = [
      'title' => $this->t('Elements'),
    ];
    $categories['integration'] = [
      'title' => $this->t('Integration'),
    ];
    $categories['mail'] = [
      'title' => $this->t('Mail'),
    ];
    $categories['migrate'] = [
      'title' => $this->t('Migrate'),
    ];
    $categories['spam'] = [
      'title' => $this->t('SPAM Protection'),
    ];
    $categories['handler'] = [
      'title' => $this->t('Submission handling'),
    ];
    $categories['validation'] = [
      'title' => $this->t('Validation'),
    ];
    $categories['utility'] = [
      'title' => $this->t('Utility'),
    ];
    return $categories;
  }

  /**
   * Initialize add-on projects.
   *
   * @return array
   *   An associative array containing add-on projects.
   */
  protected function initProjects() {
    $projects = [];

    // Element: Webform Layout Container.
    $projects['webform_layout_container'] = [
      'title' => $this->t('Webform Layout Container'),
      'description' => $this->t("Provides a layout container element to add to a webform, which uses old fashion floats to support legacy browsers that don't support CSS Flexbox (IE9 and IE10)."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_layout_container'),
      'category' => 'element',
    ];

    // Spam: CAPTCHA.
    $projects['captcha'] = [
      'title' => $this->t('CAPTCHA'),
      'description' => $this->t('Provides CAPTCHA for adding challenges to arbitrary forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/captcha'),
      'category' => 'spam',
    ];

    // Spam: Honeypot.
    $projects['honeypot'] = [
      'title' => $this->t('Honeypot'),
      'description' => $this->t('Mitigates spam form submissions using the honeypot method.'),
      'url' => Url::fromUri('https://www.drupal.org/project/honeypot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
    ];

    // Validation: Clientside Validation.
    $projects['clientside_validation'] = [
      'title' => $this->t('Clientside Validation'),
      'description' => $this->t('Adds clientside validation to forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/clientside_validation'),
      'category' => 'validation',
    ];

    // Validation: Validators.
    $projects['validators'] = [
      'title' => $this->t('Validators'),
      'description' => $this->t('Provides Symfony (form) Validators for Drupal 8.'),
      'url' => Url::fromUri('https://www.drupal.org/project/validators'),
      'category' => 'validation',
    ];

    // Integrations: Webform Views Integration.
    $projects['webform_views'] = [
      'title' => $this->t('Webform Views Integration'),
      'description' => $this->t('Integrates Forms 8.x-5.x and Views modules.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views'),
      'category' => 'integration',
    ];

    // Integrations: Webfomr MailChimp.
    $projects['mailchimp'] = [
      'title' => $this->t('Webform MailChimp'),
      'description' => $this->t('Posts form submissions to MailChimp list.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mailchimp'),
      'category' => 'integration',
    ];

    // Integrations: Webform Slack integration.
    $projects['webform_slack'] = [
      'title' => $this->t('Webform Slack integration'),
      'description' => $this->t('Provides a Webform handler for posting a message to a slack channel when a submission is saved.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/smaz/2833275'),
      'category' => 'integration',
    ];

    // Handler: Web Form Queue.
    $projects['webform_queue'] = [
      'title' => $this->t('Webform Queue'),
      'description' => $this->t('Posts form submissions into a Drupal queue.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_queue'),
      'category' => 'handler',
    ];

    // Mail: Mail System.
    $projects['mailsystem'] = [
      'title' => $this->t('Mail System'),
      'description' => $this->t('Provides a user interface for per-module and site-wide mail system selection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailsystem'),
      'category' => 'mail',
    ];

    // Mail: SMTP Authentication Support.
    $projects['smtp'] = [
      'title' => $this->t('SMTP Authentication Support'),
      'description' => $this->t('Allows for site emails to be sent through an SMTP server of your choice.'),
      'url' => Url::fromUri('https://www.drupal.org/project/smtp'),
      'category' => 'mail',
    ];

    // Utility: Webform Encrypt.
    $projects['wf_encrypt'] = [
      'title' => $this->t('Webform Encrypt'),
      'description' => $this->t('Provides encryption for webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/wf_encrypt'),
      'category' => 'utility',
    ];

    // Utility: Token.
    $projects['token'] = [
      'title' => $this->t('Token'),
      'description' => $this->t('Provides a user interface for the Token API and some missing core tokens.'),
      'url' => Url::fromUri('https://www.drupal.org/project/token'),
      'category' => 'utility',
    ];

    // Migrate: YAML Form Migrate.
    $projects['yamlform_migrate'] = [
      'title' => $this->t('YAML Form Migrate'),
      'description' => $this->t('Provides migration routines from  Drupal 6 YAML Form module to  Drupal 8 YAML Form module.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/dippers/2819169'),
      'category' => 'migrate',
    ];

    // Config: Drush CMI tools.
    $projects['drush_cmi_tools'] = [
      'title' => $this->t('Drush CMI tools'),
      'description' => $this->t('Provides advanced CMI import and export functionality for CMI workflows. Drush CMI tools should be used to protect Forms from being overwritten during a configuration import.'),
      'url' => Url::fromUri('https://github.com/previousnext/drush_cmi_tools'),
      'category' => 'config',
    ];

    // Configuration Ignore.
    $projects['config_ignore'] = [
      'title' => $this->t('Config Ignore'),
      'description' => $this->t('Ignore certain configuration during import'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_ignore'),
      'category' => 'config',
    ];

    // Configuration Split.
    $projects['config_split'] = [
      'title' => $this->t('Configuration Split'),
      'description' => $this->t('Provides configuration filter for importing and exporting split config.'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_split'),
      'category' => 'config',
    ];

    return $projects;
  }

}
