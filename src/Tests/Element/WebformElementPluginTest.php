<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for the webform element plugin.
 *
 * @group Webform
 */
class WebformElementPluginTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_element'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_plugin_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform element plugin.
   */
  public function testWebformElement() {
    $this->drupalLogin($this->adminWebformUser);

    // Get the webform test element.
    $webform_plugin_test = Webform::load('test_element_plugin_test');

    // Check prepare and setDefaultValue().
    $this->drupalGet('webform/test_element_plugin_test');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:preCreate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postCreate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:prepare');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:setDefaultValue');

    // Check save.
    $sid = $this->postSubmission($webform_plugin_test);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:preCreate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:prepare');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:setDefaultValue');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest::validate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:preSave');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postSave insert');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postLoad');

    // Check update.
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_plugin_test/submission/' . $sid . '/edit', [], t('Save'));
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postLoad');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:prepare');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:setDefaultValue');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest::validate');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:preSave');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postSave update');

    // Check HTML.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin_test/submission/' . $sid);
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postLoad');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:formatHtml');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:formatText');

    // Check plain text.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin_test/submission/' . $sid . '/text');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postLoad');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:formatText');

    // Check delete.
    $this->drupalPostForm('/admin/structure/webform/manage/test_element_plugin_test/submission/' . $sid . '/delete', [], t('Delete'));
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:preDelete');
    $this->assertRaw('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTest:postDelete');
    $this->assertRaw('Test: Element: Test (plugin): Submission #' . $webform_submission->serial() . ' has been deleted.');
  }

}
