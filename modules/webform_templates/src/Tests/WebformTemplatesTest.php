<?php

namespace Drupal\webform_templates\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform submission webform settings.
 *
 * @group WebformTemplates
 */
class WebformTemplatesTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'block', 'node', 'user', 'webform', 'webform_test', 'webform_templates'];

  /**
   * Tests webform template setting.
   */
  public function testSettings() {
    $template_webform = Webform::load('test_form_template');

    // Check the templates always will remain closed.
    $this->assertTrue($template_webform->isClosed());
    $template_webform->setStatus(TRUE)->save();
    $this->assertTrue($template_webform->isClosed());

    // Login the own user.
    $this->drupalLogin($this->ownFormUser);

    // Check template is included in the 'Templates' list display.
    $this->drupalGet('admin/structure/webform/templates');
    $this->assertRaw('Test: Webform: Template');
    $this->assertRaw('Test using a webform as a template.');

    // Check template is accessible to user with create webform access.
    $this->drupalGet('webform/test_form_template');
    $this->assertResponse(200);
    $this->assertRaw('You are previewing the below template,');

    // Login the admin user.
    $this->drupalLogin($this->adminFormUser);
  }

}
