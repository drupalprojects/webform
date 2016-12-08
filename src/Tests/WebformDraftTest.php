<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform draft.
 *
 * @group Webform
 */
class WebformDraftTest extends WebTestBase {

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
   * Test webform draft with autosave.
   */
  public function testDraftWithAutosave() {
    $account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($account);

    $webform_draft = Webform::load('test_form_draft');

    // Save a draft.
    $sid = $this->postSubmission($webform_draft, ['name' => 'John Smith'], t('Save a draft'));

    // Check saved draft message.
    $this->assertRaw('Your draft has been saved');
    $this->assertNoRaw('You have an existing draft');

    // Check loaded draft message.
    $this->drupalGet('webform/test_form_draft');
    $this->assertNoRaw('Your draft has been saved');
    $this->assertRaw('You have an existing draft');
    $this->assertFieldByName('name', 'John Smith');

    // Check submissions.
    $this->drupalGet('webform/test_form_draft/submissions');
    $this->assertRaw($sid . ' (draft)');

    // Check submission.
    $this->drupalGet('admin/structure/webform/manage/test_form_draft/submission/' . $sid);
    $this->assertRaw('<div><b>Is draft:</b> Yes</div>');

    // Check update draft and bypass validation.
    $this->drupalPostForm('webform/test_form_draft', [
      'name' => '',
      'comment' => 'Hello World!',
    ], t('Save a draft'));
    $this->assertRaw('Your draft has been saved');
    $this->assertNoRaw('You have an existing draft');
    $this->assertFieldByName('name', '');
    $this->assertFieldByName('comment', 'Hello World!');

    // Check preview of draft with valid data.
    $this->drupalPostForm('webform/test_form_draft', [
      'name' => 'John Smith',
      'comment' => 'Hello World!',
    ], t('Preview'));
    $this->assertNoRaw('Your draft has been saved');
    $this->assertNoRaw('You have an existing draft');
    $this->assertNoFieldByName('name', '');
    $this->assertNoFieldByName('comment', 'Hello World!');
    $this->assertRaw('<b>Name</b><br/>');
    $this->assertRaw('<b>Comment</b><br/>');
    $this->assertRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    // Check submit.
    $this->drupalPostForm('webform/test_form_draft', [], t('Submit'));
    $this->assertRaw('New submission added to Test: Webform: Draft.');

    // Check submission not in draft.
    $this->drupalGet('webform/test_form_draft');
    $this->assertNoRaw('Your draft has been saved');
    $this->assertNoRaw('You have an existing draft');
    $this->assertFieldByName('name', '');
    $this->assertFieldByName('comment', '');

    // Check submissions.
    $this->drupalGet('webform/test_form_draft/submissions');
    $this->assertNoRaw($sid . ' (draft)');

    // Check export with draft settings.
    $this->drupalGet('admin/structure/webform/manage/test_form_draft/results/download');
    $this->assertFieldByName('export[download][state]', 'all');

    // Check export without draft settings.
    $this->drupalGet('admin/structure/webform/manage/test_form_preview/results/download');
    $this->assertNoFieldByName('export[download][state]', 'all');

    // Check autosave on submit with validation errors.
    $this->drupalPostForm('webform/test_form_draft', [], t('Submit'));
    $this->assertRaw('Name field is required.');
    $this->drupalGet('webform/test_form_draft');
    $this->assertRaw('You have an existing draft');

    // Check autosave on preview.
    $this->drupalPostForm('webform/test_form_draft', ['name' => 'John Smith'], t('Preview'));
    $this->assertRaw('Please review your submission.');
    $this->drupalGet('webform/test_form_draft');
    $this->assertRaw('You have an existing draft');
    $this->assertRaw('<b>Name</b><br/>John Smith<br/><br/>');
  }

}
