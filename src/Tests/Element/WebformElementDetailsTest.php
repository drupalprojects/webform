<?php

namespace Drupal\webform\Tests\Element;

/**
 * Tests for details element.
 *
 * @group Webform
 */
class WebformElementDetailsTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_details'];

  /**
   * Test details element.
   */
  public function testDetails() {
    // Check that invalid 'required' and 'aria-required' attributes are removed
    // from details.
    $this->drupalGet('webform/test_element_details');
    $this->assertRaw('<details data-webform-key="details_required" data-drupal-selector="edit-details-required" id="edit-details-required" class="js-form-wrapper form-wrapper required">');
  }

}
