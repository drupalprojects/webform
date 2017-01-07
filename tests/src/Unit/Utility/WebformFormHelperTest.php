<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformFormHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform helper utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformFormHelper
 */
class WebformFormHelperTest extends UnitTestCase {

  /**
   * Tests WebformFormHelper with WebformFormHelper::cleanupFormStateValues().
   *
   * @param array $values
   *   The array to run through WebformFormHelper::cleanupFormStateValues().
   * @param array $keys
   *   (optional) An array of custom keys to be removed.
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformFormHelper::cleanupFormStateValues()
   *
   * @dataProvider providerCleanupFormStateValues
   */
  public function testCleanupFormStateValues(array $values, array $keys, $expected) {
    $result = WebformFormHelper::cleanupFormStateValues($values, $keys);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testCleanupFormStateValues().
   *
   * @see testCleanupFormStateValues()
   */
  public function providerCleanupFormStateValues() {
    $tests[] = [['key' => 'value'], [], ['key' => 'value']];
    $tests[] = [['key' => 'value', 'form_token' => 'ignored'], [], ['key' => 'value']];
    $tests[] = [['key' => 'value', 'form_token' => 'ignored'], ['key'], []];
    return $tests;
  }

}
