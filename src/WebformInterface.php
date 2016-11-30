<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a webform entity.
 */
interface WebformInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface, EntityOwnerInterface {

  /**
   * Determine if the webform has page or is attached to other entities.
   *
   * @return bool
   *   TRUE if the webform is a page with dedicated path.
   */
  public function hasPage();

  /**
   * Determine if the webform's elements include a managed_file upload element.
   *
   * @return bool
   *   TRUE if the webform's elements include a managed_file upload element.
   */
  public function hasManagedFile();

  /**
   * Determine if the webform is using a Flexbox layout.
   *
   * @return bool
   *   TRUE if if the webform is using a Flexbox layout.
   */
  public function hasFlexboxLayout();

  /**
   * Returns the webform opened status indicator.
   *
   * @return bool
   *   TRUE if the webform is open to new submissions.
   */
  public function isOpen();

  /**
   * Returns the webform closed status indicator.
   *
   * @return bool
   *   TRUE if the webform is closed to new submissions.
   */
  public function isClosed();

  /**
   * Returns the webform template indicator.
   *
   * @return bool
   *   TRUE if the webform is a template and available for duplication.
   */
  public function isTemplate();

  /**
   * Returns the webform confidential indicator.
   *
   * @return bool
   *   TRUE if the webform is confidential .
   */
  public function isConfidential();

  /**
   * Checks if a webform has submissions.
   *
   * @return bool
   *   TRUE if the webform has submissions.
   */
  public function hasSubmissions();

  /**
   * Determine if the current webform is translated.
   *
   * @return bool
   *   TRUE if the current webform is translated.
   */
  public function hasTranslations();

  /**
   * Returns the webform's description.
   *
   * @return string
   *   A webform's description.
   */
  public function getDescription();

  /**
   * Sets a webform's description.
   *
   * @param string $description
   *   A description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the webform's CSS.
   *
   * @return string
   *   The webform's CSS.
   */
  public function getCss();

  /**
   * Sets the webform's CSS.
   *
   * @param string $css
   *   The webform's CSS.
   *
   * @return $this
   */
  public function setCss($css);

  /**
   * Returns the webform's JavaScript.
   *
   * @return string
   *   The webform's CSS.
   */
  public function getJavaScript();

  /**
   * Sets the webform's JavaScript.
   *
   * @param string $javascript
   *   The webform's JavaScript.
   *
   * @return $this
   */
  public function setJavaScript($javascript);

  /**
   * Returns the webform settings.
   *
   * @return array
   *   A structured array containing all the webform settings.
   */
  public function getSettings();

  /**
   * Sets the webform settings.
   *
   * @param array $settings
   *   The structured array containing all the webform setting.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Returns the webform settings for a given key.
   *
   * @param string $key
   *   The key of the setting to retrieve.
   *
   * @return mixed
   *   The settings value, or NULL if no settings exists.
   */
  public function getSetting($key);

  /**
   * Saves a webform setting for a given key.
   *
   * @param string $key
   *   The key of the setting to store.
   * @param mixed $value
   *   The data to store.
   *
   * @return $this
   */
  public function setSetting($key, $value);

  /**
   * Returns the webform access controls.
   *
   * @return array
   *   A structured array containing all the webform access controls.
   */
  public function getAccessRules();

  /**
   * Sets the webform access.
   *
   * @param array $access
   *   The structured array containing all the webform access controls.
   *
   * @return $this
   */
  public function setAccessRules(array $access);

  /**
   * Returns the webform default settings.
   *
   * @return array
   *   A structured array containing all the webform default settings.
   */
  public static function getDefaultSettings();

  /**
   * Returns the webform default access controls.
   *
   * @return array
   *   A structured array containing all the webform default access controls.
   */
  public static function getDefaultAccessRules();

