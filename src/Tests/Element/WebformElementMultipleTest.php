<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element multiple.
 *
 * @group Webform
 */
class WebformElementMultipleTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_multiple'];

  /**
   * Tests building of list elements.
   */
  public function testWebformElementMultiple() {

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_multiple', [], t('Submit'));
    $this->assertRaw("webform_multiple_default:
  - One
  - Two
  - Three");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('webform/test_element_multiple');

    // Check first tr.
    $this->assertRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-multiple-default-items-0">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-webform-multiple-default-items-0--item- form-item-webform-multiple-default-items-0--item- form-no-label">');
    $this->assertRaw('<label for="edit-webform-multiple-default-items-0-item-" class="visually-hidden">Item value</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-default-items-0-item-" type="text" id="edit-webform-multiple-default-items-0-item-" name="webform_multiple_default[items][0][_item_]" value="One" size="60" maxlength="128" placeholder="Enter value" class="form-text" />');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-webform-multiple-default-items-0-weight form-item-webform-multiple-default-items-0-weight form-no-label">');
    $this->assertRaw('<label for="edit-webform-multiple-default-items-0-weight" class="visually-hidden">Item weight</label>');
    $this->assertRaw('<input class="webform-multiple-sort-weight form-number" data-drupal-selector="edit-webform-multiple-default-items-0-weight" type="number" id="edit-webform-multiple-default-items-0-weight" name="webform_multiple_default[items][0][weight]" value="0" step="1" size="10" />');
    $this->assertRaw('<td><input data-drupal-selector="edit-webform-multiple-default-items-0-operations-add" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-add" name="webform_multiple_default_table_add_0"');
    $this->assertRaw('<input data-drupal-selector="edit-webform-multiple-default-items-0-operations-remove" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-remove" name="webform_multiple_default_table_remove_0"');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check populated 'webform_multiple_default'.
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Three');
    $this->assertFieldByName('webform_multiple_default[items][3][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][4][_item_]', '');

    // Check adding 'four' and 1 more option.
    $edit = [
      'webform_multiple_default[items][3][_item_]' => 'Four',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_multiple_default_table_add');
    $this->assertFieldByName('webform_multiple_default[items][3][_item_]', 'Four');
    $this->assertFieldByName('webform_multiple_default[items][4][_item_]', '');

    // Check add 10 more rows.
    $edit = ['webform_multiple_default[add][more_items]' => 10];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_multiple_default_table_add');
    $this->assertFieldByName('webform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][15][_item_]', '');

    // Check remove 'one' options.
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_multiple_default_table_remove_0');
    $this->assertNoFieldByName('webform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('webform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('webform_multiple_default[items][0][_item_]', 'Two');
    $this->assertFieldByName('webform_multiple_default[items][1][_item_]', 'Three');
    $this->assertFieldByName('webform_multiple_default[items][2][_item_]', 'Four');
  }

}
