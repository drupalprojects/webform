<?php

namespace Drupal\webform\Tests;

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
  protected static $modules = ['webform', 'webform_devel'];

  /**
   * Tests webform element plugin.
   */
  public function testWebformHandler() {
    $webform = Webform::load('contact');

    // Check initial dependencies.
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform']]);

    // Add 'debug' handler provided by the webform_devel.module.
    $webform_handler_configuration = [
      'id' => 'debug',
      'label' => 'Debug',
      'handler_id' => 'debug',
      'status' => 1,
      'weight' => 2,
      'settings' => [],
    ];
    $webform->addWebformHandler($webform_handler_configuration);
    $webform->save();

    // Check that handler has been added to the dependencies.
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform_devel', 'webform']]);

    // Uninstall the webform_devel.module which will also remove the
    // debug handler.
    $this->container->get('module_installer')->uninstall(['webform_devel']);
    $webform = Webform::load('contact');

    // Check that handler was removed from the dependencies.
    $this->assertNotEqual($webform->getDependencies(), ['module' => ['webform_devel', 'webform']]);
    $this->assertEqual($webform->getDependencies(), ['module' => ['webform']]);
  }

}
