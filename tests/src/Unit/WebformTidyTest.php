<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformTidy;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform tidy utility.
 *
 * @group WebformUnit
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformTidy
 */
class WebformTidyTest extends UnitTestCase {

  /**
   * Tests WebformTidy tidy with WebformTidy::tidy().
   *
   * @param array $data
   *   The array to run through WebformTidy::tidy().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformTidy::tidy()
   *
   * @dataProvider providerTidy
   */
  public function testTidy(array $data, $expected) {
    $result = WebformTidy::tidy(Yaml::encode($data));
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testTidy().
   *
   * @see testTidy()
   */
  public function providerTidy() {
    $tests[] = [
      ['simple' => 'value'],
      "simple: value",
    ];
    $tests[] = [
      ['returns' => "line 1\nline 2"],
      "returns: |\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['one two' => "line 1\nline 2"],
      "'one two': |\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['array' => ['one', 'two']],
      "array:\n  - one\n  - two",
    ];
    return $tests;
  }

}
