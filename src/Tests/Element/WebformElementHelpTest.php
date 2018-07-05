<?php

namespace Drupal\webform\Tests\Element;

/**
 * Tests for element help.
 *
 * @group Webform
 */
class WebformElementHelpTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_help'];

  /**
   * Test element help.
   */
  public function testHelp() {
    $this->drupalGet('webform/test_element_help');

    // Check basic help.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help}"><span aria-hidden="true">?</span></span>');

    // Check help with HTML markup.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help with &lt;b&gt;HTML markup&lt;/b&gt;}"><span aria-hidden="true">?</span></span>');

    // Check help with XSS.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help with &lt;b&gt;XSS alert(&quot;XSS&quot;)&lt;/b&gt;}"><span aria-hidden="true">?</span></span>');

    // Check help with inline title.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help with an inline title}"><span aria-hidden="true">?</span></span>
help_inline</label>');

    // Check radios (fieldset).
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help for radio buttons}"><span aria-hidden="true">?</span></span>');

    // Check fieldset.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help for a fieldset}"><span aria-hidden="true">?</span></span>');

    // Check details.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help for a details element}"><span aria-hidden="true">?</span></span>');

    // Check section.
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="{This is an example of help for a section element}"><span aria-hidden="true">?</span></span>');
  }

}
