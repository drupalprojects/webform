<?php

namespace Drupal\webform_node\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform node access rules.
 *
 * @group WebformNode
 */
class WebformNodeAccessTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'block', 'node', 'user', 'webform', 'webform_test', 'webform_node'];

  /**
   * Tests webform node access rules.
   *
   * @see \Drupal\webform\Tests\WebformAccessTest::testAccessRules
   */
  public function testAccessRules() {
    // Create webform node that references the contact webform.
    $webform = Webform::load('contact');
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $node->webform->target_id = 'contact';
    $node->webform->status = 1;
    $node->save();
    $nid = $node->id();

    // Log in normal user and get their rid.
    $this->drupalLogin($this->normalUser);
    $roles = $this->normalUser->getRoles(TRUE);
    $rid = reset($roles);
    $uid = $this->normalUser->id();

    // Add one submission to the Webform node.
    $edit = [
      'name' => '{name}',
      'email' => 'example@example.com',
      'subject' => '{subject}',
      'message' => '{message',
    ];
    $this->drupalPostForm('node/' . $node->id(), $edit, t('Send message'));
    $sid = $this->getLastSubmissionId($webform);

    // Check create authenticated/anonymous access.
    $webform->setAccessRules(Webform::getDefaultAccessRules())->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertFieldByName('name', $this->normalUser->getAccountName());
    $this->assertFieldByName('email', $this->normalUser->getEmail());

    $access_rules = [
      'create' => [
        'roles' => [],
        'users' => [],
      ],
    ] + Webform::getDefaultAccessRules();
    $webform->setAccessRules($access_rules)->save();

    // Check no access.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoFieldByName('name', $this->normalUser->getAccountName());
    $this->assertNoFieldByName('email', $this->normalUser->getEmail());

    $any_tests = [
      'node/{node}/webform/results/submissions' => 'view_any',
      'node/{node}/webform/results/table' => 'view_any',
      'node/{node}/webform/results/download' => 'view_any',
      'node/{node}/webform/results/clear' => 'purge_any',
      'node/{node}/webform/submission/{webform_submission}' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/text' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/yaml' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/edit' => 'update_any',
      'node/{node}/webform/submission/{webform_submission}/delete' => 'delete_any',
    ];

    // Check that all the test paths are access denied for authenticated.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{node}', $nid, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      $this->drupalGet($path);
      $this->assertResponse(403, 'Webform returns access denied');
    }

    // Check access rules by role and user id.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{node}', $nid, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [$rid],
          'users' => [],
        ],
      ] + Webform::getDefaultAccessRules();
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $this->assertResponse(200, 'Webform allows access via role access rules');

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [],
          'users' => [$uid],
        ],
      ] + Webform::getDefaultAccessRules();
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $this->assertResponse(200, 'Webform allows access via user access rules');
    }
  }

}
