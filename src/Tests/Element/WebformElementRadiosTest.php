<?php

namespace Drupal\webform\Tests\Element;

/**
 * Tests for webform element radios.
 *
 * @group Webform
 */
class WebformElementRadiosTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_radios'];

  /**
   * Tests radios element.
   */
  public function testElementRadios() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('webform/test_element_radios');

    // Check radios with description display.
    $this->assertRaw('<input data-drupal-selector="edit-radios-description-one" aria-describedby="edit-radios-description-one--description" type="radio" id="edit-radios-description-one" name="radios_description" value="one" class="form-radio" />');
    $this->assertRaw('<label for="edit-radios-description-one" class="option">One</label>');
    $this->assertRaw('<div id="edit-radios-description-one--description" class="webform-element-description">This is a description</div>');

    // Check radios with help text display.
    $this->assertRaw('<input data-drupal-selector="edit-radios-help-one" type="radio" id="edit-radios-help-one" name="radios_help" value="one" class="form-radio" />');
    $this->assertRaw('<label for="edit-radios-help-one" class="option">One<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;One&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is a description&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check radios results does not include description.
    $edit = [
      'radios_required' => 'Yes',
      'radios_required_conditional_trigger' => FALSE,
      'buttons_required_conditional_trigger' => FALSE,
      'radios_description' => 'one',
      'radios_help' => 'two',
    ];
    $this->drupalPostForm('webform/test_element_radios', $edit, t('Preview'));
    $this->assertPattern('#<label>radios_description</label>\s+One#');
    $this->assertPattern('#<label>radios_help</label>\s+Two#');
  }

}
