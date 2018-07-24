<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for text format element.
 *
 * @group Webform
 */
class WebformElementTextFormatTest extends WebformElementTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_text_format'];

  /**
   * Test text format element.
   */
  public function testTextFormat() {
    $webform_text_format = Webform::load('test_element_text_format');

    // Check that formats and tips are removed and/or hidden.
    $this->drupalGet('webform/test_element_text_format');
    $this->assertRaw('<div class="filter-wrapper js-form-wrapper form-wrapper" data-drupal-selector="edit-text-format-format" style="display: none" id="edit-text-format-format">');
    $this->assertRaw('<div class="filter-help js-form-wrapper form-wrapper" data-drupal-selector="edit-text-format-format-help" style="display: none" id="edit-text-format-format-help">');

    // Check 'text_format' values.
    $this->drupalGet('webform/test_element_text_format');
    $this->assertFieldByName('text_format[value]', 'The quick brown fox jumped over the lazy dog.');
    $this->assertRaw('No HTML tags allowed.');

    $text_format = [
      'value' => 'Custom value',
      'format' => 'custom_format',
    ];
    $form = $webform_text_format->getSubmissionForm(['data' => ['text_format' => $text_format]]);
    $this->assertEqual($form['elements']['text_format']['#default_value'], $text_format['value']);
    $this->assertEqual($form['elements']['text_format']['#format'], $text_format['format']);
  }

}
