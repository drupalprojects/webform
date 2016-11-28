<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform array utility.
 *
 * @group WebformUnit
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformArrayHelper
 */
class WebformArrayHelperTest extends UnitTestCase {

  /**
   * Tests converting arrays to readable string with WebformArrayHelper::toString().
   *
   * @param array $array
   *   The array to run through WebformArrayHelper::toString().
   * @param string $conjunction
   *   The $conjunction to run through WebformArrayHelper::toString().
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $message
   *   The message to display as output to the test.
   *
   * @see WebformArrayHelper::toString()
   *
   * @dataProvider providerToString
   */
  public function testToString(array $array, $conjunction, $expected, $message) {
    $result = WebformArrayHelper::toString($array, $conjunction);
    $this->assertEquals($expected, $result, $message);
  }

  /**
   * Data provider for testToString().
   *
   * @see testToString()
   */
  public function providerToString() {
    $tests[] = [['Jack', 'Jill'], 'and', 'Jack and Jill', 'WebformArrayHelper::toString with Jack and Jill'];
    $tests[] = [['Jack', 'Jill'], 'or', 'Jack or Jill', 'WebformArrayHelper::toString with Jack or Jill'];
    $tests[] = [['Jack', 'Jill', 'Bill'], 'and', 'Jack, Jill, and Bill', 'WebformArrayHelper::toString with Jack and Jill'];
    $tests[] = [[''], 'and', '', 'WebformArrayHelper::toString with no one'];
    $tests[] = [['Jack'], 'and', 'Jack', 'WebformArrayHelper::toString with just Jack'];
    return $tests;
  }

}
