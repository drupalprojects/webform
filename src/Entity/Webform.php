<?php

namespace Drupal\webform\Entity;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformHandlerInterface;
use Drupal\webform\WebformHandlerPluginCollection;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the webform entity.
 *
 * @ConfigEntityType(
 *   id = "webform",
 *   label = @Translation("Webform"),
 *   handlers = {
 *     "storage" = "\Drupal\webform\WebformEntityStorage",
 *     "view_builder" = "Drupal\webform\WebformEntityViewBuilder",
 *     "list_builder" = "Drupal\webform\WebformEntityListBuilder",
 *     "access" = "Drupal\webform\WebformEntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\webform\WebformEntityForm",
 *       "settings" = "Drupal\webform\WebformEntitySettingsForm",
 *       "third_party_settings" = "Drupal\webform\WebformEntityThirdPartySettingsForm",
 *       "assets" = "Drupal\webform\WebformEntityAssetsForm",
 *       "access" = "Drupal\webform\WebformEntityAccessForm",
 *       "handlers" = "Drupal\webform\WebformEntityHandlersForm",
 *       "delete" = "Drupal\webform\WebformEntityDeleteForm",
 *       "duplicate" = "Drupal\webform\WebformEntityForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   bundle_of = "webform_submission",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/webform/{webform}",
 *     "submissions" = "/webform/{webform}/submissions",
 *     "add-form" = "/webform/{webform}",
 *     "test-form" = "/webform/{webform}/test",
 *     "edit-form" = "/admin/structure/webform/manage/{webform}",
 *     "settings-form" = "/admin/structure/webform/manage/{webform}/settings",
 *     "assets-form" = "/admin/structure/webform/manage/{webform}/assets",
 *     "third-party-settings-form" = "/admin/structure/webform/manage/{webform}/third-party",
 *     "access-form" = "/admin/structure/webform/manage/{webform}/access",
 *     "handlers-form" = "/admin/structure/webform/manage/{webform}/handlers",
 *     "duplicate-form" = "/admin/structure/webform/manage/{webform}/duplicate",
 *     "delete-form" = "/admin/structure/webform/manage/{webform}/delete",
 *     "export-form" = "/admin/structure/webform/manage/{webform}/export",
 *     "results-submissions" = "/admin/structure/webform/manage/{webform}/results/submissions",
 *     "results-table" = "/admin/structure/webform/manage/{webform}/results/table",
 *     "results-export" = "/admin/structure/webform/manage/{webform}/results/download",
 *     "results-clear" = "/admin/structure/webform/manage/{webform}/results/clear",
 *     "collection" = "/admin/structure/webform",
 *   },
 *   config_export = {
 *     "status",
 *     "uid",
 *     "template",
 *     "id",
 *     "uuid",
 *     "title",
 *     "description",
 *     "elements",
 *     "css",
 *     "javascript",
 *     "settings",
 *     "access",
 *     "handlers",
 *     "third_party_settings",
 *   },
 * )
 */
class Webform extends ConfigEntityBundleBase implements WebformInterface {

  use StringTranslationTrait;

  /**
   * The webform ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The webform UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The webform status.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The webform template indicator.
   *
   * @var bool
   */
  protected $template = FALSE;

  /**
   * The webform title.
   *
   * @var string
   */
  protected $title;

  /**
   * The webform description.
   *
   * @var string
   */
  protected $description;

  /**
   * The owner's uid.
   *
   * @var int
   */
  protected $uid;

  /**
   * The webform settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The webform access controls.
   *
   * @var array
   */
  protected $access = [];

  /**
   * The webform elements.
   *
   * @var string
   */
  protected $elements;

  /**
   * The CSS stylesheet.
   *
   * @var string
   */
  protected $css = '';

  /**
   * The JavaScript.
   *
   * @var string
   */
  protected $javascript = '';

  /**
   * The array of webform handlers for this webform.
   *
   * @var array
   */
  protected $handlers = [];

