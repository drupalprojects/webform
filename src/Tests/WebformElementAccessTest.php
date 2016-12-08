<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform element access.
 *
 * @group Webform
 */
class WebformElementAccessTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui', 'webform_test'];

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $webform = Webform::load('test_element_access');

    // Check user from USER:1 to admin submission user.
    $elements = $webform->get('elements');
    $elements = str_replace('      - 1', '      - ' . $this->adminSubmissionUser->id(), $elements);
    $elements = str_replace('USER:1', 'USER:' . $this->adminSubmissionUser->id(), $elements);
    $webform->set('elements', $elements);
    $webform->save();

    // Create a webform submission.
    $this->drupalLogin($this->normalUser);
    $sid = $this->postSubmission($webform);
    $webform_submission = WebformSubmission::load($sid);

    /* Test #private element property */

    // Check element with #private property hidden for normal user.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('webform/test_element_access');
    $this->assertNoFieldByName('private', '');

    // Check element with #private property visible for admin user.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet('webform/test_element_access');
    $this->assertFieldByName('private', '');

    // Check admins have 'administer webform element access' permission.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet('admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertFieldById('edit-properties-access-create-roles-anonymous');

    // Check webform builder don't have 'administer webform element access'
    // permission.
    $this->drupalLogin($this->ownFormUser);
    $this->drupalGet('admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertNoFieldById('edit-properties-access-create-roles-anonymous');

    /* Create access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet('webform/test_element_access');
    $this->assertFieldByName('access_create_roles_anonymous');
    $this->assertNoFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');

    // Check authenticated access.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('webform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet('webform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertFieldByName('access_create_users');

    /* Create update */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertFieldByName('access_update_roles_anonymous');
    $this->assertNoFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');

    // Check authenticated role access.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet("/webform/test_element_access/submissions/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet("/admin/structure/webform/manage/test_element_access/submission/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertFieldByName('access_update_users');

    /* Create view */

    // NOTE: Anonymous users can view submissions, so there is nothing to check.

    // Check authenticated role access.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet("/admin/structure/webform/manage/test_element_access/submission/$sid");
    $this->assertNoRaw('access_view_roles (anonymous)');
    $this->assertRaw('access_view_roles (authenticated)');
    $this->assertNoRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet("/admin/structure/webform/manage/test_element_access/submission/$sid");
    $this->assertNoRaw('access_view_roles (anonymous)');
    $this->assertRaw('access_view_roles (authenticated)');
    $this->assertRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');

  }

}
