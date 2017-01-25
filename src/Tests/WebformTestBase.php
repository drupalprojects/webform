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
  protected static $modules = ['webform', 'webform_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->purgeSubmissions();
    parent::tearDown();
  }

}