  /**
   * Holds the collection of webform handlers that are used by this webform.
   *
   * @var \Drupal\webform\WebformHandlerPluginCollection
   */
  protected $handlersCollection;

  /**
   * The webform elements original.
   *
   * @var string
   */
  protected $elementsOriginal;

  /**
   * The webform elements decoded.
   *
   * @var array
   */
  protected $elementsDecoded;

  /**
   * The webform elements initializes (and decoded).
   *
   * @var array
   */
  protected $elementsInitialized;

  /**
   * The webform elements decoded and flattened.
   *
   * @var array
   */
  protected $elementsDecodedAndFlattened;

  /**
   * The webform elements initialized and flattened.
   *
   * @var array
   */
  protected $elementsInitializedAndFlattened;

  /**
   * The webform elements flattened and has value.
   *
   * @var array
   */
  protected $elementsFlattenedAndHasValue;

  /**
   * The webform elements translations.
   *
   * @var array
   */
  protected $elementsTranslations;

  /**
   * The webform pages.
   *
   * @var array
   */
  protected $pages;

  /**
   * Track if the webform has a managed file (upload) element.
   *
   * @var bool
   */
  protected $hasManagedFile = FALSE;

  /**
   * Track if the webform is using a flexbox layout.
   *
   * @var bool
   */
  protected $hasFlexboxLayout = FALSE;

