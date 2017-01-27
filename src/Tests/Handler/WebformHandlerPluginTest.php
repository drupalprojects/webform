<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\simpletest\WebTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform handler plugin.
 *
 * @group Webform
 */
class WebformHandlerPluginTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_handler'];

  /**
   * Tests webform element plugin.
   */
  public function testWebformHandler() {
    $webform = Webform::load('contact');

    // Check initial dependencies.
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform']]);

    // Add 'test' handler provided by the webform_test.module.
    $webform_handler_configuration = [
      'id' => 'test',
      'label' => 'test',
      'handler_id' => 'test',
      'status' => 1,
      'weight' => 2,
      'settings' => [],
    ];
    $webform->addWebformHandler($webform_handler_configuration);
    $webform->save();

    // Check that handler has been added to the dependencies.
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform_test_handler', 'webform']]);

    // Uninstall the webform_test.module which will also remove the
    // debug handler.
    $this->container->get('module_installer')->uninstall(['webform_test_handler']);
    $webform = Webform::load('contact');

    // Check that handler was removed from the dependencies.
    $this->assertNotEqual($webform->getDependencies(), ['module' => ['webform_test_handler', 'webform']]);
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform']]);
  }

}
