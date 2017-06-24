<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission limits.
 *
 * @group Webform
 */
class WebformSubmissionLimitsTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_limit'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform setting including confirmation.
   */
  public function testSettings() {
    $webform_limit = Webform::load('test_form_limit');

    // Check webform available.
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');

    $this->drupalLogin($this->normalUser);

    // Check that draft does not count toward limit.
    $this->postSubmission($webform_limit, [], t('Save Draft'));
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('A partially-completed form was found. Please complete the remaining portions.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check limit reached and webform not available for authenticated user.
    $this->postSubmission($webform_limit);
    $this->drupalGet('webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');

    $this->drupalLogout();

    // Check admin can still edit their submission.
    $this->drupalLogin($this->rootUser);
    $sid = $this->postSubmission($webform_limit);
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $this->assertFieldByName('op', 'Save');
    $this->assertNoRaw('No more submissions are permitted.');

    $this->drupalLogout();

    // Check webform is still available for anonymous users.
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Add 1 more submissions as an anonymous user making the total number of
    // submissions equal to 3.
    $this->postSubmission($webform_limit);

    // Check limit reached and webform not available for anonymous user.
    $this->drupalGet('webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');

    // Add 1 more submissions as an root user making the total number of
    // submissions equal to 4.
    $this->drupalLogin($this->rootUser);
    $this->postSubmission($webform_limit);
    $this->drupalLogout();

    // Check total limit.
    $this->drupalGet('webform/test_form_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('Only 4 submissions are allowed.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check admin can still post submissions.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('webform/test_form_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('Only 4 submissions are allowed.');
    $this->assertRaw('Only submission administrators are allowed to access this webform and create new submissions.');
  }

}