  /**
   * Track if the webform has translations.
   *
   * @var bool
   */
  protected $hasTranslations;

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->uid ? User::load($this->uid) : NULL;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The owner user entity.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->uid = $account->id();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->uid;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->uid = ($uid) ? $uid : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen() {
    return $this->status ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isClosed() {
    return !$this->isOpen();
  }

  /**
   * {@inheritdoc}
   */
  public function isTemplate() {
    return $this->template ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfidential() {
    return $this->getSetting('form_confidential');
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubmissions() {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    return ($submission_storage->getTotal($this)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslations() {
    if (isset($this->hasTranslations)) {
      return $this->hasTranslations;
    }

    // Make sure the config translation module is enabled.
    if (!\Drupal::moduleHandler()->moduleExists('config_translation')) {
      $this->hasTranslations = FALSE;
      return $this->hasTranslations;
    }

    /** @var \Drupal\locale\LocaleConfigManager $local_config_manager */
    $local_config_manager = \Drupal::service('locale.config_manager');
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $langcode => $language) {
      if ($local_config_manager->hasTranslation('webform.webform.' . $this->id(), $langcode)) {
        $this->hasTranslations = TRUE;
        return $this->hasTranslations;
      }
    }

    $this->hasTranslations = FALSE;
    return $this->hasTranslations;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPage() {
    $settings = $this->getSettings();
    return $settings['page'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasManagedFile() {
    $this->initElements();
    return $this->hasManagedFile;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFlexboxLayout() {
    $this->initElements();
    return $this->hasFlexboxLayout;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCss() {
    return $this->css;
  }

  /**
   * {@inheritdoc}
   */
  public function setCss($css) {
    $this->css = $css;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJavaScript() {
    return $this->javascript;
  }

  /**
   * {@inheritdoc}
   */
  public function setJavaScript($javascript) {
    $this->javascript = $javascript;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings + self::getDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    // Always apply the default settings.
    $this->settings = self::getDefaultSettings();
    // Now apply custom settings.
    foreach ($settings as $name => $value) {
      $this->settings[$name] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    $settings = $this->getSettings();
    return (isset($settings[$key])) ? $settings[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $settings = $this->getSettings();
    $settings[$key] = $value;
    $this->setSettings($settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRules() {
    return $this->access;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessRules(array $access) {
    $this->access = $access;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    return [
      'page' => TRUE,
      'page_submit_path' => '',
      'page_confirm_path' => '',
      'form_submit_label' => '',
      'form_submit_attributes' => [],
      'form_exception_message' => '',
      'form_closed_message' => '',
      'form_confidential' => FALSE,
      'form_confidential_message' => '',
      'form_prepopulate' => FALSE,
      'form_prepopulate_source_entity' => FALSE,
      'form_novalidate' => FALSE,
      'form_unsaved' => FALSE,
      'form_disable_back' => FALSE,
      'form_autofocus' => FALSE,
      'form_details_toggle' => FALSE,
      'wizard_progress_bar' => TRUE,
      'wizard_progress_pages' => FALSE,
      'wizard_progress_percentage' => FALSE,
      'wizard_next_button_label' => '',
      'wizard_next_button_attributes' => [],
      'wizard_prev_button_label' => '',
      'wizard_prev_button_attributes' => [],
      'wizard_start_label' => '',
      'wizard_complete' => TRUE,
      'wizard_complete_label' => '',
      'preview' => DRUPAL_DISABLED,
      'preview_next_button_label' => '',
      'preview_next_button_attributes' => [],
      'preview_prev_button_label' => '',
      'preview_prev_button_attributes' => [],
      'preview_message' => '',
      'draft' => FALSE,
      'draft_auto_save' => FALSE,
      'draft_button_label' => '',
      'draft_button_attributes' => [],
      'draft_saved_message' => '',
      'draft_loaded_message' => '',
      'confirmation_type' => 'page',
      'confirmation_message' => '',
      'confirmation_url' => '',
      'confirmation_attributes' => [],
      'confirmation_back' => TRUE,
      'confirmation_back_label' => '',
      'confirmation_back_attributes' => [],
      'limit_total' => NULL,
      'limit_total_message' => '',
      'limit_user' => NULL,
      'limit_user_message' => '',
      'entity_limit_total' => NULL,
      'entity_limit_user' => NULL,
      'results_disabled' => FALSE,
      'results_disabled_ignore' => FALSE,
      'token_update' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultAccessRules() {
    return [
      'create' => [
        'roles' => [
          'anonymous',
          'authenticated',
        ],
        'users' => [],
      ],
      'view_any' => [
        'roles' => [],
        'users' => [],
      ],
      'update_any' => [
        'roles' => [],
        'users' => [],
      ],
      'delete_any' => [
        'roles' => [],
        'users' => [],
      ],
      'purge_any' => [
        'roles' => [],
        'users' => [],
      ],
      'view_own' => [
        'roles' => [],
        'users' => [],
      ],
      'update_own' => [
        'roles' => [],
        'users' => [],
      ],
      'delete_own' => [
        'roles' => [],
        'users' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, AccountInterface $account, WebformSubmissionInterface $webform_submission = NULL) {
    // Always grant access to "admin" which are webform and form
    // submission administrators.
    if ($account->hasPermission('administer webform') || $account->hasPermission('administer webform submission')) {
      return TRUE;
    }

    // The "page" operation is the same as "create" but requires that the
    // Webform is allowed to be displayed as dedicated page.
    // Used by the 'entity.webform.canonical' route.
    if ($operation == 'page') {
      if (empty($this->settings['page'])) {
        return FALSE;
      }
      else {
        $operation = 'create';
      }
    }
    $access_rules = $this->getAccessRules();

    if (isset($access_rules[$operation])
      && in_array($operation, ['create', 'view_any', 'update_any', 'delete_any', 'purge_any', 'view_own'])
      && $this->checkAccessRule($access_rules[$operation], $account)) {
      return TRUE;
    }
    elseif (isset($access_rules[$operation . '_any'])
      && $this->checkAccessRule($access_rules[$operation . '_any'], $account)) {
      return TRUE;
    }
    elseif (isset($access_rules[$operation . '_own'])
      && $account->isAuthenticated() && $webform_submission
      && $account->id() === $webform_submission->getOwnerId()
      && $this->checkAccessRule($access_rules[$operation . '_own'], $account)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Checks an access rule against a user account's roles and id.
   *
   * @param array $access_rule
   *   An access rule.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   The access result. Returns a TRUE if access is allowed.
   */
  protected function checkAccessRule(array $access_rule, AccountInterface $account) {
    if (!empty($access_rule['roles']) && array_intersect($access_rule['roles'], $account->getRoles())) {
      return TRUE;
    }
    elseif (!empty($access_rule['users']) && in_array($account->id(), $access_rule['users'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionForm(array $values = [], $operation = 'default') {
    // Set this webform's id.
    $values['webform_id'] = $this->id();

    $webform_submission = $this->entityTypeManager()
      ->getStorage('webform_submission')
      ->create($values);

    return \Drupal::service('entity.form_builder')
      ->getForm($webform_submission, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsRaw() {
    return $this->elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsOriginalRaw() {
    return $this->elementsOriginal;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDecoded() {
    $this->initElements();
    return $this->elementsDecoded;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsInitialized() {
    $this->initElements();
    return $this->elementsInitialized;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsInitializedAndFlattened() {
    $this->initElements();
    return $this->elementsInitializedAndFlattened;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDecodedAndFlattened() {
    $this->initElements();
    return $this->elementsDecodedAndFlattened;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsFlattenedAndHasValue() {
    $this->initElements();
    return $this->elementsFlattenedAndHasValue;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSelectorOptions() {
    /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $selectors = [];
    $elements = $this->getElementsInitializedAndFlattened();
    foreach ($elements as $element) {
      $element_handler = $element_manager->getElementInstance($element);
      $selectors += $element_handler->getElementSelectorOptions($element);
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function setElements(array $elements) {
    $this->elements = Yaml::encode($elements);
    $this->resetElements();
    return $this;
  }

  /**
   * Initialize parse webform elements.
   */
  protected function initElements() {
    if (isset($this->elementsInitialized)) {
      return;
    }

    $this->elementsDecodedAndFlattened = [];
    $this->elementsInitializedAndFlattened = [];
    $this->elementsFlattenedAndHasValue = [];
    $this->elementsTranslations = [];
    try {
      /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
      $translation_manager = \Drupal::service('webform.translation_manager');
      /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
      $language_manager = \Drupal::service('language_manager');

      // If current webform is translated, load the base (default) webform and apply
      // the translation to the elements.
      if ($this->langcode != $language_manager->getCurrentLanguage()->getId()) {
        $default_langcode = $language_manager->getDefaultLanguage()->getId();
        $elements = $translation_manager->getConfigElements($this, $default_langcode);
        $this->elementsTranslations = Yaml::decode($this->elements);
      }
      else {
        $elements = Yaml::decode($this->elements);
      }

      // Since YAML supports simple values.
      $elements = (is_array($elements)) ? $elements : [];
      $this->elementsDecoded = $elements;
    }
    catch (\Exception $exception) {
      $link = $this->link(t('Edit'), 'edit-form');
      \Drupal::logger('webform')
        ->notice('%title elements are not valid. @message', [
          '%title' => $this->label(),
          '@message' => $exception->getMessage(),
          'link' => $link,
        ]);
      $elements = FALSE;
    }

    if ($elements !== FALSE) {
      $this->initElementsRecursive($elements);
      $this->invokeHandlers('alterElements', $elements, $this);
    }

    $this->elementsInitialized = $elements;
  }

  /**
   * Reset parsed and cached webform elements.
   */
  protected function resetElements() {
    $this->elementsDecoded = NULL;
    $this->elementsInitialized = NULL;
    $this->elementsDecodedAndFlattened = NULL;
    $this->elementsInitializedAndFlattened = NULL;
    $this->elementsFlattenedAndHasValue = NULL;
    $this->elementsTranslations = NULL;
  }

  /**
   * Initialize webform elements into a flatten array.
   *
   * @param array $elements
   *   The webform elements.
   */
  protected function initElementsRecursive(array &$elements, $parent = '', $depth = 0) {
    /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    /** @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info */
    $element_info = \Drupal::service('plugin.manager.element_info');

    // Remove ignored properties.
    $elements = WebformElementHelper::removeIgnoredProperties($elements);

    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      // Apply translation to element.
      if (isset($this->elementsTranslations[$key])) {
        WebformElementHelper::applyTranslation($element, $this->elementsTranslations[$key]);
      }

      // Copy only the element properties to decoded and flattened elements.
      $this->elementsDecodedAndFlattened[$key] = WebformElementHelper::getProperties($element);

      // Set id, key, parent_key, depth, and parent children.
      $element['#webform_id'] = $this->id() . '--' . $key;
      $element['#webform_key'] = $key;
      $element['#webform_parent_key'] = $parent;
      $element['#webform_parent_flexbox'] = FALSE;
      $element['#webform_depth'] = $depth;
      $element['#webform_children'] = [];
      $element['#webform_multiple'] = FALSE;
      $element['#webform_composite'] = FALSE;

      if (!empty($parent)) {
        $parent_element = $this->elementsInitializedAndFlattened[$parent];
        // Add element to the parent element's children.
        $parent_element['#webform_children'][$key] = $key;
        // Set #parent_flexbox to TRUE is the parent element is a
        // 'webform_flexbox'.
        $element['#webform_parent_flexbox'] = (isset($parent_element['#type']) && $parent_element['#type'] == 'webform_flexbox') ? TRUE : FALSE;
      }

      // Set #title and #admin_title to NULL if it is not defined.
      $element += [
        '#title' => NULL,
        '#admin_title' => NULL,
      ];

      // If #private set #access.
      if (!empty($element['#private'])) {
        $element['#access'] = $this->access('submission_view_any');
      }

      $element_handler = NULL;
      if (isset($element['#type'])) {
        // Track managed file upload.
        if ($element['#type'] == 'managed_file') {
          $this->hasManagedFile = TRUE;
        }

        // Track flexbox.
        if ($element['#type'] == 'flexbox' || $element['#type'] == 'webform_flexbox') {
          $this->hasFlexboxLayout = TRUE;
        }

        // Set webform_* prefix to #type that are using alias without webform_
        // namespace.
        if (!$element_info->hasDefinition($element['#type']) && $element_info->hasDefinition('webform_' . $element['#type'])) {
          $element['#type'] = 'webform_' . $element['#type'];
        }

        // Load the element's handler.
        $element_handler = $element_manager->createInstance($element['#type']);

        // Initialize the element.
        $element_handler->initialize($element);

        $element['#webform_multiple'] = $element_handler->hasMultipleValues($element);
        $element['#webform_composite'] = $element_handler->isComposite();
      }

      // Copy only the element properties to initialized and flattened elements.
      $this->elementsInitializedAndFlattened[$key] = WebformElementHelper::getProperties($element);

      // Check if element has value (aka can be exported) and add it to
      // flattened has value array.
      if ($element_handler && $element_handler->isInput($element)) {
        $this->elementsFlattenedAndHasValue[$key] =& $this->elementsInitializedAndFlattened[$key];
      }

      $this->initElementsRecursive($element, $key, $depth + 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElement($key) {
    $elements_flattened = $this->getElementsInitializedAndFlattened();
    return (isset($elements_flattened[$key])) ? $elements_flattened[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementDecoded($key) {
    $elements = $this->getElementsDecodedAndFlattened();
    return (isset($elements[$key])) ? $elements[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementInitialized($key) {
    $elements = $this->getElementsInitializedAndFlattened();
    return (isset($elements[$key])) ? $elements[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setElementProperties($key, array $properties, $parent_key = '') {
    $elements = $this->getElementsDecoded();
    // If element is was not added to elements, add it as the last element.
    if (!$this->setElementPropertiesRecursive($elements, $key, $properties, $parent_key)) {
      $elements[$key] = $properties;
    }
    $this->setElements($elements);
    return $this;
  }

  /**
   * Set element properties.
   *
   * @param array $elements
   *   An associative nested array of elements.
   * @param string $key
   *   The element's key.
   * @param array $properties
   *   An associative array of properties.
   * @param string $parent_key
   *   (optional) The element's parent key. Only used for new elements.
   *
   * @return bool
   *   TRUE when the element's properties has been set. FALSE when the element
   *   has not been found.
   */
  protected function setElementPropertiesRecursive(array &$elements, $key, array $properties, $parent_key = '') {
    foreach ($elements as $element_key => &$element) {
      if (Element::property($element_key) || !is_array($element)) {
        continue;
      }

      if ($element_key == $key) {
        $element = $properties + WebformElementHelper::removeProperties($element);
        return TRUE;
      }

      if ($element_key == $parent_key) {
        $element[$key] = $properties;
        return TRUE;
      }

      if ($this->setElementPropertiesRecursive($element, $key, $properties, $parent_key)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key) {
    // Delete element from the elements render array.
    $elements = $this->getElementsDecoded();
    $sub_element_keys = $this->deleteElementRecursive($elements, $key);
    $this->setElements($elements);

    // Delete submission element key data.
    \Drupal::database()->delete('webform_submission_data')
      ->condition('webform_id', $this->id())
      ->condition('name', $sub_element_keys, 'IN')
      ->execute();
  }

  /**
   * Remove an element by key from a render array.
   *
   * @param array $elements
   *   An associative nested array of elements.
   * @param string $key
   *   The element's key.
   *
   * @return bool|array
   *   An array containing the deleted element and sub element keys.
   *   FALSE is no sub elements are found.
   */
  protected function deleteElementRecursive(array &$elements, $key) {
    foreach ($elements as $element_key => &$element) {
      if (Element::property($element_key) || !is_array($element)) {
        continue;
      }

      if ($element_key == $key) {
        $sub_element_keys = [$element_key => $element_key];
        $this->collectSubElementKeysRecursive($sub_element_keys, $element);
        unset($elements[$element_key]);
        return $sub_element_keys;
      }

      if ($sub_element_keys = $this->deleteElementRecursive($element, $key)) {
        return $sub_element_keys;
      }
    }

    return FALSE;
  }

  /**
   * Collect sub element keys from a render array.
   *
   * @param array $sub_element_keys
   *   An array to be populated with sub element keys.
   * @param array $elements
   *   A render array.
   */
  protected function collectSubElementKeysRecursive(array &$sub_element_keys, array $elements) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      $sub_element_keys[$key] = $key;
      $this->collectSubElementKeysRecursive($sub_element_keys, $element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPages() {
    if (isset($this->pages)) {
      return $this->pages;
    }

    $wizard_properties = [
      '#title' => '#title',
      '#prev_button_label' => '#prev_button_label',
      '#next_button_label' => '#next_button_label',
    ];

    $elements = $this->getElementsInitialized();

    // Add webform page containers.
    $this->pages = [];
    if (is_array($elements)) {
      foreach ($elements as $key => $element) {
        if (isset($element['#type']) && $element['#type'] == 'webform_wizard_page') {
          $this->pages[$key] = array_intersect_key($element, $wizard_properties);
        }
      }
    }

    // Add preview page.
    $settings = $this->getSettings();
    if ($settings['preview'] != DRUPAL_DISABLED) {
      // If there is no start page, we must define one.
      if (empty($this->pages)) {
        $this->pages['start'] = [
          '#title' => $this->getSetting('wizard_start_label') ?: \Drupal::config('webform.settings')->get('settings.default_wizard_start_label'),
        ];
      }
      $this->pages['preview'] = [
        '#title' => $this->t('Preview'),
      ];
    }

    // Only add complete page, if there are some pages.
    if ($this->pages && $this->getSetting('wizard_complete')) {
      $this->pages['complete'] = [
        '#title' => $this->getSetting('wizard_complete_label') ?: \Drupal::config('webform.settings')->get('settings.default_wizard_complete_label'),
      ];
    }

    return $this->pages;
  }

  /**
   * {@inheritdoc}
   */
  public function getPage($key) {
    $pages = $this->getPages();
    return (isset($pages[$key])) ? $pages[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'uid' => \Drupal::currentUser()->id(),
      'settings' => self::getDefaultSettings(),
      'access' => self::getDefaultAccessRules(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    foreach ($entities as $entity) {
      $entity->elementsOriginal = $entity->elements;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\webform\WebformInterface[] $entities */
    parent::preDelete($storage, $entities);

    // Delete all paths and states associated with this webform.
    foreach ($entities as $entity) {
      // Delete all paths.
      $entity->deletePaths();

      // Delete the state.
      \Drupal::state()->delete('webform.webform.' . $entity->id());
    }

    // Delete all submission associated with this webform.
    $submission_ids = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', array_keys($entities), 'IN')
      ->sort('sid')
      ->execute();
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $submissions = $submission_storage->loadMultiple($submission_ids);
    $submission_storage->delete($submissions);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // Add webform to cache tags which are used by the WebformSubmissionForm.
    $cache_tags[] = 'webform:' . $this->id();
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Always unpublish templates.
    if ($this->isTemplate()) {
      $this->setStatus(FALSE);
    }

    // Serialize elements array to YAML.
    if (is_array($this->elements)) {
      $this->elements = Yaml::encode($this->elements);
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update paths.
    $this->updatePaths();

    // Reset elements.
    $this->resetElements();
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaths() {
    // Path module must be enable for URL aliases to be updated.
    if (!\Drupal::moduleHandler()->moduleExists('path')) {
      return;
    }

    // Update submit path.
    $submit_path = $this->settings['page_submit_path'] ?: trim(\Drupal::config('webform.settings')->get('settings.default_page_base_path'), '/') . '/' . str_replace('_', '-', $this->id());
    $submit_source = '/webform/' . $this->id();
    $submit_alias = '/' . trim($submit_path, '/');
    $this->updatePath($submit_source, $submit_alias, $this->langcode);
    $this->updatePath($submit_source, $submit_alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);

    // Update confirm path.
    $confirm_path = $this->settings['page_confirm_path'] ?:  $submit_path . '/confirmation';
    $confirm_source = '/webform/' . $this->id() . '/confirmation';
    $confirm_alias = '/' . trim($confirm_path, '/');
    $this->updatePath($confirm_source, $confirm_alias, $this->langcode);
    $this->updatePath($confirm_source, $confirm_alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);

    // Update submissions path.
    $submissions_path = $submit_path . '/submissions';
    $submissions_source = '/webform/' . $this->id() . '/submissions';
    $submissions_alias = '/' . trim($submissions_path, '/');
    $this->updatePath($submissions_source, $submissions_alias, $this->langcode);
    $this->updatePath($submissions_source, $submissions_alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaths() {
    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = \Drupal::service('path.alias_storage');
    $path_alias_storage->delete(['source' => '/webform/' . $this->id()]);
    $path_alias_storage->delete(['source' => '/webform/' . $this->id() . '/confirmation']);
  }

  /**
   * Saves a path alias to the database.
   *
   * @param string $source
   *   The internal system path.
   * @param string $alias
   *   The URL alias.
   * @param string $langcode
   *   (optional) The language code of the alias.
   */
  protected function updatePath($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = \Drupal::service('path.alias_storage');

    $path = $path_alias_storage->load(['source' => $source, 'langcode' => $langcode]);

    // Check if the path alias is already setup.
    if ($path && ($path['alias'] == $alias)) {
      return;
    }

    $path_alias_storage->save($source, $alias, $langcode, $path['pid']);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWebformHandler(WebformHandlerInterface $handler) {
    $this->getHandlers()->removeInstanceId($handler->getHandlerId());
    $this->save();
    return $this;
  }

  /**
   * Returns the webform handler plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The webform handler plugin manager.
   */
  protected function getWebformHandlerPluginManager() {
    return \Drupal::service('plugin.manager.webform.handler');
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($handler) {
    return $this->getHandlers()->get($handler);
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlers($plugin_id = NULL, $status = NULL, $results = NULL) {
    if (!$this->handlersCollection) {
      $this->handlersCollection = new WebformHandlerPluginCollection($this->getWebformHandlerPluginManager(), $this->handlers);
      /** @var \Drupal\webform\WebformHandlerBase $handler */
      foreach ($this->handlersCollection as $handler) {
        // Initialize the handler and pass in the webform.
        $handler->init($this);
      }
      $this->handlersCollection->sort();
    }

    /** @var \Drupal\webform\WebformHandlerPluginCollection $handlers */
    $handlers = $this->handlersCollection;

    // Clone the handlers if they are being filtered.
    if (isset($plugin_id) || isset($status) || isset($results)) {
      /** @var \Drupal\webform\WebformHandlerPluginCollection $handlers */
      $handlers = clone $this->handlersCollection;
    }

    // Filter the handlers by plugin id.
    // This is used to limit track and enforce a handlers cardinality.
    if (isset($plugin_id)) {
      foreach ($handlers as $instance_id => $handler) {
        if ($handler->getPluginId() != $plugin_id) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the handlers by status.
    // This is used to limit track and enforce a handlers cardinality.
    if (isset($status)) {
      foreach ($handlers as $instance_id => $handler) {
        if ($handler->getStatus() != $status) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the handlers by results.
    // This is used to track is results are processed or ignored.
    if (isset($results)) {
      foreach ($handlers as $instance_id => $handler) {
        $plugin_definition = $handler->getPluginDefinition();
        if ($plugin_definition['results'] != $results) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    return $handlers;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['handlers' => $this->getHandlers()];
  }

  /**
   * {@inheritdoc}
   */
  public function addWebformHandler(array $configuration) {
    $this->getHandlers()->addInstanceId($configuration['handler_id'], $configuration);
    return $configuration['handler_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function invokeHandlers($method, &$data, &$context1 = NULL, &$context2 = NULL) {
    $handlers = $this->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler->isEnabled()) {
        $handler->$method($data, $context1, $context2);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeElements($method, &$data, &$context1 = NULL, &$context2 = NULL) {
    /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $elements = $this->getElementsInitializedAndFlattened();
    foreach ($elements as $element) {
      $element_manager->invokeMethod($method, $element, $data, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that URLs pointing to webform default to 'canonical'
   * submission webform and not the back-end 'edit-form'.
   */
  public function url($rel = 'canonical', $options = []) {
    // Do not remove this override: the default value of $rel is different.
    return parent::url($rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that URLs pointing to webform default to 'canonical'
   * submission webform and not the back-end 'edit-form'.
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that URLs pointing to webform default to 'canonical'
   * submission webform and not the back-end 'edit-form'.
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    return parent::urlInfo($rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that links to webform default to 'canonical' submission
   * webform and not the back-end 'edit-form'.
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    return parent::toLink($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that links to webform default to 'canonical' submission
   * webform and not the back-end 'edit-form'.
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    return parent::link($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($key, $default = NULL) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    return (isset($values[$key])) ? $values[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    $values[$key] = $value;
    \Drupal::state()->set($namespace, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteState($key) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    unset($values[$key]);
    \Drupal::state()->set($namespace, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasState($key) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    return (isset($values[$key])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    $handlers = $this->getHandlers();
    if (empty($handlers)) {
      return $changed;
    }

    foreach ($handlers as $handler) {
      $plugin_definition = $handler->getPluginDefinition();
      $provider = $plugin_definition['provider'];
      if (in_array($provider, $dependencies['module'])) {
        $this->deleteWebformHandler($handler);
        $changed = TRUE;
      }
    }
    return $changed;
  }

  /**
   * Define empty array iterator.
   *
   * See: Issue #2759267: Undefined method Webform::getIterator().
   */
  public function getIterator() {
    return new \ArrayIterator([]);
  }

}
