<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Test for webform element managed file image handling.
 *
 * @group Webform
 */
class WebformElementManagedFileImageTest extends WebformElementManagedFileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_image_file'];

  /**
   * Test image file upload.
   */
  public function testImageFileUpload() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_image_file');

    // Get test image.
    $images = $this->drupalGetTestFiles('image');
    $image = reset($images);

    // Check image max resolution validation is being applied.
    $edit = [
      'files[webform_image_file_advanced]' => \Drupal::service('file_system')->realpath($image->uri),
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('The image was resized to fit within the maximum allowed dimensions of <em class="placeholder">20x20</em> pixels.');
  }

}
