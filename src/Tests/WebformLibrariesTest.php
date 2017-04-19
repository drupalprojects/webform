<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform libraries.
 *
 * @group Webform
 */
class WebformLibrariesTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_libraries_optional'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform libraries.
   */
  public function testLibraries() {
    $optional_properties = [
      'icheck' => 'properties[icheck]',
      'input_mask' => 'properties[input_mask][select]',
      'international_telephone' => 'properties[international]',
      'international_telephone_composite' => 'properties[phone__international]',
      'word_counter' => 'properties[counter_type]',
      'select2' => 'properties[select2]',
    ];

    $this->drupalLogin($this->adminWebformUser);

    // Check optional libraries are included.
    $this->drupalGet('webform/test_libraries_optional');
    $this->assertRaw('/select2.min.js');
    $this->assertRaw('/word-and-character-counter.min.js');
    $this->assertRaw('/intlTelInput.min.js');
    $this->assertRaw('/jquery.inputmask.bundle.min.js');
    $this->assertRaw('/icheck.js');
    $this->assertRaw('/codemirror.js');

    // Check optional libraries are properties accessible (#access = TRUE).
    foreach ($optional_properties as $element_name => $input_name) {
      $this->drupalGet("/admin/structure/webform/manage/test_libraries_optional/element/$element_name/edit");
      $this->assertFieldByName($input_name);
    }

    // Exclude optional libraries.
    $edit = [
      'libraries[excluded_libraries][ckeditor]' => FALSE,
      'libraries[excluded_libraries][ckeditor_autogrow]' => FALSE,
      'libraries[excluded_libraries][codemirror]' => FALSE,
      'libraries[excluded_libraries][jquery.icheck]' => FALSE,
      'libraries[excluded_libraries][jquery.inputmask]' => FALSE,
      'libraries[excluded_libraries][jquery.intl-tel-input]' => FALSE,
      'libraries[excluded_libraries][jquery.select2]' => FALSE,
      'libraries[excluded_libraries][jquery.word-and-character-counter]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/webform/settings', $edit, t('Save configuration'));

    // Check optional libraries are excluded.
    $this->drupalGet('webform/test_libraries_optional');
    $this->assertNoRaw('/select2.min.js');
    $this->assertNoRaw('/word-and-character-counter.min.js');
    $this->assertNoRaw('/intlTelInput.min.js');
    $this->assertNoRaw('/jquery.inputmask.bundle.min.js');
    $this->assertNoRaw('/icheck.js');
    $this->assertNoRaw('/codemirror.js');

    // Check optional libraries are properties hidden (#access = FALSE).
    foreach ($optional_properties as $element_name => $input_name) {
      $this->drupalGet("/admin/structure/webform/manage/test_libraries_optional/element/$element_name/edit");
      $this->assertNoFieldByName($input_name);
    }
  }

}
