<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform entity.
 *
 * @group Webform
 */
class WebformTest extends WebformTestBase {

  /**
   * Tests webform entity.
   */
  public function testWebform() {
    /** @var \Drupal\webform\WebformInterface $webform */
    list($webform) = $this->createWebformWithSubmissions();

    // Check get elements.
    $elements = $webform->getElementsInitialized();
    $this->assert(is_array($elements));

    // Check getElements.
    $columns = $webform->getElementsFlattenedAndHasValue();
    $this->assertEqual(array_keys($columns), ['first_name', 'last_name', 'sex', 'dob', 'node', 'colors', 'likert', 'address']);

    // Set invalid elements.
    $webform->set('elements', "not\nvalid\nyaml")->save();

    // Check invalid elements.
    $this->assertFalse($webform->getElementsInitialized());

    // Check invalid element columns.
    $this->assertEqual($webform->getElementsFlattenedAndHasValue(), []);

    // Check for 3 submissions..
    $this->assertEqual($this->submissionStorage->getTotal($webform), 3);

    // Check delete.
    $webform->delete();

    // Check all 3 submissions deleted.
    $this->assertEqual($this->submissionStorage->getTotal($webform), 0);

    // Check that 'test' state was deleted with the webform.
    $this->assertEqual(\Drupal::state()->get('webform.webform.' . $webform->id()), NULL);
  }

}
