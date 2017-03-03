<?php

namespace Drupal\webform\Tests\Element;

use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for term_select element.
 *
 * @group Webform
 */
class WebformElementTermSelectTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_term_select'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    $this->terms = [];
    for ($i = 1; $i <= 3; $i++) {
      $parent_term = Term::create([
        'name' => "Parent $i",
        'vid' => 'tags',
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $parent_term->save();
      $this->terms[] = $parent_term;
      for ($x = 1; $x <= 3; $x++) {
        $child_term = Term::create([
          'name' => "Parent $i: Child $x",
          'parent' => $parent_term->id(),
          'vid' => 'tags',
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ]);
        $child_term->save();
        $this->terms[] = $child_term;
      }
    }
  }

  /**
   * Test terme_select element.
   */
  public function testTermSelectElement() {
    $webform = Webform::load('test_element_term_select');

    $this->drupalGet('webform/test_element_term_select');

    // Check term tree default.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">-Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">-Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">-Parent 1: Child 3</option>');

    // Check term tree advanced.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">..Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">..Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">..Parent 1: Child 3</option>');

    // Check term breadcrumb default.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">Parent 1 › Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">Parent 1 › Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">Parent 1 › Parent 1: Child 3</option>');

    // Check term breadcrumb advanced.
    $this->assertRaw('<option value="1">Parent 1</option>');
    $this->assertRaw('<option value="2">Parent 1 » Parent 1: Child 1</option>');
    $this->assertRaw('<option value="3">Parent 1 » Parent 1: Child 2</option>');
    $this->assertRaw('<option value="4">Parent 1 » Parent 1: Child 3</option>');

    $edit = [
      'webform_term_select_breadcrumb_advanced[]' => [2, 3],
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertRaw('<b>webform_term_select_breadcrumb_advanced</b><br/><div class="item-list"><ul><li>Parent 1 › Parent 1: Child 1</li><li>Parent 1 › Parent 1: Child 2</li></ul></div>');
  }

}
