<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for webform element options.
 *
 * @group Webform
 */
class WebformElementOptionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'webform', 'webform_test'];

  /**
   * Tests building of options elements.
   */
  public function test() {
    global $base_path;

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_options', [], t('Submit'));
    $this->assertRaw("webform_options: {  }
webform_options_default_value:
  one: One
  two: Two
  three: Three
webform_options_optgroup:
  'Group One':
    one: One
  'Group Two':
    two: Two
  'Group Three':
    three: Three
webform_element_options_entity: yes_no
webform_element_options_custom:
  one: One
  two: Two
  three: Three");

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_options', ['webform_element_options_custom[options]' => 'yes_no'], t('Submit'));
    $this->assertRaw("webform_element_options_custom: yes_no");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('webform/test_element_options');

    // Check empty 'webform_options' table and first tr.
    $this->assertRaw('<label for="edit-webform-options">webform_options</label>');
    $this->assertRaw('<div id="webform_options_table" class="webform-options-table"><table data-drupal-selector="edit-webform-options-options" id="edit-webform-options-options" class="responsive-enabled" data-striping="1">');
    $this->assertRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-options-options-0">');
    $this->assertRaw('<input data-drupal-selector="edit-webform-options-options-0-value" type="text" id="edit-webform-options-options-0-value" name="webform_options[options][0][value]" value="" size="25" maxlength="128" placeholder="Enter value" class="form-text" />');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-webform-options-options-0-text form-item-webform-options-options-0-text form-no-label">');
    $this->assertRaw('<input data-drupal-selector="edit-webform-options-options-0-text" type="text" id="edit-webform-options-options-0-text" name="webform_options[options][0][text]" value="" size="25" placeholder="Enter text" class="form-text" />');
    $this->assertRaw('<input class="webform-options-sort-weight form-number" data-drupal-selector="edit-webform-options-options-0-weight" type="number" id="edit-webform-options-options-0-weight" name="webform_options[options][0][weight]" value="0" step="1" size="10" />');
    $this->assertRaw('<td><input data-drupal-selector="edit-webform-options-options-0-remove" formnovalidate="formnovalidate" type="image" id="edit-webform-options-options-0-remove" name="webform_options_table_remove_0" src="' . $base_path . 'core/misc/icons/787878/ex.svg" class="image-button js-form-submit form-submit" />');

    // Check optgroup 'webform_options' display CodeMirror editor.
    $this->assertRaw('<label for="edit-webform-options-optgroup" class="js-form-required form-required">webform_options (optgroup)</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-options-optgroup-options" aria-describedby="edit-webform-options-optgroup-options--description" class="js-webform-codemirror webform-codemirror yaml form-textarea resize-vertical" data-webform-codemirror-mode="text/x-yaml" id="edit-webform-options-optgroup-options" name="webform_options_optgroup[options]" rows="5" cols="60">');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check populated 'webform_options_default_value'.
    $this->assertFieldByName('webform_options_default_value[options][0][value]', 'one');
    $this->assertFieldByName('webform_options_default_value[options][0][text]', 'One');
    $this->assertFieldByName('webform_options_default_value[options][1][value]', 'two');
    $this->assertFieldByName('webform_options_default_value[options][1][text]', 'Two');
    $this->assertFieldByName('webform_options_default_value[options][2][value]', 'three');
    $this->assertFieldByName('webform_options_default_value[options][2][text]', 'Three');
    $this->assertFieldByName('webform_options_default_value[options][3][value]', '');
    $this->assertFieldByName('webform_options_default_value[options][3][text]', '');
    $this->assertNoFieldByName('webform_options_default_value[options][4][value]', '');
    $this->assertNoFieldByName('webform_options_default_value[options][4][text]', '');

    // Check adding 'four' and 1 more option.
    $edit = [
      'webform_options_default_value[options][3][value]' => 'four',
      'webform_options_default_value[options][3][text]' => 'Four',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_options_default_value_table_add');
    $this->assertFieldByName('webform_options_default_value[options][3][value]', 'four');
    $this->assertFieldByName('webform_options_default_value[options][3][text]', 'Four');
    $this->assertFieldByName('webform_options_default_value[options][4][value]', '');
    $this->assertFieldByName('webform_options_default_value[options][4][text]', '');

    // Check add 10 more rows.
    $edit = ['webform_options_default_value[add][more_options]' => 10];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_options_default_value_table_add');
    $this->assertFieldByName('webform_options_default_value[options][14][value]', '');
    $this->assertFieldByName('webform_options_default_value[options][14][text]', '');
    $this->assertNoFieldByName('webform_options_default_value[options][15][value]', '');
    $this->assertNoFieldByName('webform_options_default_value[options][15][text]', '');

    // Check remove 'one' options.
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_options_default_value_table_remove_0');
    $this->assertNoFieldByName('webform_options_default_value[options][14][value]', '');
    $this->assertNoFieldByName('webform_options_default_value[options][14][text]', '');
    $this->assertNoFieldByName('webform_options_default_value[options][0][value]', 'one');
    $this->assertNoFieldByName('webform_options_default_value[options][0][text]', 'One');
    $this->assertFieldByName('webform_options_default_value[options][0][value]', 'two');
    $this->assertFieldByName('webform_options_default_value[options][0][text]', 'Two');
  }

}
