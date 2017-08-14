<?php

namespace Drupal\webform\Tests\Wizard;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform basic wizard.
 *
 * @group Webform
 */
class WebformWizardBasicTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Test webform advanced wizard.
   */
  public function testBasicWizard() {
    $this->drupalLogin($this->rootUser);

    // Create a wizard submission.
    $wizard_webform = Webform::load('test_form_wizard_basic');
    $this->drupalPostForm('webform/test_form_wizard_basic', [], t('Next Page >'));
    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($wizard_webform);

    // Check access to 'Edit: All' tab for wizard.
    $this->drupalGet("admin/structure/webform/manage/test_form_wizard_basic/submission/$sid/edit/all");
    $this->assertResponse(200);

    // Check that page 1 and 2 are displayed.
    $this->assertRaw('<summary role="button" aria-controls="edit-page-1" aria-expanded="false" aria-pressed="false">Page 1</summary>');
    $this->assertRaw('<summary role="button" aria-controls="edit-page-2" aria-expanded="false" aria-pressed="false">Page 2</summary>');

    // Create a contact form submission.
    $contact_webform = Webform::load('contact');
    $sid = $this->postSubmissionTest($contact_webform);

    // Check access denied to 'Edit: All' tab for simple form.
    $this->drupalGet("admin/structure/webform/manage/contact/submission/$sid/edit/all");
    $this->assertResponse(403);
  }

}