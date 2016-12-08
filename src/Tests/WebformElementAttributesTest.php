<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for webform element attributes.
 *
 * @group Webform
 */
class WebformElementAttributesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'webform', 'webform_test'];

  /**
   * Tests element attributes.
   */
  public function test() {
    // Check default value handling.
    $this->drupalPostForm('webform/test_element_attributes', [], t('Submit'));
    $this->assertRaw("webform_element_attributes:
  class:
    - one
    - two
    - four
  style: 'color: red'
  custom: test");
  }

}
