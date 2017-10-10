<?php

namespace Drupal\webform_editorial\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformHelpManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;

/**
 * Provides route responses for webform editorial.
 */
class WebformEditorialController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  
  /**
   * The webform help manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $helpManager;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * Constructs a WebformEditorialController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\webform\WebformHelpManagerInterface $help_manager
   *   The webform help manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   */
  public function __construct(RendererInterface $renderer, WebformHelpManagerInterface $help_manager, WebformElementManagerInterface $element_manager, WebformLibrariesManagerInterface $libraries_manager) {
    $this->renderer = $renderer;
    $this->helpManager = $help_manager;
    $this->elementManager = $element_manager;
    $this->librariesManager = $libraries_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('webform.help_manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.libraries_manager')
    );
  }


  /****************************************************************************/
  // Help.
  /****************************************************************************/

  /**
   * Returns webform help editorial.
   **
   * @return array
   *   A renderable array containing webform help editorial.
   */
  public function help() {
    $help = $this->helpManager->getHelp();
    $groups = $this->helpManager->getGroup();
    foreach ($groups as $group_name => $group) {
      $groups[$group_name] = [];
    }
    foreach ($help as $name => $info) {
      $group_name = (isset($info['group'])) ? $info['group'] : (string) $this->t('General');
      $groups[$group_name][$name] = $info;
    }
    $groups = array_filter($groups);

    $build = [];
    $build['title'] = [
      '#prefix' => '<h1>',
      '#suffix' => '</h1>',
      '#markup' => $this->t('Webform: Help editorial'),
    ];
    foreach ($groups as $group_name => $help) {
      // Header.
      $header = [
        ['data' => $this->t('Name'), 'width' => '20%'],
        ['data' => $this->t('Title'), 'width' => '20%'],
        ['data' => $this->t('Content'), 'width' => '50%'],
        ['data' => $this->t('Links'), 'width' => '10%'],
      ];

      // Rows.
      $rows = [];
      foreach ($help as $name => $info) {
        // Paths.
        $paths = [];
        if (!empty($info['paths'])) {
          $paths = array_merge($paths, $info['paths']);
        }
        if (!empty($info['routes'])) {
          $paths = array_merge($paths, \Drupal::database()->query("SELECT path FROM {router} WHERE name IN (:name[])", [':name[]' => $info['routes']])->fetchCol() ?: []);
        }
        asort($paths);
        foreach ($paths as $index => $path) {
          $path = str_replace('/admin/structure/webform/', '../', $path);
          $path = str_replace('/admin/structure/', '../', $path);
          $path = preg_replace('/\{[^}]+\}/', '*', $path);
          $paths[$index] = $path;
        }
        $name = '<b>' . $name .'</b><br/><small><small><em>' . implode('<br />', $paths) .'</em></small></small>';

        // Links
        $links = [];
        if (!empty($info['video_id'])) {
          $video = $this->helpManager->getVideo($info['video_id']);
          $links[] = Link::fromTextAndUrl('Video', Url::fromUri('https://www.youtube.com/watch', ['query' => ['v' => $video['youtube_id']]]))->toString();
        }
        $rows[] = [
          'data' => [
            ['data' => $name],
            ['data' => $info['title']],
            ['data' => $info['content']],
            ['data' => implode(' | ', $links)],
          ],
        ];
      }
      $title = ($this->helpManager->getGroup($group_name) ?: $group_name) . ' [' . $group_name . ']';
      $build[$group_name] = $this->buildTable($title, $header, $rows, 'h2');
    }

    // Add presentations.
    $presentations = $this->helpManager->initVideoPresentations();
    foreach ($presentations as $presentation_name => $presentation) {

      $build[$presentation_name]['description']['title'] = [
        '#markup' => $presentation['title'],
        '#prefix' => '<strong>',
        '#suffix' => '</strong><br/>',
      ];
      $build[$presentation_name]['description']['content'] = [
        '#markup' => $presentation['content'],
        '#suffix' => '<br/><br/>',
      ];
    }


    return $this->response($build);
  }

  /****************************************************************************/
  // Videos.
  /****************************************************************************/

  /**
   * Returns webform videos editorial.
   **
   * @return array
   *   A renderable array containing webform elements videos.
   */
  public function videos() {
    // Header.
    $header = [
      ['data' => $this->t('Name'), 'width' => '10%'],
      ['data' => $this->t('Title'), 'width' => '20%'],
      ['data' => $this->t('Content'), 'width' => '50%'],
      ['data' => $this->t('Video'), 'width' => '20%'],
    ];

    // Rows.
    $rows = [];
    $videos = $this->helpManager->getVideo();
    foreach ($videos as $name => $info) {
      // @see https://stackoverflow.com/questions/2068344/how-do-i-get-a-youtube-video-thumbnail-from-the-youtube-api
      $image = Markup::create('<img width="180" src="https://img.youtube.com/vi/' . $info['youtube_id'] .'/0.jpg" />');
      $video = Link::fromTextAndUrl($image, Url::fromUri('https://www.youtube.com/watch', ['query' => ['v' => $info['youtube_id']]]))->toString();
      $rows[] = [
        'data' => [
          ['data' => '<b>' . $name . '</b>' . (!empty($info['hidden']) ? '<br/>[' . $this->t('hidden') . ']' : '')],
          ['data' => $info['title']],
          ['data' => $info['content']],
          ['data' => $video],
        ],
      ];
    }

    $build = $this->buildTable('Webform: Videos editorial', $header, $rows);
    return $this->response($build);
  }

  /****************************************************************************/
  // Elements.
  /****************************************************************************/

  /**
   * Returns webform elements editorial.
   **
   * @return array
   *   A renderable array containing webform elements editorial.
   */
  public function elements() {
    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);
    unset($grouped_definitions['Other elements']);

    $build = [];
    $build['title'] = [
      '#prefix' => '<h1>',
      '#suffix' => '</h1>',
      '#markup' => $this->t('Webform: Elements editorial'),
    ];
    foreach ($grouped_definitions as $category_name => $elements) {

      // Header.
      $header = [
        ['data' => $this->t('Name'), 'width' => '25%'],
        ['data' => $this->t('Label'), 'width' => '25%'],
        ['data' => $this->t('Description'), 'width' => '50%'],
      ];

      // Rows.
      $rows = [];
      foreach ($elements as $name => $element) {
        $rows[] = [
          'data' => [
            ['data' => '<b>' . $name . '</b>'],
            ['data' => $element['label']],
            ['data' => $element['description']],
          ],
        ];
      }

      $build[$category_name] = $this->buildTable($category_name, $header, $rows, 'h2');
    }

    return $this->response($build);
  }


  /****************************************************************************/
  // Libraries.
  /****************************************************************************/

  /**
   * Returns webform libraries.
   **
   * @return array
   *   A renderable array containing webform libraries editorial.
   */
  public function libraries() {
    // Header.
    $header = [
      ['data' => $this->t('Name'), 'width' => '10%'],
      ['data' => $this->t('Title'), 'width' => '10%'],
      ['data' => $this->t('Description'), 'width' => '40%'],
      ['data' => $this->t('Notes'), 'width' => '40%'],
    ];

    // Rows.
    $rows = [];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $name => $library) {
      $rows[] = [
        'data' => [
          ['data' => $name],
          ['data' => '<a href="' . $library['homepage_url']->toString() . '">' . $library['title'] . '</a>'],
          ['data' => $library['description']],
          ['data' => $library['notes']],
        ],
      ];
    }

    $build = $this->buildTable('Webform: Libraries editorial', $header, $rows);
    return $this->response($build);
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Build a reusable and styled table for inputs, outputs, and publications.
   *
   * @param string $title
   *   The table's title.
   * @param array $header
   *   The table's header.
   * @param array $rows
   *   The table's rows.
   * @param string $title_tag
   *   The header tag for title.
   *
   * @return array
   *   A renderable array representing a table with title.
   */
  protected function buildTable($title, array $header, array $rows, $title_tag = 'h1') {
    // Add style and alignment to header.
    foreach ($header as &$column) {
      $column['style'] = 'background-color: #ccc';
      $column['valign'] = 'top';
      $column['align'] = 'left';
    }

    // Add style and alignment to rows.
    foreach ($rows as &$row) {
      foreach ($row['data'] as &$column) {
        if (isset($column['data'])) {
          $column['data'] = ['#markup' => $column['data'], '#allowed_tags' => Xss::getAdminTagList()];
        }
      }
      $row['valign'] = 'top';
      $row['align'] = 'left';
    }

    return [
      'title' => [
        '#prefix' => "<$title_tag>",
        '#suffix' => "</$title_tag>",
        '#markup' => $title,
      ],
      'description' => [],
      'table' => [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => [
          'border' => 1,
          'cellspacing' => 0,
          'cellpadding' => 5,
          'width' => '950',
        ],
      ],
      '#suffix' => '<br/><hr/>',
    ];
  }


  /**
   * Build a custom response the returns raw HTML markup.
   *
   * @param array $build
   *   A renderable array.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   a custom response that contains raw HTML markup.
   */
  protected function response(array $build) {
    $output = $this->renderer->renderPlain($build);
    $headers = [
      'Content-Length' => strlen($output),
      'Content-Type' => 'text/html',
    ];
    return new Response($output, 200, $headers);
  }

}
