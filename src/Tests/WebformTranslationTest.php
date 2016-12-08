<?php

namespace Drupal\webform\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform translation.
 *
 * @group Webform
 */
class WebformTranslationTest extends WebTestBase {

  use WebformTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'block', 'webform', 'webform_examples', 'webform_test_translation'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->placeBlocks();

    $admin_user = $this->drupalCreateUser(['access content', 'administer webform', 'administer webform submission', 'translate configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests webform translate.
   */
  public function testTranslate() {
    /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
    $translation_manager = \Drupal::service('webform.translation_manager');

    $webform = Webform::load('test_translation');
    $elements_raw = \Drupal::config('webform.webform.test_translation')->get('elements');
    $elements = Yaml::decode($elements_raw);

    // Check translate tab.
    $this->drupalGet('admin/structure/webform/manage/test_translation');
    $this->assertRaw('>Translate<');

    // Check translations.
    $this->drupalGet('admin/structure/webform/manage/test_translation/translate');
    $this->assertRaw('<a href="' . base_path() . 'admin/structure/webform/manage/test_translation/translate/es/edit">Edit</a>');

    // Check Spanish translations.
    $this->drupalGet('admin/structure/webform/manage/test_translation/translate/es/edit');
    $this->assertFieldByName('translation[config_names][webform.webform.test_translation][title]', 'Prueba: Traducción');
    $this->assertField('translation[config_names][webform.webform.test_translation][elements]');

    // Check translated webform options.
    $this->drupalGet('es/webform/test_translation');
    $this->assertRaw('<label for="edit-textfield">Campo de texto</label>');
    $this->assertRaw('<option value="1">Uno</option>');
    $this->assertRaw('<option value="4">Las cuatro</option>');

    // Check that webform is not translated into French.
    $this->drupalGet('fr/webform/test_translation');
    $this->assertRaw('<label for="edit-textfield">Text field</label>');
    $this->assertRaw('<option value="1">One</option>');
    $this->assertRaw('<option value="4">Four</option>');

    // Check that French config elements returns the default languages elements.
    // Please note: This behavior might change.
    $translation_element = $translation_manager->getConfigElements($webform, 'fr', TRUE);
    $this->assertEqual($elements, $translation_element);

    // Create French translation.
    $translation_elements = [
      'textfield' => [
        '#title' => 'French',
        '#custom' => 'custom',
      ],
      'custom' => [
        '#title' => 'Custom',
      ],
    ] + $elements;
    $edit = [
      'translation[config_names][webform.webform.test_translation][elements]' => Yaml::encode($translation_elements),
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_translation/translate/fr/add', $edit, t('Save translation'));

    // Check French translation.
    $this->drupalGet('fr/webform/test_translation');
    $this->assertRaw('<label for="edit-textfield">French</label>');

    // Check French config elements only contains translated properties and
    // custom properties are removed.
    $translation_element = $translation_manager->getConfigElements($webform, 'fr', TRUE);
    $this->assertEqual(['textfield' => ['#title' => 'French']], $translation_element);
  }

}
