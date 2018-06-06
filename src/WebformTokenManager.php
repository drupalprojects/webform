<?php

namespace Drupal\webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, Token $token) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->token = $token;

    $this->config = $this->configFactory->get('webform.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function replace($text, EntityInterface $entity = NULL, array $data = [], array $options = []) {
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

    // For anonymous users remove all [current-user] tokens to prevent
    // anonymous user properties from being displayed.
    // For example, the [current-user:display-name] token will return
    // 'Anonymous', which is not an expected behavior.
    if ($this->currentUser->isAnonymous() && strpos($text, '[current-user:') !== FALSE) {
      $text = preg_replace('/\[current-user:[^]]+\]/', '', $text);
    }

    // Collect all tokens that include the clear suffix.
    $tokens_clear = [];
    if (preg_match_all('/\[(webform[^]]+):clear\]/', $text, $matches)) {
      foreach ($matches[0] as $index => $match) {
        // Wrapping tokens in {webform-token-clear} so that only tokens with
        // the :clear suffix are removed.
        $token_base = '{webform-token-clear}[' . $matches[1][$index] . ']{/webform-token-clear}';
        $text = str_replace($match, $token_base, $text);
        $tokens_clear[] = $token_base;
      }
    }

    // Replace the webform related tokens.
    $text = $this->token->replace($text, $data, $options);

    // Collect tokens that include the clear suffix.
    if ($tokens_clear) {
      foreach ($tokens_clear as $clear_token) {
        $text = str_replace($clear_token, '', $text);
      }
      // Replace {webform-token-clear} wrappers that are no longer needed.
      $text = preg_replace('/{\/?webform-token-clear}/', '', $text);
    }

    // Clear current user tokens for undefined values.
    if (strpos($text, '[current-user:') !== FALSE) {
      $text = preg_replace('/\[current-user:[^\]]+\]/', '', $text);
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

    $build = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#click_insert' => TRUE,
      '#dialog' => TRUE,
    ];

    if ($description) {
      if ($this->config->get('ui.description_help')) {
        return [
          '#type' => 'container',
          'token_tree_link' => $build,
          'help' => [
            '#type' => 'webform_help',
            '#help' => $description,
          ],
        ];
      }
      else {
        return [
          '#type' => 'container',
          'token_tree_link' => $build,
          'description' => [
            '#prefix' => ' ',
            '#markup' => $description,
          ],
        ];
      }
    }
    else {
      return [
        '#type' => 'container',
        'token_tree_link' => $build,
      ];
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
