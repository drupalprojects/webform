<?php

namespace Drupal\webform\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\filter\Entity\FilterFormat;
use Drupal\webform\WebformInterface;

/**
 * Defines webform test trait.
 */
trait WebformTestTrait {

  /****************************************************************************/
  // User.
  /****************************************************************************/

  /**
   * A normal user to submit webforms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * A webform administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminWebformUser;

  /**
   * A webform submission administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminSubmissionUser;

  /**
   * A webform own access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $ownWebformUser;

  /**
   * A webform any access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anyWebformUser;

  /**
   * Create webform test users.
   */
  protected function createUsers() {
    // Default user permissions.
    $default_user_permissions = [];
    $default_user_permissions[] = 'access user profiles';
    if (in_array('webform_node', self::$modules)) {
      $default_user_permissions[] = 'access content';
    }

    // Normal user.
    $normal_user_permissions = $default_user_permissions;
    $this->normalUser = $this->drupalCreateUser($normal_user_permissions);

    // Admin webform user.
    $admin_form_user_permissions = array_merge($default_user_permissions, [
      'administer webform',
      'create webform',
      'administer users',
    ]);
    if (in_array('block', self::$modules)) {
      $admin_form_user_permissions[] = 'administer blocks';
    }
    if (in_array('webform_node', self::$modules)) {
      $admin_form_user_permissions[] = 'administer nodes';
    }
    if (in_array('webform_test_translation', self::$modules)) {
      $admin_form_user_permissions[] = 'translate configuration';
    }
    $this->adminWebformUser = $this->drupalCreateUser($admin_form_user_permissions);

    // Own webform user.
    $this->ownWebformUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'access webform overview',
      'create webform',
      'edit own webform',
      'delete own webform',
    ]));

    // Any webform user.
    $this->anyWebformUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'access webform overview',
      'create webform',
      'edit any webform',
      'delete any webform',
    ]));

    // Admin submission user.
    $this->adminSubmissionUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'administer webform submission',
    ]));
  }

  /****************************************************************************/
  // Block.
  /****************************************************************************/

  /**
   * Place breadcrumb page, tasks, and actions.
   */
  protected function placeBlocks() {
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /****************************************************************************/
  // Filter.
  /****************************************************************************/

  /**
   * Basic HTML filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $basicHtmlFilter;

  /**
   * Full HTML filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $fullHtmlFilter;

  /**
   * Create basic HTML filter format.
   */
  protected function createFilters() {
    $this->basicHtmlFilter = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em>',
          ],
        ],
      ],
    ]);
    $this->basicHtmlFilter->save();

    $this->fullHtmlFilter = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $this->fullHtmlFilter->save();
  }

  /****************************************************************************/
  // Submission.
  /****************************************************************************/

  /**
   * Load the specified webform submission from the storage.
   *
   * @param int $sid
   *   The submission identifier.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   The loaded webform submission.
   */
  protected function loadSubmission($sid) {
    /** @var \Drupal\webform\WebformSubmissionStorage $storage */
    $storage = $this->container->get('entity.manager')->getStorage('webform_submission');
    $storage->resetCache([$sid]);
    return $storage->load($sid);
  }

  /**
   * Purge all submission before the webform.module is uninstalled.
   */
  protected function purgeSubmissions() {
    db_query('DELETE FROM {webform_submission}');
  }

  /**
   * Post a new submission to a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   *
   * @return int
   *   The created submission's sid.
   */
  protected function postSubmission(WebformInterface $webform, array $edit = [], $submit = NULL) {
    $submit = $submit ?: t('Submit');
    $this->drupalPostForm('webform/' . $webform->id(), $edit, $submit);
    return $this->getLastSubmissionId($webform);
  }

  /**
   * Post a new test submission to a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   *
   * @return int
   *   The created test submission's sid.
   */
  protected function postSubmissionTest(WebformInterface $webform, array $edit = [], $submit = NULL) {
    $submit = $submit ?: t('Submit');
    $this->drupalPostForm('webform/' . $webform->id() . '/test', $edit, $submit);
    return $this->getLastSubmissionId($webform);
  }

  /**
   * Get the last submission id.
   *
   * @return int
   *   The last submission id.
   */
  protected function getLastSubmissionId($webform) {
    // Get submission sid.
    $url = UrlHelper::parse($this->getUrl());
    if (isset($url['query']['sid'])) {
      return $url['query']['sid'];
    }
    else {
      $entity_ids = \Drupal::entityQuery('webform_submission')
        ->sort('sid', 'DESC')
        ->condition('webform_id', $webform->id())
        ->execute();
      return reset($entity_ids);
    }
  }

  /****************************************************************************/
  // Export.
  /****************************************************************************/

  /**
   * Request a webform results export CSV.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $options
   *   An associative array of export options.
   */
  protected function getExport(WebformInterface $webform, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionExporterInterface $exporter */
    $exporter = \Drupal::service('webform_submission.exporter');
    $options += $exporter->getDefaultExportOptions();
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/download', ['query' => $options]);
  }

  /**
   * Get webform export columns.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An array of exportable columns.
   */
  protected function getExportColumns(WebformInterface $webform) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $columns = array_merge(
      array_keys($submission_storage->getFieldDefinitions()),
      array_keys($webform->getElementsInitializedAndFlattened())
    );
    return array_combine($columns, $columns);
  }

  /****************************************************************************/
  // Email.
  /****************************************************************************/

  /**
   * Gets that last email sent during the currently running test case.
   *
   * @return array
   *   An array containing the last email message captured during the
   *   current test.
   */
  protected function getLastEmail() {
    $sent_emails = $this->drupalGetMails();
    $sent_email = end($sent_emails);
    $this->debug($sent_email);
    return $sent_email;
  }

  /****************************************************************************/
  // Assert.
  /****************************************************************************/

  /**
   * Passes if the substring is contained within text, fails otherwise.
   */
  protected function assertContains($haystack, $needle, $message = '', $group = 'Other') {
    if (!$message) {
      $t_args = [
        '@haystack' => Unicode::truncate($haystack, 150, TRUE, TRUE),
        '@needle' => $needle,
      ];
      $message = new FormattableMarkup('"@needle" found', $t_args);
    }
    $result = (strpos($haystack, $needle) !== FALSE);
    if (!$result) {
      $this->verbose($haystack);
    }
    return $this->assert($result, $message, $group);
  }

  /**
   * Passes if the substring is not contained within text, fails otherwise.
   */
  protected function assertNotContains($haystack, $needle, $message = '', $group = 'Other') {
    if (!$message) {
      $t_args = [
        '@haystack' => Unicode::truncate($haystack, 150, TRUE, TRUE),
        '@needle' => $needle,
      ];

      $message = new FormattableMarkup('"@needle" not found', $t_args);
    }
    $result = (strpos($haystack, $needle) === FALSE);
    if (!$result) {
      $this->verbose($haystack);
    }
    return $this->assert($result, $message, $group);
  }

  /**
   * Passes if the CSS selector IS found on the loaded page, fail otherwise.
   */
  protected function assertCssSelect($selector, $message = '') {
    $element = $this->cssSelect($selector);
    if (!$message) {
      $message = new FormattableMarkup('Found @selector', ['@selector' => $selector]);
    }
    $this->assertTrue(!empty($element), $message);
  }

  /**
   * Passes if the CSS selector IS NOT found on the loaded page, fail otherwise.
   */
  protected function assertNoCssSelect($selector, $message = '') {
    $element = $this->cssSelect($selector);
    $this->assertTrue(empty($element), $message);
  }

  /****************************************************************************/
  // Debug.
  /****************************************************************************/

  /**
   * Logs verbose (debug) message in a text file.
   *
   * @param mixed $data
   *   Data to be output.
   */
  protected function debug($data) {
    $string = var_export($data, TRUE);
    $string = preg_replace('/=>\s*array\s*\(/', '=> array(', $string);
    $this->verbose('<pre>' . htmlentities($string) . '</pre>');
  }

}
