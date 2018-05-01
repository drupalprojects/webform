<?php

namespace Drupal\webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\webform\Utility\WebformFormHelper;

/**
 * Defines a class to manage token replacement.
 */
class WebformTokenManager implements WebformTokenManagerInterface {

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a WebformTokenManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, Token $token) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->token = $token;

    $this->config = $this->configFactory->get('webform.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function replace($text, EntityInterface $entity = NULL, array $data = [], array $options = []) {
    // Issue #2968554: Clear cache via the UI is throwing an exception.
    // Workaround: Do not replace tokens when cache is cleared via the UI.
    // (/admin/config/development/performance)
    // @see https://www.drupal.org/project/webform/issues/2968554
    if (\Drupal::routeMatch()->getRouteName() === 'system.performance_settings') {
      return $text;
    }

    // Replace tokens within an array.
    if (is_array($text)) {
      foreach ($text as $key => $value) {
        $text[$key] = $this->replace($value, $entity, $data, $options);
      }
      return $text;
    }

    // Most strings won't contain tokens so let's check and return ASAP.
    if (!is_string($text) || strpos($text, '[') === FALSE) {
      return $text;
    }

    if ($entity) {
      // Replace @deprecated [webform-submission] with [webform_submission].
      $text = str_replace('[webform-submission:', '[webform_submission:', $text);

      // Set token data based on entity type.
      $this->setTokenData($data, $entity);
    }

    // Track the active theme, if there is no active theme it means tokens
    // are being replaced during the initial page request.
    $has_active_theme = $this->themeManager->hasActiveTheme();

    // For anonymous users remove all [current-user] tokens to prevent
    // anonymous user properties from being displayed.
    // For example, the [current-user:display-name] token will return
    // 'Anonymous', which is not an expected behavior.
    if ($this->currentUser->isAnonymous() && strpos($text, '[current-user:') !== FALSE) {
      $text = preg_replace('/\[current-user:[^]]+\]/', '', $text);
    }

    // Replace the webform related tokens.
    $text = $this->token->replace($text, $data, $options);

    // Clear current user tokens for undefined values.
    if (strpos($text, '[current-user:') !== FALSE) {
      $text = preg_replace('/\[current-user:[^\]]+\]/', '', $text);
    }

    // If there was no active theme and now there is one.
    // Reset the active theme, so that theme negotiators can determine the
    // correct active theme.
    if (!$has_active_theme && $this->themeManager->hasActiveTheme()) {
      $this->themeManager->resetActiveTheme();
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTreeLink(array $token_types = ['webform', 'webform_submission', 'webform_handler'], $description = NULL) {
    if (!$this->moduleHandler->moduleExists('token')) {
      return [];
    }

    // @todo Issue #2235581: Make Token Dialog support inserting in WYSIWYGs.
    $build = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#click_insert' => FALSE,
      '#dialog' => TRUE,
    ];

    if ($description) {
      if ($this->config->get('ui.description_help')) {
        return [
          'token_tree_link' => $build,
          'help' => [
            '#type' => 'webform_help',
            '#help' => $description,
          ],
        ];
      }
      else {
        return [
          'token_tree_link' => $build,
          'description' => [
            '#prefix' => ' ',
            '#markup' => $description,
          ],
        ];
      }
    }
    else {
      return $build;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate(array &$form, array $token_types = ['webform', 'webform_submission', 'webform_handler']) {
    if (!function_exists('token_element_validate')) {
      return;
    }

    // Always add system tokens.
    // @see system_token_info()
    $token_types = array_merge($token_types, ['site', 'date']);

    $text_element_types = [
      'email' => 'email',
      'textfield' => 'textfield',
      'textarea' => 'textarea',
      'url' => 'url',
      'webform_codemirror' => 'webform_codemirror',
      'webform_email_multiple' => 'webform_email_multiple',
      'webform_html_editor' => 'webform_html_editor',
      'webform_checkboxes_other' => 'webform_checkboxes_other',
      'webform_select_other' => 'webform_select_other',
      'webform_radios_other' => 'webform_radios_other',
    ];
    $elements =& WebformFormHelper::flattenElements($form);
    foreach ($elements as &$element) {
      if (!isset($element['#type']) || !isset($text_element_types[$element['#type']])) {
        continue;
      }

      $element['#element_validate'][] = 'token_element_validate';
      $element['#token_types'] = $token_types;
    }
  }

  /**
   * Get token data based on an entity's type.
   *
   * @param array $token_data
   *   An array of token data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Webform or Webform submission entity.
   */
  protected function setTokenData(array &$token_data, EntityInterface $entity) {
    if ($entity instanceof WebformSubmissionInterface) {
      $token_data['webform_submission'] = $entity;
      $token_data['webform'] = $entity->getWebform();
    }
    elseif ($entity instanceof WebformInterface) {
      $token_data['webform'] = $entity;
    }
  }

}
