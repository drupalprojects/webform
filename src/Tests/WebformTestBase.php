<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines an abstract test base for webform tests.
 */
abstract class WebformTestBase extends WebTestBase {

  use WebformTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'block', 'node', 'user', 'webform', 'webform_test'];

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Storage.
    $this->submissionStorage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    // Set page.front (aka <front>) to /node instead of /user/login.
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node')->save();

    // Place blocks.
    $this->placeBlocks();

    // Create users.
    $this->createUsers();

    // Login the normal user.
    $this->drupalLogin($this->normalUser);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->purgeSubmissions();
    parent::tearDown();
  }

}
