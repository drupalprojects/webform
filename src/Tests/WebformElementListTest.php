<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for webform element list.
 *
 * @group Webform
 */
class WebformElementListTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'webform', 'webform_test'];

  /**
   * Tests building of list elements.
   */
  public function test() {
    global $base_path;

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_list', [], t('Submit'));
    $this->assertRaw("webform_list_basic:
  - One
  - Two
  - Three");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('webform/test_element_list');

    // Check first tr.
    $this->assertRaw('<tr class="draggable odd" data-drupal-selector="edit-webform-list-basic-items-0">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-webform-list-basic-items-0-item form-item-webform-list-basic-items-0-item form-no-label">');
    $this->assertRaw('<label for="edit-webform-list-basic-items-0-item" class="visually-hidden">Item value</label>');
    $this->assertRaw('<input data-drupal-selector="edit-webform-list-basic-items-0-item" type="text" id="edit-webform-list-basic-items-0-item" name="webform_list_basic[items][0][item]" value="One" size="25" maxlength="128" placeholder="Enter value" class="form-text" />');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-webform-list-basic-items-0-weight form-item-webform-list-basic-items-0-weight form-no-label">');
    $this->assertRaw('<label for="edit-webform-list-basic-items-0-weight" class="visually-hidden">Item weight</label>');
    $this->assertRaw('<input class="webform-list-sort-weight form-number" data-drupal-selector="edit-webform-list-basic-items-0-weight" type="number" id="edit-webform-list-basic-items-0-weight" name="webform_list_basic[items][0][weight]" value="0" step="1" size="10" />');
    $this->assertRaw('<td><input data-drupal-selector="edit-webform-list-basic-items-0-operations-add" formnovalidate="formnovalidate" type="image" id="edit-webform-list-basic-items-0-operations-add" name="webform_list_basic_table_add_0" src="' . $base_path . 'core/misc/icons/787878/plus.svg" class="image-button js-form-submit form-submit" />');
    $this->assertRaw('<input data-drupal-selector="edit-webform-list-basic-items-0-operations-remove" formnovalidate="formnovalidate" type="image" id="edit-webform-list-basic-items-0-operations-remove" name="webform_list_basic_table_remove_0" src="' . $base_path . 'core/misc/icons/787878/ex.svg" class="image-button js-form-submit form-submit" />');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check populated 'webform_list_basic'.
    $this->assertFieldByName('webform_list_basic[items][0][item]', 'One');
    $this->assertFieldByName('webform_list_basic[items][1][item]', 'Two');
    $this->assertFieldByName('webform_list_basic[items][2][item]', 'Three');
    $this->assertFieldByName('webform_list_basic[items][3][item]', '');
    $this->assertNoFieldByName('webform_list_basic[items][4][item]', '');

    // Check adding 'four' and 1 more option.
    $edit = [
      'webform_list_basic[items][3][item]' => 'Four',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_list_basic_table_add');
    $this->assertFieldByName('webform_list_basic[items][3][item]', 'Four');
    $this->assertFieldByName('webform_list_basic[items][4][item]', '');

    // Check add 10 more rows.
    $edit = ['webform_list_basic[add][more_items]' => 10];
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_list_basic_table_add');
    $this->assertFieldByName('webform_list_basic[items][14][item]', '');
    $this->assertNoFieldByName('webform_list_basic[items][15][item]', '');

    // Check remove 'one' options.
    $this->drupalPostAjaxForm(NULL, $edit, 'webform_list_basic_table_remove_0');
    $this->assertNoFieldByName('webform_list_basic[items][14][item]', '');
    $this->assertNoFieldByName('webform_list_basic[items][0][item]', 'One');
    $this->assertFieldByName('webform_list_basic[items][0][item]', 'Two');
    $this->assertFieldByName('webform_list_basic[items][1][item]', 'Three');
    $this->assertFieldByName('webform_list_basic[items][2][item]', 'Four');
  }

}
