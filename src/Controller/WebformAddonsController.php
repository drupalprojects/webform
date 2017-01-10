<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformAddonsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform add-on.
 */
class WebformAddonsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addons;

  /**
   * Constructs a WebformAddonsController object.
   *
   * @param \Drupal\webform\WebformAddonsManagerInterface $addons
   *   The add-ons manager.
   */
  public function __construct(WebformAddonsManagerInterface $addons) {
    $this->addons = $addons;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.addons_manager')
    );
  }

  /**
   * Returns the Webform extend page.
   *
   * @return array
   *   The webform submission webform.
   */
  public function index() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons', 'js-webform-details-toggle', 'webform-details-toggle'],
      ],
    ];
    $build['#attached']['library'][] = 'webform/webform.admin';
    $build['#attached']['library'][] = 'webform/webform.element.details.toggle';

    $categories = $this->addons->getCategories();
    foreach ($categories as $category_name => $category) {
      $build[$category_name] = [
        '#type' => 'details',
        '#title' => $category['title'],
        '#open' => TRUE,
      ];
      $projects = $this->addons->getProjects($category_name);
      foreach ($projects as &$project) {
        $project['description'] .= ' ' . '<br/><small>' . $project['url']->toString() . '</small>';
      }
      $build[$category_name]['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $projects,
      ];
    }
    return $build;
  }

}
