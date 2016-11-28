<?php

namespace Drupal\webform_node\Tests;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform node.
 *
 * @group WebformNode
 */
class WebformNodeTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'block', 'node', 'user', 'webform', 'webform_test', 'webform_node'];

  /**
   * Tests webform node.
   */
  public function testNode() {
    // Create node.
    $node = $this->drupalCreateNode(['type' => 'webform']);

    // Check contact webform.
    $node->webform->target_id = 'contact';
    $node->webform->status = 1;
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('webform-submission-contact-form');
    $this->assertNoFieldByName('name', 'John Smith');

    // Check contact webform with default data.
    $node->webform->default_data = "name: 'John Smith'";
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertFieldByName('name', 'John Smith');

    /* Webform closed */

    // Check contact webform closed.
    $node->webform->status = 0;
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertNoFieldByName('name', 'John Smith');
    $this->assertRaw('Sorry...This form is closed to new submissions.');

    /* Confirmation inline (test_confirmation_inline) */

    // Check confirmation inline webform.
    $node->webform->target_id = 'test_confirmation_inline';
    $node->webform->default_data = '';
    $node->webform->status = 1;
    $node->save();
    $this->drupalPostForm('node/' . $node->id(), [], t('Submit'));
    $this->assertRaw('This is a custom inline confirmation message.');

    /* Submission limit (test_submission_limit) */

    // Set per entity total and user limit.
    // @see \Drupal\webform\Tests\WebformSubmissionFormSettingsTest::testSettings
    $node->webform->target_id = 'test_submission_limit';
    $node->webform->default_data = '';
    $node->save();

    $limit_form = Webform::load('test_submission_limit');
    $limit_form->setSettings([
      'limit_total' => NULL,
      'limit_user' => NULL,
      'entity_limit_total' => 3,
      'entity_limit_user' => 1,
      'limit_total_message' => 'Only 3 submissions are allowed.',
      'limit_user_message' => 'You are only allowed to have 1 submission for this webform.',
    ]);
    $limit_form->save();

    // Check per entity user limit.
    $this->drupalLogin($this->normalUser);
    $this->drupalPostForm('node/' . $node->id(), [], t('Submit'));
    $this->drupalGet('node/' . $node->id());
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');
    $this->drupalLogout();

    // Check per entity total limit.
    $this->drupalPostForm('node/' . $node->id(), [], t('Submit'));
    $this->drupalPostForm('node/' . $node->id(), [], t('Submit'));
    $this->drupalGet('node/' . $node->id());
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('Only 3 submissions are allowed.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    /* Prepopulate source entity */

    $webform_contact = Webform::load('contact');

    $node->webform->target_id = 'contact';
    $node->webform->status = 1;
    $node->webform->default_data = "name: '{name}'";
    $node->save();

    $source_entity_options = ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]];

    // Check default data from source entity using query string.
    $this->drupalGet('webform/contact', $source_entity_options);
    $this->assertFieldByName('name', '{name}');

    // Check prepopulating source entity using query string.
    $edit = [
      'name' => 'name',
      'email' => 'example@example.com',
      'subject' => 'subject',
      'message' => 'message',
    ];
    $this->drupalPostForm('webform/contact', $edit, t('Send message'), $source_entity_options);
    $sid = $this->getLastSubmissionId($webform_contact);
    $submission = WebformSubmission::load($sid);
    $this->assertNotNull($submission->getSourceEntity());
    if ($submission->getSourceEntity()) {
      $this->assertEqual($submission->getSourceEntity()
        ->getEntityTypeId(), 'node');
      $this->assertEqual($submission->getSourceEntity()->id(), $node->id());
    }

    /* Check displaying link to webform */

    // Set webform reference to be displayed as a link.
    $display_options = [
      'type' => 'webform_entity_reference_link',
      'settings' => [
        'label' => 'Register',
      ],
    ];
    $view_display = EntityViewDisplay::load('node.webform.default');
    $view_display->setComponent('webform', $display_options)->save();

    // Set default data.
    $node->webform->target_id = 'contact';
    $node->webform->status = 1;
    $node->webform->default_data = "name: '{name}'";
    $node->save();

    // Check 'Register' link.
    $this->drupalGet('node/' . $node->id());
    $this->assertLink('Register');

    // Check that link include source_entity_type and source_entity_id.
    $this->assertLinkByHref($webform_contact->toUrl('canonical', $source_entity_options)->toString());
  }

}