  /**
   * Checks webform access to an operation on a webform's submission.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually "create", "view", "update", "delete", "purge", or "admin".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   (optional) A webform submission.
   *
   * @return bool
   *   The access result. Returns a TRUE if access is allowed.
   */
  public function checkAccessRules($operation, AccountInterface $account, WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Get webform submission webform.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   * @param string $operation
   *   (optional) The operation identifying the webform variation to be returned.
   *   Defaults to 'default'. This is typically used in routing.
   *
   * @return array
   *   A render array representing a webform submission webform.
   */
  public function getSubmissionForm(array $values = [], $operation = 'default');

  /**
   * Get elements (YAML) value.
   *
   * @return string
   *   The elements raw value.
   */
  public function getElementsRaw();

  /**
   * Get original elements (YAML) value.
   *
   * @return string|null
   *   The original elements' raw value. Original elements is NULL for new YAML
   *   webforms.
   */
  public function getElementsOriginalRaw();

  /**
   * Get webform elements decoded as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE is elements YAML is invalid.
   */
  public function getElementsDecoded();

  /**
   * Set element properties.
   *
   * @param string $key
   *   The element's key.
   * @param array $properties
   *   An associative array of properties.
   * @param string $parent_key
   *   (optional) The element's parent key. Only used for new elements.
   *
   * @return $this
   */
  public function setElementProperties($key, array $properties, $parent_key = '');

  /**
   * Remove an element.
   *
   * @param string $key
   *   The element's key.
   */
  public function deleteElement($key);

  /**
   * Get webform elements initialized as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE is elements YAML is invalid.
   */
  public function getElementsInitialized();

  /**
   * Get webform raw elements decoded and flattened into an associative array.
   *
   * @return array
   *   Webform raw elements decoded and flattened into an associative array
   *   keyed by element name. Returns FALSE is elements YAML is invalid.
   */
  public function getElementsDecodedAndFlattened();

  /**
   * Get webform elements initialized and flattened into an associative array.
   *
   * @return array
   *   Webform elements flattened into an associative array keyed by element name.
   *   Returns FALSE is elements YAML is invalid.
   */
  public function getElementsInitializedAndFlattened();

  /**
   * Get webform flattened list of elements.
   *
   * @return array
   *   Webform elements flattened into an associative array keyed by element name.
   */
  public function getElementsFlattenedAndHasValue();

  /**
   * Get webform elements selectors as options.
   *
   * @return array
   *   Webform elements selectors as options.
   */
  public function getElementsSelectorOptions();

  /**
   * Sets elements (YAML) value.
   *
   * @param array $elements
   *   An renderable array of elements.
   *
   * @return $this
   */
  public function setElements(array $elements);

  /**
   * Get a webform's initialized element.
   *
   * @param string $key
   *   The element's key.
   *
   * @return array|null
   *   An associative array containing an initialized element.
   */
  public function getElement($key);

  /**
   * Get a webform's raw (uninitialized) element.
   *
   * @param string $key
   *   The element's key.
   *
   * @return array|null
   *   An associative array containing an raw (uninitialized) element.
   */
  public function getElementDecoded($key);

  /**
   * Get webform wizard pages.
   *
   * @return array
   *   An associative array of webform pages.
   */
  public function getPages();

  /**
   * Get webform wizard page.
   *
   * @param string|int $key
   *   The name/key of a webform wizard page.
   *
   * @return array|null
   *   A webform wizard page element.
   */
  public function getPage($key);

  /**
   * Update submit and confirm paths (ie URL aliases) associated with this webform.
   */
  public function updatePaths();

  /**
   * Update submit and confirm paths associated with this webform.
   */
  public function deletePaths();

  /**
   * Returns a specific webform handler.
   *
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return \Drupal\webform\WebformHandlerInterface
   *   The webform handler object.
   */
  public function getHandler($handler_id);

  /**
   * Returns the webform handlers for this webform.
   *
   * @param string $plugin_id
   *   (optional) Plugin id used to return specific plugin instances
   *   (ie handlers).
   * @param bool $status
   *   (optional) Status used to return enabled or disabled plugin instances
   *   (ie handlers).
   * @param int $results
   *   (optional) Value indicating if webform submissions are saved to internal or
   *   external system.
   *
   * @return \Drupal\webform\WebformHandlerPluginCollection|\Drupal\webform\WebformHandlerInterface[]
   *   The webform handler plugin collection.
   */
  public function getHandlers($plugin_id = NULL, $status = NULL, $results = NULL);

  /**
   * Saves a webform handler for this webform.
   *
   * @param array $configuration
   *   An array of webform handler configuration.
   *
   * @return string
   *   The webform handler ID.
   */
  public function addWebformHandler(array $configuration);

  /**
   * Deletes a webform handler from this style.
   *
   * @param \Drupal\webform\WebformHandlerInterface $effect
   *   The webform handler object.
   *
   * @return $this
   */
  public function deleteWebformHandler(WebformHandlerInterface $effect);

  /**
   * Invoke a handlers method.
   *
   * @param string $method
   *   The handle method to be invoked.
   * @param mixed $data
   *   The argument to passed by reference to the handler method.
   */
  public function invokeHandlers($method, &$data, &$context1 = NULL, &$context2 = NULL);

  /**
   * Invoke elements method.
   *
   * @param string $method
   *   The handle method to be invoked.
   * @param mixed $data
   *   The argument to passed by reference to the handler method.
   */
  public function invokeElements($method, &$data, &$context1 = NULL, &$context2 = NULL);

  /**
   * Required to allow webform which are config entities to have an EntityViewBuilder.
   *
   * Prevents:
   *   Fatal error: Call to undefined method
   *   Drupal\webform\Entity\Webform::isDefaultRevision()
   *   in /private/var/www/sites/d8_dev/core/lib/Drupal/Core/Entity/EntityViewBuilder.php
   *   on line 169
   *
   * @see \Drupal\Core\Entity\RevisionableInterface::isDefaultRevision()
   *
   * @return bool
   *   Always return TRUE since config entities are not revisionable.
   */
  public function isDefaultRevision();

  /**
   * Returns the stored value for a given key in the webform's state.
   *
   * @param string $key
   *   The key of the data to retrieve.
   * @param mixed $default
   *   The default value to use if the key is not found.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function getState($key, $default = NULL);

  /**
   * Saves a value for a given key in the webform's state.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function setState($key, $value);

  /**
   * Deletes an item from the webform's state.
   *
   * @param string $key
   *   The item name to delete.
   */
  public function deleteState($key);

  /**
   * Determine if the stored value for a given key exists in the webform's state.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return bool
   *   TRUE if the  stored value for a given key exists
   */
  public function hasState($key);

}
