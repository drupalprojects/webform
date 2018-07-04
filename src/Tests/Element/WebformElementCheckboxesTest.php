<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform checkboxes element.
 *
 * @group Webform
 */
class WebformElementCheckboxesTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_checkboxes'];

  /**
   * Tests checkbox and checkboxes element.
   */
  public function testCheckboxes() {
    $webform = Webform::load('test_element_checkboxes');

    // Check exclude empty is not visible.
    $edit = [
      'checkboxes_required_conditions[Yes]' => TRUE,
      'checkboxes_other_required_conditions[checkboxes][Yes]' => TRUE,
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertNoRaw('<label>checkbox_exclude_empty</label>');

    // Uncheck #exclude_empty.
    $webform->setElementProperties('checkbox_exclude_empty', ['#type' => 'checkbox', '#title' => 'checkbox_exclude_empty']);
    $webform->save();

    // Check exclude empty is  visible.
    $edit = [
      'checkboxes_required_conditions[Yes]' => TRUE,
      'checkboxes_other_required_conditions[checkboxes][Yes]' => TRUE,
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertRaw('<label>checkbox_exclude_empty</label>');

  }

}
