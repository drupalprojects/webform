<?php

namespace Drupal\webform\Tests;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform editor.
 *
 * @group Webform
 */
class WebformEditorTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_text_format'];

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileUsage = $this->container->get('file.usage');
  }

  /**
   * Tests webform entity settings files.
   */
  public function testWebformSettingsFiles() {
    $this->drupalLogin($this->rootUser);

    // Create three test images.
    /** @var \Drupal\file\FileInterface[] $images */
    $images = $this->drupalGetTestFiles('image');
    $images = array_slice($images, 0, 5);
    foreach ($images as $index => $image_file) {
      $images[$index] = File::create((array) $image_file);
      $images[$index]->save();
    }

    // Check that all images are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Upload the first image.
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first image is not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check create first image file usage.
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');

    // Upload the second image.
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/><img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first and second image are not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Remove the first image.
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first is temporary and second image is not temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical([], $this->fileUsage->listUsage($images[0]), 'The file has 0 usage.');
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Delete the webform.
    Webform::load('contact')->delete();
    $this->reloadImages($images);

    // Check that first and second image are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());
  }

  /**
   * Tests webform configuration files.
   */
  public function testWebformConfigurationFiles() {
    $this->drupalLogin($this->rootUser);

    // Create three test images.
    /** @var \Drupal\file\FileInterface[] $images */
    $images = $this->drupalGetTestFiles('image');
    $images = array_slice($images, 0, 5);
    foreach ($images as $index => $image_file) {
      $images[$index] = File::create((array) $image_file);
      $images[$index]->save();
    }

    // Check that all images are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Upload the first image.
    $edit = [
      'form_settings[default_form_open_message][value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/config', $edit, t('Save configuration'));
    $this->reloadImages($images);

    // Check that first image is not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check create first image file usage.
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');

    // Upload the second image.
    $edit = [
      'form_settings[default_form_open_message][value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/><img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/config', $edit, t('Save configuration'));
    $this->reloadImages($images);

    // Check that first and second image are not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Remove the first image.
    $edit = [
      'form_settings[default_form_open_message][value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/config', $edit, t('Save configuration'));
    $this->reloadImages($images);

    // Check that first is temporary and second image is not temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical([], $this->fileUsage->listUsage($images[0]), 'The file has 0 usage.');
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Simulate deleting webform.settings.yml during webform uninstall.
    // @see webform_uninstall()
    $config = \Drupal::configFactory()->get('webform.settings');
    _webform_config_delete($config);
    $this->reloadImages($images);

    // Check that first and second image are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());
  }

  /**
   * Tests webform text format element files.
   */
  public function testTextFormatFiles() {
    $this->createFilters();

    $webform = Webform::load('test_element_text_format');

    $this->drupalLogin($this->rootUser);

    // Create three test images.
    /** @var \Drupal\file\FileInterface[] $images */
    $images = $this->drupalGetTestFiles('image');
    $images = array_slice($images, 0, 5);
    foreach ($images as $index => $image_file) {
      $images[$index] = File::create((array) $image_file);
      $images[$index]->save();
    }
    // Check that all images are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Upload the first image.
    $edit = [
      'text_format[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
      'text_format[format]' => 'full_html',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $this->reloadImages($images);

    // Check that first image is not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check create first image file usage.
    $this->assertIdentical(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');

    // Upload the second image.
    $edit = [
      'text_format[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/><img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
      'text_format[format]' => 'full_html',
    ];
    $this->drupalPostForm("/admin/structure/webform/manage/test_element_text_format/submission/$sid/edit", $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first and second image are not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');
    $this->assertIdentical(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Remove the first image.
    $edit = [
      'text_format[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
      'text_format[format]' => 'full_html',
    ];
    $this->drupalPostForm("/admin/structure/webform/manage/test_element_text_format/submission/$sid/edit", $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first is temporary and second image is not temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical([], $this->fileUsage->listUsage($images[0]), 'The file has 0 usage.');
    $this->assertIdentical(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Duplicate submission.
    $webform_submission = WebformSubmission::load($sid);
    $webform_submission_duplicate = $webform_submission->createDuplicate();
    $webform_submission_duplicate->save();

    // Check second image file usage.
    $this->assertIdentical(['editor' => ['webform_submission' => [$webform_submission->id() => '1', $webform_submission_duplicate->id() => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 2 usages.');

    // Delete the duplicate webform submission.
    $webform_submission_duplicate->delete();

    // Check second image file usage.
    $this->assertIdentical(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Delete the webform submission.
    $this->drupalPostForm("/admin/structure/webform/manage/test_element_text_format/submission/$sid/delete", [], t('Delete'));
    $this->reloadImages($images);

    // Check that first and second image are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Reload images.
   *
   * @param array $images
   *   An array of image files.
   */
  protected function reloadImages(array &$images) {
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    foreach ($images as $index => $image) {
      $images[$index] = File::load($image->id());
    }
  }

}
