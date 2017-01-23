<?php

namespace Drupal\simpletest;

namespace Drupal\webform\Tests;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Provides methods to create webform and submissions based on default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait WebformTestCreationTrait {

  /**
   * Get nodes keyed by nid.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Associative array of nodes keyed by nid.
   */
  protected function getNodes() {
    if (empty($this->nodes)) {
      $this->drupalCreateContentType(['type' => 'page']);
      for ($i = 0; $i < 3; $i++) {
        $this->nodes[$i] = $this->drupalCreateNode(['type' => 'page', 'title' => 'Node ' . $i, 'status' => NODE_PUBLISHED]);
        $this->drupalGet('node/' . $this->nodes[$i]->id());
      }
    }
    return $this->nodes;
  }

  /**
   * Create a webform with submissions.
   *
   * @param array|null $elements
   *   (optional) Array of elements.
   * @param array $settings
   *   (optional) Webform settings.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  protected function createWebform(array $elements = [], array $settings = []) {
    // Create new webform.
    $id = $this->randomMachineName(8);
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => $id,
      'title' => $id,
      'elements' => Yaml::encode($elements),
      'settings' => $settings + Webform::getDefaultSettings(),
    ]);
    $webform->save();
    return $webform;
  }

  /**
   * Create a webform with submissions.
   *
   * @return array
   *   Array containing the webform and submissions.
   */
  protected function createWebformWithSubmissions() {
    // Load webform.
    $webform = Webform::load('test_results');

    // Load nodes.
    $nodes = $this->getNodes();
    // Create some submissions.
    $names = [
      [
        'George',
        'Washington',
        'Male',
        '1732-02-22',
        $nodes[0],
        ['white'],
        ['q1' => 1, 'q2' => 1, 'q3' => 1],
        ['address' => '{Address}', 'city' => '{City}', 'state_province' => 'New York', 'country' => 'United States', 'postal_code' => '11111-1111'],
      ],
      [
        'Abraham',
        'Lincoln',
        'Male',
        '1809-02-12',
        $nodes[1],
        ['red', 'white', 'blue'],
        ['q1' => 2, 'q2' => 2, 'q3' => 2],
        ['address' => '{Address}', 'city' => '{City}', 'state_province' => 'New York', 'country' => 'United States', 'postal_code' => '11111-1111'],
      ],
      [
        'Hillary',
        'Clinton',
        'Female',
        '1947-10-26',
        $nodes[2],
        ['red'],
        ['q1' => 2, 'q2' => 2, 'q3' => 2],
        ['address' => '{Address}', 'city' => '{City}', 'state_province' => 'New York', 'country' => 'United States', 'postal_code' => '11111-1111'],
      ],
    ];
    $sids = [];
    foreach ($names as $name) {
      $edit = [
        'first_name' => $name[0],
        'last_name' => $name[1],
        'sex' => $name[2],
        'dob' => $name[3],
        'node' => $name[4]->label() . ' (' . $name[4]->id() . ')',
      ];
      foreach ($name[5] as $color) {
        $edit["colors[$color]"] = $color;
      }
      foreach ($name[6] as $question => $answer) {
        $edit["likert[$question]"] = $answer;
      }
      foreach ($name[7] as $composite_key => $composite_value) {
        $edit["address[$composite_key]"] = $composite_value;
      }
      $sids[] = $this->postSubmission($webform, $edit);
    }

    // Change array keys to index instead of using entity ids.
    $submissions = array_values(WebformSubmission::loadMultiple($sids));

    $this->assert($webform instanceof Webform, 'Webform was created');
    $this->assertEqual(count($submissions), 3, 'WebformSubmissions were created.');

    return [$webform, $submissions];
  }

}
