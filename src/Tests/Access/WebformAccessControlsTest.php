<?php

namespace Drupal\webform\Tests\Access;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform access controls.
 *
 * @group Webform
 */
class WebformAccessControlsTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform access rules.
   */
  public function testAccessControlHandler() {
    // Login as user who can access own webform.
    $this->drupalLogin($this->ownWebformUser);

    // Check create own webform.
    $this->drupalPostForm('admin/structure/webform/add', ['id' => 'test_own', 'title' => 'test_own'], t('Save'));

    // Add test element to own webform.
    $this->drupalPostForm('/admin/structure/webform/manage/test_own', ['elements' => "test:\n  '#markup': 'test'"], t('Save'));

    // Check duplicate own webform.
    $this->drupalGet('admin/structure/webform/manage/test_own/duplicate');
    $this->assertResponse(200);

    // Check delete own webform.
    $this->drupalGet('admin/structure/webform/manage/test_own/delete');
    $this->assertResponse(200);

    // Check access own webform submissions.
    $this->drupalGet('admin/structure/webform/manage/test_own/results/submissions');
    $this->assertResponse(200);

    // Login as user who can access any webform.
    $this->drupalLogin($this->anyWebformUser);

    // Check duplicate any webform.
    $this->drupalGet('admin/structure/webform/manage/test_own/duplicate');
    $this->assertResponse(200);

    // Check delete any webform.
    $this->drupalGet('admin/structure/webform/manage/test_own/delete');
    $this->assertResponse(200);

    // Check access any webform submissions.
    $this->drupalGet('admin/structure/webform/manage/test_own/results/submissions');
    $this->assertResponse(200);

    // Change the owner of the webform to 'any' user.
    $own_webform = Webform::load('test_own');
    $own_webform->setOwner($this->anyWebformUser)->save();

    // Login as user who can access own webform.
    $this->drupalLogin($this->ownWebformUser);

    // Check duplicate denied any webform.
    $this->drupalGet('admin/structure/webform/manage/test_own/duplicate');
    $this->assertResponse(403);

    // Check delete denied any webform.
    $this->drupalGet('admin/structure/webform/manage/test_own/delete');
    $this->assertResponse(403);

    // Check access denied any webform submissions.
    $this->drupalGet('admin/structure/webform/manage/test_own/results/submissions');
    $this->assertResponse(403);
  }

}
