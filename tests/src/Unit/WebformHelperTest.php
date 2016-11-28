<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\webform\Utility\WebformHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform helper utility.
 *
 * @group WebformUnit
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformHelper
 */
class WebformHelperTest extends UnitTestCase {

  /**
   * Tests WebformHelper with WebformHelper::cleanupFormStateValues().
   *
   * @param array $values
   *   The array to run through WebformHelper::cleanupFormStateValues().
   * @param array $keys
   *   (optional) An array of custom keys to be removed.
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformHelper::cleanupFormStateValues()
   *
   * @dataProvider providerCleanupFormStateValues
   */
  public function testCleanupFormStateValues(array $values, array $keys, $expected) {
    $result = WebformHelper::cleanupFormStateValues($values, $keys);
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
