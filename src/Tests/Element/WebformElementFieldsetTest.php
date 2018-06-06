<?php

namespace Drupal\webform\Tests\Element;

/**
 * Tests for fieldset element.
 *
 * @group Webform
 */
class WebformElementFieldsetTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_fieldset'];

  /**
   * Test fieldset element.
   */
  public function testFieldset() {
    // Check that invalid 'required' and 'aria-required' attributes are removed
    // from fieldset.
    $this->drupalGet('webform/test_element_fieldset');
    $this->assertRaw('<fieldset data-drupal-selector="edit-fieldset-required" id="edit-fieldset-required" class="required js-form-item form-item js-form-wrapper form-wrapper">');
  }

}
