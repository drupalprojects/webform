<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for signature element.
 *
 * @group Webform
 */
class WebformElementSignatureTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_signature'];

  /**
   * Test signature element.
   */
  public function testSignature() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_signature');

    $signature_path = '/webform/test_element_signature/signature';
    $signature_directory = 'public://webform/test_element_signature/signature';

    // Check signature display.
    $this->drupalGet('webform/test_element_signature');
    $this->assertRaw('<input data-drupal-selector="edit-signature" aria-describedby="edit-signature--description" type="hidden" name="signature" value="" class="js-webform-signature form-webform-signature" />');
    $this->assertRaw('<input type="submit" name="op" value="Reset" class="button js-form-submit form-submit" />');
    $this->assertRaw('<canvas></canvas>');
    $this->assertRaw('</div>');
    $this->assertRaw('<div id="edit-signature--description" class="description">');
    $this->assertRaw('Sign above');

    // Check signature preview image.
    $this->postSubmissionTest($webform, [], t('Preview'));
    $this->assertRaw("$signature_path/signature-");
    $this->assertRaw(' alt="Signature" class="webform-signature-image" />');
    $this->assertEqual(count(file_scan_directory($signature_directory, '/^signature-.*\.png$/')), 1);

    // Check signature saved image.
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertRaw("$signature_path/$sid/signature-");
    $this->assertTrue(file_exists("$signature_directory/$sid"));
    $this->assertEqual(count(file_scan_directory($signature_directory, '/^signature-.*\.png$/')), 1);

    // Check deleting the submission deletes submission's signature directory.
    $webform_submission->delete();
    $this->assertTrue(file_exists("$signature_directory"));
    $this->assertFalse(file_exists("$signature_directory/$sid"));
    $this->assertEqual(count(file_scan_directory($signature_directory, '/^signature-.*\.png$/')), 0);

    // Check deleting the webform deletes webform's signature directory.
    $webform->delete();
    $this->assertFalse(file_exists("$signature_directory"));
  }

}
