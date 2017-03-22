<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for email webform handler #options mapping functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailMappingTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_mapping'];

  /**
   * Test email mapping handler.
   */
  public function testEmailMapping() {
    $site_name = \Drupal::config('system.site')->get('name');
    $site_mail = \Drupal::config('system.site')->get('mail');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email_mapping');

    $this->postSubmission($webform);

    // Check that empty select menu email sent.
    $this->assertText("Email mapping handler: Select empty triggered sent to empty@example.com from $site_name [$site_mail].");

    // Check that default select menu email sent.
    $this->assertText("Email mapping handler: Select default triggered sent to default@default.com from $site_name [$site_mail].");

    // Check that no email sent.
    $this->assertText("Message not sent Email mapping handler: Select option triggered because a To, CC, or BCC email was not provided.");
    $this->assertText("Message not sent Email mapping handler: Checkboxes triggered because a To, CC, or BCC email was not provided.");
    $this->assertText("Message not sent Email mapping handler: Radios other triggered because a To, CC, or BCC email was not provided.");

    // Check that single select menu option email sent.
    $edit = [
      'select' => 'Yes',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertText("Email mapping handler: Select option triggered sent to yes@example.com from $site_name [$site_mail].");
    $this->assertText("Email mapping handler: Select default triggered sent to default@default.com from $site_name [$site_mail].");
    $this->assertNoText("Email mapping handler: Select empty triggered sent to empty@example.com from $site_name [$site_mail].");

    // Check that mulitple radios checked email sent.
    $edit = [
      'checkboxes[Saturday]' => TRUE,
      'checkboxes[Sunday]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertText("Email mapping handler: Checkboxes triggered sent to saturday@example.com,sunday@example.com from $site_name [$site_mail].");
    $this->assertNoText("Message not sent Email mapping handler: Checkboxes triggered because a To, CC, or BCC email was not provided.");

    // Check that checkbxoes other option email sent.
    $edit = [
      'radios_other[radios]' => '_other_',
      'radios_other[other]' => '{Other}',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertText("Email mapping handler: Radios other triggered sent to other@example.com from $site_name [$site_mail].");
    $this->assertNoText("Message not sent Email mapping handler: Radios other triggered because a To, CC, or BCC email was not provided.");
  }

}
