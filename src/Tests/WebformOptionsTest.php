<?php

namespace Drupal\webform\Tests;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\WebformOptions;

/**
 * Tests for webform option entity.
 *
 * @group Webform
 */
class WebformOptionsTest extends WebformTestBase {

  /**
   * Tests webform options entity.
   */
  public function testWebformOptions() {
    // Check get element options.
    $yes_no_options = ['Yes' => 'Yes', 'No' => 'No'];
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => $yes_no_options]), $yes_no_options);
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => 'yes_no']), $yes_no_options);
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => 'not-found']), []);

    $color_options = [
      'red' => 'Red',
      'white' => 'White',
      'blue' => 'Blue',
    ];

    // Check get element options for manually defined options.
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => $color_options]), $color_options);

    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = WebformOptions::create([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'test_flag',
      'title' => 'Test flag',
      'options' => Yaml::encode($color_options),
    ]);
    $webform_options->save();

    // Check get options.
    $this->assertEqual($webform_options->getOptions(), $color_options);

    // Set invalid options.
    $webform_options->set('options', "not\nvalid\nyaml")->save();

    // Check invalid options.
    $this->assertFalse($webform_options->getOptions());

    // Check hook_webform_options_WEBFORM_OPTIONS_ID_alter().
    $this->drupalGet('webform/test_options');
    $this->assertRaw('<option value="one">One</option><option value="two">Two</option><option value="three">Three</option>');

    // Check hook_webform_options_WEBFORM_OPTIONS_ID_alter() is not executed
    // when options are altered.
    $webform_test_options = WebformOptions::load('test');
    $webform_test_options->set('options', Yaml::encode($color_options));
    $webform_test_options->save();

    $this->drupalGet('webform/test_options');
    $this->assertRaw('<option value="red">Red</option><option value="white">White</option><option value="blue">Blue</option>');
  }

}
