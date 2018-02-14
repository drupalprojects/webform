<?php

namespace Drupal\Tests\webform\Unit\Plugin\Block;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Plugin\Block\WebformBlock;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Tests webform submission bulk form actions.
 *
 * @coversDefaultClass \Drupal\webform\Plugin\Block\WebformBlock
 *
 * @group webform
 */
class WebformBlockTest extends UnitTestCase {

  /**
   * Tests the dependencies of a webform block.
   */
  public function testCalculateDependencies() {
    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform->method('id')
      ->willReturn($this->randomMachineName());
    $webform->method('getConfigDependencyKey')
      ->willReturn('config');
    $webform->method('getConfigDependencyName')
      ->willReturn('config.webform.' . $webform->id());

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['webform', $storage],
      ]);

    $storage->method('load')
      ->willReturnMap([
        [$webform->id(), $webform],
      ]);

    $token_manager = $this->getMockBuilder(WebformTokenManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $block = new WebformBlock([
      'webform_id' => $webform->id(),
      'default_data' => [],
    ], 'webform_block', [
      'provider' => 'unit_test',
    ], $entity_type_manager, $token_manager);

    $dependencies = $block->calculateDependencies();
    $expected = [
      $webform->getConfigDependencyKey() => [$webform->getConfigDependencyName()],
    ];
    $this->assertEquals($expected, $dependencies, 'WebformBlock reports proper dependencies.');
  }

}
