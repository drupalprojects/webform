<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform submission access.
 *
 * @group Webform
 */
class WebformSubmissionAccessTest extends WebformTestBase {

  /**
   * Tests webform submission access.
   */
  public function testAccess() {
    /** @var \Drupal\webform\WebformInterface $webform */
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    list($webform, $submissions) = $this->createWebformWithSubmissions();

    $webform_id = $webform->id();
    $sid = $submissions[0]->id();

    // Check all results access denied.
    $this->drupalGet('/admin/structure/webform/results/manage');
    $this->assertResponse(403);

    // Check webform results access denied.
    $this->drupalGet("/admin/structure/webform/manage/$webform_id/results/submissions");
    $this->assertResponse(403);

    // Check webform submission access denied.
    $this->drupalGet("/admin/structure/webform/manage/$webform_id/submission/$sid");
    $this->assertResponse(403);

    $viewSubmissionUser = $this->drupalCreateUser([
      'access content',
      'access webform overview',
      'view any webform submission',
    ]);
    $this->drupalLogin($viewSubmissionUser);

    // Check all results access allowed.
    $this->drupalGet('/admin/structure/webform/results/manage');
    $this->assertResponse(200);

    // Check webform results access allowed.
    $this->drupalGet("/admin/structure/webform/manage/$webform_id/results/submissions");
    $this->assertResponse(200);

    // Check webform submission access allowed.
    $this->drupalGet("/admin/structure/webform/manage/$webform_id/submission/$sid");
    $this->assertResponse(200);
  }

}
