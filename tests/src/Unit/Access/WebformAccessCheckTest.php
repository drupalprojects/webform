<?php

namespace Drupal\Tests\webform\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Access\WebformAccountAccess;
use Drupal\webform\Access\WebformSubmissionAccess;

/**
 * @coversDefaultClass \Drupal\webform\Access\WebformAccountAccess
 *
 * @group webform
 */
class WebformAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\user\Access\PermissionAccessCheck
   */
  public $accessCheck;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    \Drupal::setContainer($container);
  }


  /**
   * Tests the check admin access.
   *
   * @covers ::checkAdminAccess
   */
  public function testCheckAdminAccess() {
    $account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $admin_account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $admin_account->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
          ['administer webform', TRUE],
          ['administer webform submission', TRUE],
      ]
      ));

    $submission_manager_account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $submission_manager_account->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
          ['access webform overview', TRUE],
          ['view any webform submission', TRUE],
      ]
      ));

    $webform_node = $this->getMockBuilder('Drupal\node\NodeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $webform_node->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $webform_node->expects($this->any())
      ->method('hasField')
      ->will($this->returnValue(TRUE));
    $webform_node->webform = (object) ['entity' => TRUE];

    $webform = $this->createMock('Drupal\webform\WebformInterface');

    $email_webform = $this->createMock('Drupal\webform\WebformInterface');
    $handler = $this->createMock('\Drupal\webform\Plugin\WebformHandlerMessageInterface');
    $email_webform->expects($this->any())
      ->method('getHandlers')
      ->will($this->returnValue([$handler]));
    $email_webform->expects($this->any())
      ->method('access')
      ->with('submission_update_any')
      ->will($this->returnValue(TRUE));
    $email_webform->expects($this->any())
      ->method('hasMessageHandler')
      ->will($this->returnValue(TRUE));

    $webform_submission = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $webform_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($webform));
    $email_webform_submission = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $email_webform_submission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($email_webform));

    // Check submission access.
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), WebformAccountAccess::checkAdminAccess($account));
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), WebformAccountAccess::checkAdminAccess($admin_account));

    // Check submission access.
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), WebformAccountAccess::checkSubmissionAccess($account));
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), WebformAccountAccess::checkSubmissionAccess($submission_manager_account));

    // Check overview access.
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), WebformAccountAccess::checkOverviewAccess($account));
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), WebformAccountAccess::checkOverviewAccess($submission_manager_account));

    // Check resend (email) message access.
    $this->assertEquals(AccessResult::forbidden(), WebformSubmissionAccess::checkResendAccess($webform_submission, $account));
    $this->assertEquals(AccessResult::allowed(), WebformSubmissionAccess::checkResendAccess($email_webform_submission, $submission_manager_account));

    // @todo Fix below access check which is looping through the node's fields.
    // Check entity results access.
    // $this->assertEquals(AccessResult::neutral(), WebformSourceEntityAccess::checkEntityResultsAccess($node, $account));
    // $this->assertEquals(AccessResult::allowed(), WebformSourceEntityAccess::checkEntityResultsAccess($webform_node, $submission_manager_account));
  }

}
