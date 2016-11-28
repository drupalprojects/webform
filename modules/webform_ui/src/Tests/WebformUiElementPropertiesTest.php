<?php

namespace Drupal\webform_ui\Tests;

use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform UI element properties.
 *
 * @group WebformUi
 */
class WebformUiElementPropertiesTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter', 'user', 'webform', 'webform_test', 'webform_examples', 'webform_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->createFilters();
  }

  /**
   * Tests element properties.
   */
  public function testElementProperties() {
    $this->drupalLogin($this->adminFormUser);

    // Loops through all the elements, edits them via the UI, and check that
    // the element's render array has not be altered.
    // This verifies that the edit element webform is not unexpectedly altering
    // an element's render array.
    $webform_ids = ['example_layout_basic', 'test_element', 'test_element_access', 'test_element_extras', 'test_form_states_triggers'];
    foreach ($webform_ids as $webform_id) {
      /** @var \Drupal\webform\WebformInterface $webform_elements */
      $webform_elements = Webform::load($webform_id);
      $original_elements = $webform_elements->getElementsDecodedAndFlattened();
      foreach ($original_elements as $key => $original_element) {
        $this->drupalPostForm('admin/structure/webform/manage/' . $webform_elements->id() . '/element/' . $key . '/edit', [], t('Save'));

        // Must reset the webform entity cache so that the update elements can
        // be loaded.
        \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();

        /** @var \Drupal\webform\WebformInterface $webform_elements */
        $webform_elements = Webform::load($webform_id);
        $updated_element = $webform_elements->getElementsDecodedAndFlattened()[$key];

        $this->assertEqual($original_element, $updated_element, "'$key'' properties is equal.");
      }
    }
  }

}
