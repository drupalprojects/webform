<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Serialization\Yaml;

/**
 * Tests for webform wizard.
 *
 * @group Webform
 */
class WebformWizardTest extends WebTestBase {

  use WebformTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'webform', 'webform_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->purgeSubmissions();
    parent::tearDown();
  }

  /**
   * Test webform custom wizard, advanced wizard, and custom wizard settings.
   */
  public function testWizard() {

    /* Custom wizard */

    // Check current page is #1.
    $this->drupalGet('webform/test_form_wizard_custom');
    $this->assertCurrentPage('Wizard page #1');

    // Check next page is #2.
    $this->drupalPostForm('webform/test_form_wizard_custom', [], 'Next Page >');
    $this->assertCurrentPage('Wizard page #2');

    // Check previous page is #1.
    $this->drupalPostForm(NULL, [], '< Previous Page');
    $this->assertCurrentPage('Wizard page #1');

    // Hide pages #3 and #4.
    $edit = [
      'pages[wizard_1]' => TRUE,
      'pages[wizard_2]' => TRUE,
      'pages[wizard_3]' => FALSE,
      'pages[wizard_4]' => FALSE,
      'pages[wizard_5]' => TRUE,
      'pages[wizard_6]' => TRUE,
      'pages[wizard_7]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Next Page >');

    // Check next page is #2.
    $this->assertCurrentPage('Wizard page #2');

    // Check next page is #5.
    $this->drupalPostForm(NULL, [], 'Next Page >');
    $this->assertCurrentPage('Wizard page #5');

    // Check previous page is #2.
    $this->drupalPostForm(NULL, [], '< Previous Page');
    $this->assertCurrentPage('Wizard page #2');

    // Check previous page is #1.
    $this->drupalPostForm(NULL, [], '< Previous Page');
    $this->assertCurrentPage('Wizard page #1');

    /* Advanced wizard */

    $webform_wizard_advanced = Webform::load('test_form_wizard_advanced');

    // Get initial wizard start page (Your Information).
    $this->drupalGet('webform/test_form_wizard_advanced');
    // Check progress bar is set to 'Your Information'.
    $this->assertPattern('#<li class="webform-progress-bar__page webform-progress-bar__page--current">\s+<b>Your Information</b><span></span></li>#');
    // Check progress pages.
    $this->assertRaw('Page 1 of 5');
    // Check progress percentage.
    $this->assertRaw('(0%)');
    // Check draft button does not exist.
    $this->assertNoFieldById('edit-draft', 'Save Draft');
    // Check next button does exist.
    $this->assertFieldById('edit-next', 'Next Page >');
    // Check first name field does exist.
    $this->assertFieldById('edit-first-name', 'John');

    // Create a login user who can save drafts.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Move to next page (Contact Information).
    $edit = [
      'first_name' => 'Jane',
    ];
    $this->drupalPostForm('webform/test_form_wizard_advanced', $edit, t('Next Page >'));
    // Check progress bar is set to 'Contact Information'.
    $this->assertPattern('#<li class="webform-progress-bar__page webform-progress-bar__page--done">\s+<b>Your Information</b><span></span></li>#');
    $this->assertPattern('#<li class="webform-progress-bar__page webform-progress-bar__page--current">\s+<b>Contact Information</b></li>#');
    // Check progress pages.
    $this->assertRaw('Page 2 of 5');
    // Check progress percentage.
    $this->assertRaw('(25%)');

    // Check draft button does exist.
    $this->assertFieldById('edit-draft', 'Save Draft');
    // Check previous button does exist.
    $this->assertFieldById('edit-previous', '< Previous Page');
    // Check next button does exist.
    $this->assertFieldById('edit-next', 'Next Page >');
    // Check email field does exist.
    $this->assertFieldById('edit-email', 'johnsmith@example.com');

    // Move to previous page (Your Information) while posting data new data
    // via autosave.
    $edit = [
      'email' => 'janesmith@example.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('< Previous Page'));
    // Check progress bar is set to 'Your Information'.
    $this->assertPattern('#<li class="webform-progress-bar__page webform-progress-bar__page--current">\s+<b>Your Information</b><span></span></li>#');
    // Check nosave class.
    $this->assertRaw('js-webform-unsaved');
    // Check no nosave attributes.
    $this->assertNoRaw('data-webform-unsaved');
    // Check progress pages.
    $this->assertRaw('Page 1 of 5');
    // Check progress percentage.
    $this->assertRaw('(0%)');

    // Check first name set to Jane.
    $this->assertFieldById('edit-first-name', 'Jane');
    // Check gender is still set to Male.
    $this->assertFieldChecked('edit-gender-male');

    // Change gender from Male to Female.
    $edit = [
      'gender' => 'Female',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save Draft'));
    // Check first name set to Jane.
    $this->assertFieldById('edit-first-name', 'Jane');
    // Check gender is now set to Female.
    $this->assertFieldChecked('edit-gender-female');

    // Move to next page (Contact Information).
    $this->drupalPostForm('webform/test_form_wizard_advanced', [], t('Next Page >'));
    // Check nosave class.
    $this->assertRaw('js-webform-unsaved');
    // Check nosave attributes.
    $this->assertRaw('data-webform-unsaved');
    // Check progress bar is set to 'Contact Information'.
    $this->assertCurrentPage('Contact Information');
    // Check progress pages.
    $this->assertRaw('Page 2 of 5');
    // Check progress percentage.
    $this->assertRaw('(25%)');

    // Check email field is now janesmith@example.com.
    $this->assertFieldById('edit-email', 'janesmith@example.com');

    // Save draft which saves the 'current_page'.
    $edit = [
      'phone' => '111-111-1111',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save Draft'));
    // Complete reload the webform.
    $this->drupalGet('webform/test_form_wizard_advanced');
    // Check progress bar is still set to 'Contact Information'.
    $this->assertCurrentPage('Contact Information');

    // Move to last page (Your Feedback).
    $this->drupalPostForm(NULL, [], t('Next Page >'));
    // Check progress bar is set to 'Your Feedback'.
    $this->assertCurrentPage('Your Feedback');
    // Check previous button does exist.
    $this->assertFieldById('edit-previous', '< Previous Page');
    // Check next button is labeled 'Preview'.
    $this->assertFieldById('edit-next', 'Preview');
    // Check submit button does exist.
    $this->assertFieldById('edit-submit', 'Submit');

    // Move to preview.
    $edit = [
      'comments' => 'This is working fine.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    // Check progress bar is set to 'Preview'.
    $this->assertCurrentPage('Preview');
    // Check progress pages.
    $this->assertRaw('Page 4 of 5');
    // Check progress percentage.
    $this->assertRaw('(75%)');

    // Check preview values.
    $this->assertRaw('<b>Last Name</b><br/>Smith<br/><br/>');
    $this->assertRaw('<b>Gender</b><br/>Female<br/><br/>');
    $this->assertRaw('<b>Email</b><br/><a href="mailto:janesmith@example.com">janesmith@example.com</a><br/><br/>');
    $this->assertRaw('<b>Phone</b><br/><a href="tel:111-111-1111">111-111-1111</a><br/><br/>');
    $this->assertRaw('This is working fine.<br/><br/>');

    // Submit the webform.
    $this->drupalPostForm(NULL, [], t('Submit'));
    // Check progress bar is set to 'Complete'.
    $this->assertCurrentPage('Complete');
    // Check progress pages.
    $this->assertRaw('Page 5 of 5');
    // Check progress percentage.
    $this->assertRaw('(100%)');

    /* Custom wizard settings (using advanced wizard) */

    $this->drupalLogout();

    // Check global next and previous button labels.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_wizard_next_button_label', '{global wizard next}')
      ->set('settings.default_wizard_prev_button_label', '{global wizard previous}')
      ->save();
    $this->drupalPostForm('webform/test_form_wizard_advanced', [], t('{global wizard next}'));

    // Check progress bar.
    $this->assertRaw('class="webform-progress-bar"');
    // Check previous button.
    $this->assertFieldById('edit-previous', '{global wizard previous}');
    // Check next button.
    $this->assertFieldById('edit-next', '{global wizard next}');

    // Check webform next and previous button labels.
    $webform_wizard_advanced->setSettings([
      'wizard_next_button_label' => '{webform wizard next}',
      'wizard_prev_button_label' => '{webform wizard previous}',
      'preview_next_button_label' => '{webform preview next}',
      'preview_prev_button_label' => '{webform preview previous}',
    ]);
    $webform_wizard_advanced->save();
    $this->drupalPostForm('webform/test_form_wizard_advanced', [], t('{webform wizard next}'));
    // Check previous button.
    $this->assertFieldById('edit-previous', '{webform wizard previous}');
    // Check next button.
    $this->assertFieldById('edit-next', '{webform wizard next}');

    // Check custom next and previous button labels.
    $elements = Yaml::decode($webform_wizard_advanced->get('elements'));
    $elements['contact']['#next_button_label'] = '{elements wizard next}';
    $elements['contact']['#prev_button_label'] = '{elements wizard previous}';
    $webform_wizard_advanced->set('elements', Yaml::encode($elements));
    $webform_wizard_advanced->save();
    $this->drupalPostForm('webform/test_form_wizard_advanced', [], t('{webform wizard next}'));

    // Check previous button.
    $this->assertFieldById('edit-previous', '{elements wizard previous}');
    // Check next button.
    $this->assertFieldById('edit-next', '{elements wizard next}');

    // Check webform next and previous button labels.
    $webform_wizard_advanced->setSettings([
      'wizard_progress_bar' => FALSE,
      'wizard_progress_pages' => TRUE,
      'wizard_progress_percentage' => TRUE,
    ] + $webform_wizard_advanced->getSettings());
    $webform_wizard_advanced->save();
    $this->drupalGet('webform/test_form_wizard_advanced');

    // Check no progress bar.
    $this->assertNoRaw('class="webform-progress-bar"');
    // Check progress pages.
    $this->assertRaw('Page 1 of 4');
    // Check progress percentage.
    $this->assertRaw('(0%)');

    // Check global complete labels.
    $webform_wizard_advanced->setSettings([
      'wizard_progress_bar' => TRUE,
    ] + $webform_wizard_advanced->getSettings());
    $webform_wizard_advanced->save();
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_wizard_complete_label', '{global complete}')
      ->save();
    $this->drupalGet('webform/test_form_wizard_advanced');
    $this->assertRaw('{global complete}');

    // Check webform complete label.
    $webform_wizard_advanced->setSettings([
      'wizard_progress_bar' => TRUE,
      'wizard_complete_label' => '{webform complete}',
    ] + $webform_wizard_advanced->getSettings());
    $webform_wizard_advanced->save();
    $this->drupalGet('webform/test_form_wizard_advanced');
    $this->assertRaw('{webform complete}');

    // Check webform exclude complete.
    $webform_wizard_advanced->setSettings([
      'wizard_complete' => FALSE,
    ] + $webform_wizard_advanced->getSettings());
    $webform_wizard_advanced->save();
    $this->drupalGet('webform/test_form_wizard_advanced');

    // Check complete label.
    $this->assertRaw('class="webform-progress-bar"');
    // Check complete is missing from confirmation page.
    $this->assertNoRaw('{webform complete}');
    $this->drupalGet('webform/test_form_wizard_advanced/confirmation');
    $this->assertNoRaw('class="webform-progress-bar"');
  }

  /**
   * Assert the current page using the progress bar's markup.
   *
   * @param string $title
   *   The title of the current page.
   */
  protected function assertCurrentPage($title) {
    $this->assertPattern('|<li class="webform-progress-bar__page webform-progress-bar__page--current">\s+<b>' . $title . '</b>|');
  }

}
