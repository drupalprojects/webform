<?php

namespace Drupal\webform;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Webform help manager.
 */
class WebformHelpManager implements WebformHelpManagerInterface {

  use StringTranslationTrait;

  /**
   * Groups applied to help and videos.
   *
   * @var array
   */
  protected $groups;

  /**
   * Help for the Webform module.
   *
   * @var array
   */
  protected $help;

  /**
   * Videos for the Webform module.
   *
   * @var array
   */
  protected $videos;

  /**
   * The current version number of the Webform module.
   *
   * @var string
   */
  protected $version;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The Webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addOnsManager;

  /**
   * The Webform libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformHelpManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\webform\WebformAddOnsManagerInterface $addons_manager
   *   The webform add-ons manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StateInterface $state, PathMatcherInterface $path_matcher, WebformAddOnsManagerInterface $addons_manager, WebformLibrariesManagerInterface $libraries_manager, WebformElementManagerInterface $element_manager) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->pathMatcher = $path_matcher;
    $this->addOnsManager = $addons_manager;
    $this->librariesManager = $libraries_manager;
    $this->elementManager = $element_manager;

    $this->groups = $this->initGroups();
    $this->help = $this->initHelp();
    $this->videos = $this->initVideos();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->groups[$id])) ? $this->groups[$id] : NULL;
    }
    else {
      return $this->groups;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->help[$id])) ? $this->help[$id] : NULL;
    }
    else {
      return $this->help;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVideo($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->videos[$id])) ? $this->videos[$id] : NULL;
    }
    else {
      return $this->videos;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHelp($route_name, RouteMatchInterface $route_match) {
    // Get path from route match.
    $path = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', Url::fromRouteMatch($route_match)->setAbsolute(FALSE)->toString());

    $build = [];
    foreach ($this->help as $id => $help) {
      // Set default values.
      $help += [
        'routes' => [],
        'paths' => [],
        'access' => TRUE,
        'message_type' => '',
        'message_close' => FALSE,
        'message_id' => '',
        'message_storage' => '',
        'video_id' => '',
        'attached' => [],
      ];

      if (!$help['access']) {
        continue;
      }

      $is_route_match = in_array($route_name, $help['routes']);
      $is_path_match = ($help['paths'] && $this->pathMatcher->matchPath($path, implode(PHP_EOL, $help['paths'])));
      $has_help = ($is_route_match || $is_path_match);
      if (!$has_help) {
        continue;
      }

      if ($help['message_type']) {
        $build[$id] = [
          '#type' => 'webform_message',
          '#message_type' => $help['message_type'],
          '#message_close' => $help['message_close'],
          '#message_id' => ($help['message_id']) ? $help['message_id'] : 'webform.help.' . $help['id'],
          '#message_storage' => $help['message_storage'],
          '#message_message' => [
            '#theme' => 'webform_help',
            '#info' => $help,
          ],
          '#attached' => $help['attached'],
        ];
        if ($help['message_close']) {
          $build['#cache']['max-age'] = 0;
        }
      }
      else {
        $build[$id] = [
          '#theme' => 'webform_help',
          '#info' => $help,
        ];
      }

    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildIndex() {
    $build['intro'] = [
      '#markup' => $this->t('The Webform module is a form builder and submission manager for Drupal 8.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $build['sections'] = [
      '#prefix' => '<div class="webform-help webform-help-accordion">',
      '#suffix' => '</div>',
    ];
    if ($this->configFactory->get('webform.settings')->get('ui.video_display') !== 'hidden') {
      $build['sections']['videos'] = $this->buildVideos();
    }
    $build['sections']['uses'] = $this->buildUses();
    $build['sections']['elements'] = $this->buildElements();
    $build['sections']['addons'] = $this->buildAddOns();
    $build['sections']['libraries'] = $this->buildLibraries();
    $build['sections']['#attached']['library'][] = 'webform/webform.help';
    return $build;
  }

  /***************************************************************************/
  // Index sections.
  /***************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildElements($docs = FALSE) {
    $build = [
      'title' => [
        '#markup' => $this->t('Form elements'),
        '#prefix' => '<h2 id="elements">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t('Below is a list of all available form and render elements.') . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];

    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);
    unset($grouped_definitions['Other elements']);
    foreach ($grouped_definitions as $category_name => $elements) {
      $build['content'][$category_name]['title'] = [
        '#markup' => $category_name,
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ];
      $build['content'][$category_name]['elements'] = [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ];
      foreach ($elements as $element_name => $element) {
        /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
        $webform_element = $this->elementManager->createInstance($element_name);

        if ($webform_element->isHidden()) {
          continue;
        }

        if ($api_url = $webform_element->getPluginApiUrl()) {
          $build['content'][$category_name]['elements'][$element_name]['title'] = [
            '#type' => 'link',
            '#title' => $element['label'],
            '#url' => $api_url,
          ];
        }
        else {
          $build['content'][$category_name]['elements'][$element_name]['title'] = [
            '#markup' => $element['label'],
          ];
        }
        $build['content'][$category_name]['elements'][$element_name]['title'] += [
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];

        $build['content'][$category_name]['elements'][$element_name]['description'] = [
          '#markup' => $element['description'],
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildUses($docs = FALSE) {
    $build = [
      'title' => [
        '#markup' => $this->t('Uses'),
        '#prefix' => '<h2 id="uses">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'help' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    foreach ($this->help as $id => $help_info) {
      // Check that help item should be displated under 'Uses'.
      if (empty($help_info['uses'])) {
        continue;
      }

      // Title.
      $build['content']['help'][$id]['title'] = [
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      if (isset($help_info['url'])) {
        $build['content']['help'][$id]['title']['link'] = [
          '#type' => 'link',
          '#url' => $help_info['url'],
          '#title' => $help_info['title'],
        ];
      }
      else {
        $build['content']['help'][$id]['title']['#markup'] = $help_info['title'];
      }
      // Content.
      $build['content']['help'][$id]['content'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
        'content' => [
          '#theme' => 'webform_help',
          '#info' => $help_info,
          '#docs' => TRUE,
        ],
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildVideos($docs = FALSE) {
    $build = [
      'title' => [
        '#markup' => $this->t('Watch videos'),
        '#prefix' => '<h2 id="videos">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'help' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    if ($docs) {
      foreach ($this->videos as $id => $video) {
        // Title.
        $build['content']['help'][$id]['title'] = [
          '#type' => 'link',
          '#title' => $video['title'],
          '#url' => Url::fromUri('https://www.youtube.com/watch', ['query' => ['v' => $video['youtube_id']]]),
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];
        // Content.
        $build['content']['help'][$id]['content'] = [
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
          '#markup' => $video['content'],
        ];
      }
    }
    else {
      foreach ($this->videos as $id => $video) {
        // Title.
        $build['content']['help'][$id]['title'] = [
          '#markup' => $video['title'],
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];
        // Content.
        $build['content']['help'][$id]['content'] = [
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
          'content' => [
            '#theme' => 'webform_help',
            '#info' => $video,
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAddOns($docs = FALSE) {
    // Libraries.
    $build = [
      'title' => [
        '#markup' => $this->t('Add-ons'),
        '#prefix' => '<h2 id="addons">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t("Below is a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal's Form API.") . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];

    $categories = $this->addOnsManager->getCategories();
    foreach ($categories as $category_name => $category) {
      $build['content'][$category_name]['title'] = [
        '#markup' => $category['title'],
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ];
      $build['content'][$category_name]['projects'] = [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ];
      $projects = $this->addOnsManager->getProjects($category_name);
      foreach ($projects as $project_name => $project) {
        $build['content'][$category_name]['projects'][$project_name] = [
          'title' => [
            '#type' => 'link',
            '#title' => $project['title'],
            '#url' => $project['url'],
            '#prefix' => '<dt>',
            '#suffix' => '</dt>',
          ],
          'description' => [
            '#markup' => $project['description'] . ((isset($project['notes'])) ? '<br /><em>(' . $project['notes'] . ')</em>' : ''),
            '#prefix' => '<dd>',
            '#suffix' => '</dd>',
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLibraries($docs = FALSE) {
    // Libraries.
    $build = [
      'title' => [
        '#markup' => $this->t('External Libraries'),
        '#prefix' => '<h2 id="libraries">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'description' => [
          '#markup' => '<p>' . $this->t('The Webform module utilizes the third-party Open Source libraries listed below to enhance webform elements and to provide additional functionality.') . ' ' .
            $this->t('It is recommended that these libraries be installed in your Drupal installations /libraries directory.') . ' ' .
            $this->t('If these libraries are not installed, they are automatically loaded from a CDN.') . ' ' .
            $this->t('All libraries are optional and can be excluded via the admin settings form.') .
            '</p>' .
            '<p>' . $this->t('There are three ways to download the needed third party libraries.') . '</p>' .
            '<ul>' .
              '<li>' . $this->t('Generate a *.make.yml or composer.json file using <code>drush webform-libraries-make</code> or <code>drush webform-libraries-composer</code>.') . '</li>' .
              '<li>' . $this->t('Execute <code>drush webform-libraries-download</code>, which will download third party libraries required by the Webform module.') . '</li>' .
              '<li>' . $this->t("Execute <code>drush webform-composer-update</code>, which will update your Drupal installation's composer.json to include the Webform module's selected libraries as repositories.") . '</li>' .
            '</ul>' .
            '<p><hr /><p>',
        ],
        'libraries' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      // Get required elements.
      $elements = [];
      if (!empty($library['elements'])) {
        foreach ($library['elements'] as $element_name) {
          $element = $this->elementManager->getDefinition($element_name);
          $elements[] = $element['label'];
        }
      }

      $build['content']['libraries'][$library_name] = [
        'title' => [
          '#type' => 'link',
          '#title' => $library['title'],
          '#url' => $library['homepage_url'],
          '#prefix' => '<dt>',
          '#suffix' => ' (' . $library['version'] . ')</dt>',
        ],
        'description' => [
          'content' => [
            '#markup' => $library['description'],
            '#suffix' => '<br />'
          ],
          'notes' => [
            '#markup' => $library['notes'] .
              ($elements ? ' <strong>' . $this->formatPlural(count($elements), 'Required by @type element.', 'Required by @type elements.', ['@type' => WebformArrayHelper::toString($elements)]) . '</strong>' : ''),
            '#prefix' => '<em>(',
            '#suffix' => ')</em><br />',
          ],
          'download' => [
            '#type' => 'link',
            '#title' => $library['download_url']->toString(),
            '#url' => $library['download_url'],
          ],
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];
      if ($docs) {
        $build['content']['libraries'][$library_name]['title']['#suffix'] = '</dt>';
        unset($build['content']['libraries'][$library_name]['description']['download']);
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComparison($docs = FALSE) {
    // @see core/themes/seven/css/components/colors.css
    $group_color = '#dcdcdc';
    $feature_color = '#f5f5f5';
    $yes_color = '#d7ffd8';
    $no_color = '#ffffdd';
    $custom_color = '#ffece8';

    $content = file_get_contents('https://docs.google.com/spreadsheets/d/1zNt3WsKxDq2ZmMHeYAorNUUIx5_yiDtDVUIKXtXaq4s/pubhtml?gid=0&single=true');
    if (preg_match('#<table[^>]+>.*</table>#', $content, $match)) {
      $html = $match[0];
    }
    else {
      return [];
    }

    // Remove all attributes.
    $html = preg_replace('#(<[a-z]+) [^>]+>#', '\1>', $html);
    // Remove thead.
    $html = preg_replace('#<thead>.*</thead>#', '', $html);
    // Remove first th cell.
    $html = preg_replace('#<tr><th>.*?</th>#', '<tr>', $html);
    // Remove empty rows.
    $html = preg_replace('#<tr>(<td></td>)+?</tr>#', '', $html);
    // Remove empty links.
    $html = str_replace('<a>', '', $html);
    $html = str_replace('</a>', '', $html);

    // Add border and padding to table.
    if ($docs) {
      $html = str_replace('<table>', '<table border="1" cellpadding="2" cellspacing="1">', $html);
    }

    // Convert first row into <thead> with <th>.
    $html = preg_replace(
      '#<tbody><tr><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td></tr>#',
      '<thead><tr><th width="30%">\1</th><th width="35%">\2</th><th width="35%">\3</th></thead><tbody>',
      $html
    );

    // Convert groups.
    $html = preg_replace('#<tr><td>([^<]+)</td>(<td></td>){2}</tr>#', '<tr><th bgcolor="' . $group_color . '">\1</th><th bgcolor="' . $group_color . '">Webform Module</th><th bgcolor="' . $group_color . '">Contact Module</th></tr>', $html);

    // Add cell colors.
    $html = preg_replace('#<tr><td>([^<]+)</td>#', '<tr><td bgcolor="' . $feature_color . '">\1</td>', $html);
    $html = preg_replace('#<td>Yes([^<]*)</td>#', '<td bgcolor="' . $yes_color . '"><img src="https://www.drupal.org/misc/watchdog-ok.png" alt="Yes"> \1</td>', $html);
    $html = preg_replace('#<td>No([^<]*)</td>#', '<td bgcolor="' . $custom_color . '"><img src="https://www.drupal.org/misc/watchdog-error.png" alt="No"> \1</td>', $html);
    $html = preg_replace('#<td>([^<]*)</td>#', '<td bgcolor="' . $no_color . '"><img src="https://www.drupal.org/misc/watchdog-warning.png" alt="Warning"> \1</td>', $html);

    // Link *.module.
    $html = preg_replace('/([a-z0-9_]+)\.module/', '<a href="https://www.drupal.org/project/\1">\1.module</a>', $html);

    // Convert URLs to links with titles.
    $links = [
      'https://www.drupal.org/docs/8/modules/webform' => $this->t('Webform Documentation'),
      'https://www.drupal.org/docs/8/core/modules/contact/overview' => $this->t('Contact Documentation'),
      'https://www.drupal.org/docs/8/modules/webform/webform-videos' => $this->t('Webform Videos'),
      'https://www.drupal.org/docs/8/modules/webform/webform-cookbook' => $this->t('Webform Cookbook'),
      'https://www.drupal.org/project/project_module?text=signature' => $this->t('Signature related-projects'),
      'https://www.drupal.org/sandbox/smaz/2833275' => $this->t('webform_slack.module'),
    ];
    foreach ($links as $link_url => $link_title) {
      $html = preg_replace('#([^"/])' . preg_quote($link_url, '#') . '([^"/])#', '\1<a href="' . $link_url . '">' . $link_title . '</a>\2', $html);
    }

    // Create fake filter object with settings.
    $filter = (object) ['settings' => ['filter_url_length' => 255]];
    $html = _filter_url($html, $filter);

    // Tidy.
    if (class_exists('\tidy')) {
      $tidy = new \tidy();
      $tidy->parseString($html, ['show-body-only' => TRUE, 'wrap' => '0'], 'utf8');
      $tidy->cleanRepair();
      $html = tidy_get_output($tidy);
    }

    return [
      'title' => [
        '#markup' => $this->t('Form builder comparison'),
        '#prefix' => '<h2 id="comparison">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'google' => [
          '#markup' => '<div class="note-warning"><p>' . $this->t('Please post comments and feedback to this <a href=":href">Google Sheet</a>.', [':href' => 'https://docs.google.com/spreadsheets/d/1zNt3WsKxDq2ZmMHeYAorNUUIx5_yiDtDVUIKXtXaq4s/edit?usp=sharing']) . '</p></div>',
        ],
        'description' => [
          '#markup' => '<p>' . $this->t("Here is a detailed feature-comparison of Webform 8.x-5.x and Contact Storage 8.x-1.x.&nbsp;It's worth noting that Contact Storage relies on the Contact module which in turn relies on the Field UI; Contact Storage out of the box is a minimalistic solution with limited (but useful!) functionality. This means it can be extended with core mechanisms such as CRUD entity hooks and overriding services; also there's a greater chance that a general purpose module will play nicely with it (eg. the Conditional Fields module is for entity form displays in general, not the Contact module).") . '</p>' .
            '<p>' . $this->t("Webform is much heavier; it has a great deal of functionality enabled right within the one module, and that's on top of supplying all the normal field elements (because it doesn't just use the Field API)") . '</p>',
        ],
        'table' => ['#markup' => $html],
      ],
    ];
  }

  /***************************************************************************/
  // Module.
  /***************************************************************************/

  /**
   * Get the current version number of the Webform module.
   *
   * @return string
   *   The current version number of the Webform module.
   */
  protected function getVersion() {
    if (isset($this->version)) {
      return $this->version;
    }

    $module_info = Yaml::decode(file_get_contents($this->moduleHandler->getModule('webform')->getPathname()));
    $this->version = (isset($module_info['version']) && !preg_match('/^8.x-5.\d+-.*-dev$/', $module_info['version'])) ? $module_info['version'] : '8.x-5.x-dev';
    return $this->version;
  }

  /**
   * Determine if the Webform module has been updated.
   *
   * @return bool
   *   TRUE if the Webform module has been updated.
   */
  protected function isUpdated() {
    return ($this->getVersion() !== $this->state->get('webform.version')) ? TRUE : FALSE;
  }

  /***************************************************************************/
  // Groups.
  /***************************************************************************/

  /**
   * Initialize group.
   *
   * @return array
   *   An associative array containing videos.
   */
  protected function initGroups() {
    return [
      // General.
      'general' => $this->t('General'),
      'installation' => $this->t('Installation'),
      'introduction' => $this->t('Introduction'),
      'forms' => $this->t('Forms'),
      'elements' => $this->t('Elements'),
      'handlers' => $this->t('Handlers'),
      'settings' => $this->t('Settings'),
      'options' => $this->t('Options'),
      'submissions' => $this->t('Submissions'),
      'results' => $this->t('Results'),
      // Administrative.
      'configuration' => $this->t('Configuration'),
      'plugins' => $this->t('Plugins'),
      'addons' => $this->t('Add-ons'),
      // Translations.
      'translations' => $this->t('Translations'),
      // Devel.
      'devel' => $this->t('Devel'),
      // Modules.
      'webform_nodes' => $this->t('Webform Nodes'),
      'webform_blocks' => $this->t('Webform Blocks'),
      // Promotions.
      'promotions' => $this->t('Promotions'),
    ];
  }

  /***************************************************************************/
  // Videos.
  /***************************************************************************/

  /**
   * Initialize videos.
   *
   * @return array
   *   An associative array containing videos.
   */
  protected function initVideos() {
    $videos = [];

    $videos['promotion_lingotek'] = [
      'title' => $this->t('Webform & Lingotek Partnership'),
      'content' => $this->t('You can help support the Webform module by signing up and trying the Lingotek-Inside Drupal Translation Module for <strong>free</strong>.'),
      'youtube_id' => '83L99vYbaGQ',
      'submit_label' => $this->t('Sign up and try Lingotek'),
      'submit_url' => Url::fromUri('https://lingotek.com/webform'),
    ];

    $videos['introduction_short'] = [
      'title' => $this->t('Welcome to the Webform module'),
      'content' => $this->t('Welcome to the Webform module for Drupal 8.'),
      'youtube_id' => 'rJ-Hcg5WtSU',
    ];

    $videos['introduction'] = [
      'title' => $this->t('Overview of the Webform module'),
      'content' => $this->t('This screencast provides a complete introduction to the Webform module for Drupal 8.'),
      'youtube_id' => 'AgaD041BTxU',
    ];

    $videos['installing'] = [
      'title' => $this->t('Installing the Webform module and third party libraries'),
      'content' => $this->t('This screencast walks through installing the core Webform module, sub-module, required libraries, and add-ons.'),
      'youtube_id' => 'IMfFTrsjg5k',
    ];

    $videos['association'] = [
      'title' => $this->t('Join the Drupal Association'),
      'content' => $this->t('The Drupal Association is dedicated to fostering and supporting the Drupal software project, the community and its growth. We help the Drupal community with funding, infrastructure, education, promotion, distribution and online collaboration at Drupal.org.'),
      'youtube_id' => 'LZWqFSMul84',
    ];

    $videos['forms'] = [
      'title' => $this->t('Managing Forms, Templates, and Examples'),
      'content' => $this->t('This screencast walks through how to view and manage forms, leverage templates, and learn from examples.'),
      'youtube_id' => 'T5MVGa_3jOQ',
    ];

    $videos['elements'] = [
      'title' => $this->t('Adding Elements, Composites, and Containers'),
      'content' => $this->t('This screencast walks through adding elements, composites, and containers to forms.'),
      'youtube_id' => 'LspF9mAvRcY',
    ];

    $videos['settings'] = [
      'title' => $this->t('Configuring Form Settings and Behaviors'),
      'content' => $this->t('This screencast walks through configuring form settings, styles, and behaviors.'),
      'youtube_id' => 'UJ0y09ZS9Uc',
    ];

    $videos['access'] = [
      'title' => $this->t('Controlling Access to Forms and Elements'),
      'content' => $this->t('This screencast walks through how to manage user permissions and controlling access to forms and elements.'),
      'youtube_id' => 'SFm76DAVjbE',
    ];

    $videos['submissions'] = [
      'title' => $this->t('Collecting Submissions, Sending Emails, and Posting Results'),
      'content' => $this->t('This screencast walks through collecting submission, managing results, sending emails, and posting submissions to remote servers.'),
      'youtube_id' => 'OdfVm5LMH9A',
    ];

    $videos['blocks'] = [
      'title' => $this->t('Placing Webforms in Blocks and Creating Webform Nodes'),
      'content' => $this->t('This screencast walks through placing Webform block and creating Webform nodes.'),
      'youtube_id' => 'xYBW2g0osd4',
    ];

    $videos['translate'] = [
      'title' => $this->t('Translating Webforms'),
      'content' => $this->t('This screencast walks through translating a Webform.'),
      'youtube_id' => 'rF8Bd-0w6Cg',
    ];

    $videos['admin'] = [
      'title' => $this->t('Administering and Extending the Webform module'),
      'content' => $this->t("This screencast walks through administering the Webform module's admin settings, options, and behaviors."),
      'youtube_id' => 'bkScAX_Qbt4',
    ];

    $videos['source'] = [
      'title' => $this->t('Using the Source'),
      'content' => $this->t('This screencast walks through viewing and editing source code and configuration behind a Webform.'),
      'youtube_id' => '2pWkJiYeR6E',
    ];

    $videos['help'] = [
      'title' => $this->t('Help Us Help You'),
      'content' => $this->t('This screencast is going to show you how to “help us help you” with issues related to the Webform module.'),
      'youtube_id' => 'uQo-1s2h06E',
    ];

    foreach ($videos as $id => &$video_info) {
      $video_info['id'] = $id;
    }

    return $videos;
  }

  /**
   * Initialize help.
   *
   * @return array
   *   An associative array containing help.
   */
  protected function initHelp() {
    $help = [];

    /**************************************************************************/
    // General.
    /**************************************************************************/

    // Release.
    $t_args = [
      '@version' => $this->getVersion(),
      ':href' => 'https://www.drupal.org/project/webform/releases/' . $this->getVersion(),
    ];
    $help['release'] = [
      'group' => 'general',
      'title' => $this->t('You have successfully updated...'),
      'content' => $this->t('You have successfully updated to the @version release of the Webform module. <a href=":href">Learn more</a>', $t_args),
      'message_type' => 'status',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'uses' => FALSE,
      'reset_version' => TRUE,
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
        // @see /admin/reports/updates
        'update.status',
      ],
    ];

    /**************************************************************************/
    // Installation.
    /**************************************************************************/

    // Installation.
    $t_args = [
      ':addons_href' => Url::fromRoute('webform.addons')->toString(),
      ':submodules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-webform'])->toString(),
      ':libraries_href' => Url::fromRoute('help.page', ['name' => 'webform'], ['fragment' => 'libraries'])->toString(),
    ];
    $help['installation'] = [
      'group' => 'installation',
      'title' => $this->t('Installing the Webform module'),
      'content' => $this->t('<strong>Congratulations!</strong> You have successfully installed the Webform module. Please make sure to install additional <a href=":libraries_href">third-party libraries</a>, <a href=":submodules_href">sub-modules</a>, and optional <a href=":addons_href">add-ons</a>.', $t_args),
      'video_id' => 'installation',
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'uses' => FALSE,
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
      ],
    ];

    /**************************************************************************/
    // Introduction.
    /**************************************************************************/

    // Introduction.
    $help['introduction'] = [
      'group' => 'introduction',
      'title' => $this->t('Welcome'),
      'content' => $this->t('Welcome to the Webform module for Drupal 8.'),
      'video_id' => 'introduction',
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_USER,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
    ];

    /**************************************************************************/
    // Forms.
    /**************************************************************************/

    // Webforms.
    $help['webforms_manage'] = [
      'group' => 'forms',
      'title' => $this->t('Managing webforms'),
      'content' => $this->t('The Forms page lists all available webforms, which can be filtered by title, description, and/or elements.'),
      'url' => Url::fromRoute('entity.webform.collection'),
      'video_id' => 'forms',
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
    ];

    // Templates.
    if ($this->moduleHandler->moduleExists('webform_templates')) {
      $help['webform_templates'] = [
        'group' => 'forms',
        'title' => $this->t('Using templates'),
        'content' => $this->t('The Templates page lists reusable templates that can be duplicated and customized to create new webforms.'),
        'video_id' => 'forms',
        'url' => Url::fromRoute('entity.webform.templates'),
        'routes' => [
          // @see /admin/structure/webform/templates
          'entity.webform.templates',
        ],
      ];
    }

    /**************************************************************************/
    // Submissions.
    /**************************************************************************/

    // Submissions.
    $help['submissions'] = [
      'group' => 'submissions',
      'title' => $this->t('Managing results'),
      'content' => $this->t('The Results page lists all incoming submissions for all webforms.'),
      'url' => Url::fromRoute('entity.webform_submission.collection'),
      'routes' => [
        // @see /admin/structure/webform/results/manage
        'entity.webform_submission.collection',
      ],
    ];

    // Submissions: Log.
    $help['submissions_log'] = [
      'group' => 'submissions',
      'title' => $this->t('Log'),
      'content' => $this->t('The Log page lists all submission events for all webforms.'),
      'url' => Url::fromRoute('entity.webform_submission.results_log'),
      'routes' => [
        // @see /admin/structure/webform/results/log
        'entity.webform_submission.results_log',
      ],
    ];

    // Log.
    $help['submission_log'] = [
      'group' => 'submissions',
      'title' => $this->t('Submission log'),
      'content' => $this->t('The Submission log lists all events logged for this submission.'),
      'url' => Url::fromRoute('entity.webform_submission.results_log'),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submission}/log
        'entity.webform_submission.log',
        // @see /node/{node}/webform/submission/{webform_submission}/log
        'entity.node.webform_submission.log',
      ],
    ];

    /**************************************************************************/
    // Addons.
    /**************************************************************************/

    // Addons.
    $help['addons'] = [
      'group' => 'addons',
      'title' => $this->t('Extend the Webform module'),
      'content' => $this->t('The Add-ons page includes a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal\'s Form API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">Webform module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/webform']),
      'url' => Url::fromRoute('webform.addons'),
      'routes' => [
        // @see /admin/structure/webform/addons
        'webform.addons',
      ],
    ];

    /**************************************************************************/
    // Configuration.
    /**************************************************************************/

    // Configuration: Forms.
    $help['config_forms'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining default form configuration'),
      'content' => $this->t('The Forms configuration page allows administrators to manage form settings, behaviors, labels, and messages.'),
      'url' => Url::fromRoute('webform.config'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/forms
        'webform.config',
      ],
    ];

    // Configuration: Elements.
    $help['config_elements'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining default element configuration'),
      'url' => Url::fromRoute('webform.config.elements'),
      'content' => $this->t('The Elements configuration page allows administrators to manage element specific settings and HTML formatting.'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/element
        'webform.config.elements',
      ],
    ];

    // Configuration: Options.
    $help['config_options'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining options'),
      'content' => $this->t('The Options page lists predefined options which are used to build select menus, radio buttons, checkboxes and likerts.') . ' ' .
        $this->t('To find and download additional options, go to <a href=":href">Webform 8.x-5.x: Cookbook</a>.', [':href' => 'https://www.drupal.org/docs/8/modules/webform/webform-cookbook']),
      'url' => Url::fromRoute('entity.webform_options.collection'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/options
        'entity.webform_options.collection',
      ],
    ];

    // Configuration: Submissions.
    $help['config_submissions'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining default submission configuration'),
      'content' => $this->t('The Submissions configuration page allows administrators to manage submissions settings and behaviors.'),
      'url' => Url::fromRoute('webform.config.submissions'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/submissions
        'webform.config.submissions',
      ],
    ];

    // Configuration: Handlers.
    $help['config_handlers'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining default email and handler configuration'),
      'content' => $this->t('The Handlersconfiguration page allows administrators to manage email and handler default values and behaviors.'),
      'url' => Url::fromRoute('webform.config.handlers'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/handlers
        'webform.config.handlers',
      ],
    ];

    // Configuration: Exporters.
    $help['config_exporters'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining default exporter configuration'),
      'content' => $this->t('The Handlers configuration page allows administrators to manage exporter default settings.'),
      'url' => Url::fromRoute('webform.config.exporters'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/exporters
        'webform.config.exporters',
      ],
    ];

    // Configuration: Libraries.
    $help['config_libraries'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining default CSS/JS and library configuration'),
      'content' => $this->t('The Libraries configuration page allows administrators to add custom CSS/JS to all form and enabled/disable external libraries.'),
      'url' => Url::fromRoute('webform.config.libraries'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/libraries
        'webform.config.libraries',
      ],
    ];

    // Configuration: Advanced.
    $help['config_advanced'] = [
      'group' => 'configuration',
      'title' => $this->t('Defining advanced configuration'),
      'content' => $this->t('The Libraries configuration page allows administrators to managed advanced settings including UI behaviors and test data.'),
      'url' => Url::fromRoute('webform.config.advanced'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/advanced
        'webform.config.advanced',
      ],
    ];

    /**************************************************************************/
    // Plugins.
    /**************************************************************************/

    // Plugins: Elements.
    $help['plugins_elements'] = [
      'group' => 'plugins',
      'title' => $this->t('Webform element plugins'),
      'content' => $this->t('The Elements page lists all available webform element plugins.') . ' ' .
        $this->t('Webform element plugins are used to enhance existing render/form elements. Webform element plugins provide default properties, data normalization, custom validation, element configuration webform, and customizable display formats.'),
      'url' => Url::fromRoute('webform.element_plugins'),
      'routes' => [
        // @see /admin/structure/webform/plugins/elements
        'webform.element_plugins',
      ],
    ];

    // Plugins: Handlers.
    $help['plugins_handlers'] = [
      'group' => 'plugins',
      'title' => $this->t('Webform handler plugins'),
      'content' => $this->t('The Handlers page lists all available webform handler plugins.') . ' ' .
        $this->t('Handlers are used to route submitted data to external applications and send notifications & confirmations.'),
      'url' => Url::fromRoute('webform.handler_plugins'),
      'routes' => [
        // @see /admin/structure/webform/plugins/handlers
        'webform.handler_plugins',
      ],
    ];

    // Plugins: Exporters.
    $help['plugins_exporters'] = [
      'group' => 'plugins',
      'title' => $this->t('Results exporter plugins'),
      'content' => $this->t('The Exporters page lists all available results exporter plugins.') . ' ' .
        $this->t('Exporters are used to export results into a downloadable format that can be used by MS Excel, Google Sheets, and other spreadsheet applications.'),
      'url' => Url::fromRoute('webform.exporter_plugins'),
      'routes' => [
        // @see /admin/structure/webform/plugins/exporters
        'webform.exporter_plugins',
      ],
    ];

    /**************************************************************************/
    // Webform.
    /**************************************************************************/

    // Webform: Elements -- Warning.
    if (!$this->moduleHandler->moduleExists('webform_ui')) {
      $help['webform_elements_warning'] = [
        'group' => 'forms',
        'title' => $this->t('Webform UI is disabled'),
        'content' => $this->t('Please enable Webform UI module if you would like to add elements from UI.'),
        'message_type' => 'warning',
        'message_close' => TRUE,
        'message_storage' => WebformMessage::STORAGE_STATE,
        'access' => $this->currentUser->hasPermission('administer webform') && $this->currentUser->hasPermission('administer modules'),
        'uses' => FALSE,
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}
          'entity.webform.edit_form',
        ],
      ];
    }

    // Webform: Source.
    $help['webform_source'] = [
      'group' => 'forms',
      'title' => $this->t('Editing YAML source'),
      'content' => $this->t("The (View) Source page allows developers to edit a webform's render array using YAML markup.") . ' ' .
        $this->t("Developers can use the (View) Source page to quickly alter a webform's labels, cut-n-paste multiple elements, reorder elements, and add customize properties and markup to elements."),
      'video_id' => 'source',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/source
        'entity.webform.source_form',
      ],
    ];

    // Webform: Test.
    $help['webform_test'] = [
      'group' => 'forms',
      'title' => $this->t('Testing a webform'),
      'content' => $this->t("The Webform test form allows a webform to be tested using a customizable test dataset.") . ' ' .
        $this->t('Multiple test submissions can be created using the devel_generate module.'),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/test
        'entity.webform.test',
        // @see /node/{node}/webform/test
        'entity.node.webform.test',
      ],
    ];

    // Webform: API.
    $help['webform_api'] = [
      'group' => 'forms',
      'title' => $this->t('Testing a webform API'),
      'content' => $this->t("The Webform test API form allows a webform's API to be tested using raw webform submission values and data."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/api
        'entity.webform.api',
        // @see /node/{node}/webform/api
        'entity.node.webform.api',
      ],
    ];

    // Webform: Translations.
    $help['webform_translations'] = [
      'group' => 'translations',
      'title' => $this->t('Translating a webform'),
      'content' => $this->t("The Translation page allows a webform's configuration and elements to be translated into multiple languages."),
      'video_id' => 'translate',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/translate
        'entity.webform.config_translation_overview',
      ],
    ];

    /**************************************************************************/
    // Elements.
    /**************************************************************************/

    // Elements
    $help['elements'] = [
      'group' => 'elements',
      'title' => $this->t('Building a webform'),
      'content' => $this->t('The Webform elements page allows users to add, update, duplicate, and delete webform elements and wizard pages.'),
      'video_id' => 'elements',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}
        'entity.webform.edit_form',
      ],
    ];

    /**************************************************************************/
    // Handlers.
    /**************************************************************************/

    // Handlers.
    $help['settings_handlers'] = [
      'group' => 'handlers',
      'title' => $this->t('Enabling webform handlers'),
      'content' => $this->t('The Webform handlers page lists additional handlers (aka behaviors) that can process webform submissions.') . ' ' .
        $this->t('Handlers are <a href=":href">plugins</a> that act on a webform submission.', [':href' => 'https://www.drupal.org/developing/api/8/plugins']) . ' ' .
        $this->t('For example, sending email confirmations and notifications is done using the Email handler which is provided by the Webform module.'),
      'video_id' => 'submissions',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/handlers
        'entity.webform.handlers',
      ],
    ];

    /**************************************************************************/
    // Settings.
    /**************************************************************************/

    // Settings.
    $help['settings'] = [
      'group' => 'settings',
      'title' => $this->t('Customizing webform general settings'),
      'content' => $this->t("The Webform settings page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
        $this->t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings
        'entity.webform.settings',
      ],
    ];

    // Settings: Form.
    $help['settings_form'] = [
      'group' => 'settings',
      'title' => $this->t('Customizing webform form settings'),
      'content' => $this->t("The Webform form settings page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
        $this->t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/form
        'entity.webform.settings_form',
      ],
    ];

    // Settings: Submissions.
    $help['settings_submissions'] = [
      'group' => 'settings',
      'title' => $this->t('Customizing webform submissions settings'),
      'content' => $this->t("The Webform submissions settings page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
        $this->t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/submissions
        'entity.webform.settings_submissions',
      ],
    ];

    // Settings: Confirmation.
    $help['settings_confirmation'] = [
      'group' => 'settings',
      'title' => $this->t('Customizing webform confirmation settings'),
      'content' => $this->t("The Webform submissions confirmation page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
        $this->t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/confirmation
        'entity.webform.settings_confirmation',
      ],
    ];

    // Settings: Assets.
    $help['settings_assets'] = [
      'group' => 'settings',
      'title' => $this->t('Adding custom CSS/JS to a webform.'),
      'content' => $this->t("The Webform assets page allows site builders to attach custom CSS and JavaScript to a webform."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/assets
        'entity.webform.settings_assets',
      ],
    ];

    // Settings: Access.
    $help['settings_access'] = [
      'group' => 'settings',
      'title' => $this->t('Controlling access to submissions'),
      'content' => $this->t('The Webform access control page allows administrator to determine who can create, update, delete, and purge webform submissions.'),
      'video_id' => 'access',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/access
        'entity.webform.access_form',
      ],
    ];

    /**************************************************************************/
    // Results.
    /**************************************************************************/

    // Results.
    $help['results'] = [
      'group' => 'results',
      'title' => $this->t('Managing results'),
      'content' => $this->t("The Results page displays an overview of a webform's submissions. This page can be used to generate a customized report.") . ' ' .
        $this->t("Submissions can be reviewed, updated, flagged, annotated, and downloaded."),
      'video_id' => 'submissions',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/submissions
        'entity.webform.results_submissions',
        // @see /node/{node}/webform/results/submissions
        'entity.node.webform.results_submissions',
      ],
    ];

    // Results: Log.
    $help['results_log'] = [
      'group' => 'results',
      'title' => $this->t('Results log'),
      'content' => $this->t('The Results log lists all logged webform submission events for the current webform.'),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/log
        'entity.webform.results_log',
        // @see /node/{node}/webform/results/log
        'entity.node.webform.results_log',
      ],
    ];

    // Results: Download.
    $help['results_download'] = [
      'group' => 'results',
      'title' => $this->t('Downloading results'),
      'content' => $this->t("The Download page allows a webform's submissions to be exported in to a customizable CSV (Comma Separated Values) file."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/download
        'entity.webform.results_export',
        // @see /node/{node}/webform/results/download
        'entity.node.webform.results_export',
      ],
    ];

    /**************************************************************************/
    // Devel.
    /**************************************************************************/

    if ($this->moduleHandler->moduleExists('webform_devel')) {
      // Devel: Export.
      $help['webform_export'] = [
        'group' => 'devel',
        'title' => $this->t('Exporting configuration'),
        'content' => $this->t("The Export (form) page allows developers to quickly export a single webform's configuration file.") . ' ' .
          $this->t('If you run into any issues with a webform, you can also attach the below configuration (without any personal information) to a new ticket in the Webform module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/webform']),
        'video_id' => 'help',
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}/export
          'entity.webform.export_form',
        ],
      ];

      // Devel: Schema.
      $help['webform_schema'] = [
        'group' => 'devel',
        'title' => $this->t('Webform schema'),
        'content' => $this->t("The Webform schema page displays an overview of a webform's elements and specified data types, which can be used to map webform submissions to a remote post API."),
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}/schema
          'entity.webform.schema_form',
        ],
      ];
    }

    /**************************************************************************/
    // Modules.
    /**************************************************************************/

    // Webform Node.
    $help['webform_node'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Creating a webform node'),
      'content' => $this->t("A webform node allows webforms to be fully integrated into a website as nodes."),
      'video_id' => 'blocks',
      'paths' => [
        '/node/add/webform',
      ],
    ];
    $help['webform_node_reference'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform references'),
      'content' => $this->t("The Reference pages displays an overview of a webform's references and allows you to quickly create new references (a.k.a Webform nodes)."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/references
        'entity.webform.references',
      ],
    ];

    // Webform Block.
    $help['webform_block'] = [
      'group' => 'webform_blocks',
      'title' => $this->t('Creating a webform block'),
      'content' => $this->t("A webform block allows a webform to be placed anywhere on a website."),
      'video_id' => 'blocks',
      'paths' => [
        '/admin/structure/block/add/webform_block/*',
      ],
    ];

    /**************************************************************************/
    // Promotions.
    // Disable promotions via Webform admin settings.
    // (/admin/structure/webform/config/advanced).
    /**************************************************************************/

    if (!$this->configFactory->get('webform.settings')->get('ui.promotions_disabled')) {
      // Lingotek.
      $help['promotion_lingotek'] = [
        'group' => 'promotions',
        'title' => $this->t('Webform & Lingotek Translation Partnership'),
        'content' => $this->t("Help <strong>support</strong> the Webform module and internationalize your website using the Lingotek-Inside Drupal Module for continuous translation. <em>Multilingual capability + global access = increased web traffic.</em>"),
        'video_id' => 'promotion_lingotek',
        'message_type' => 'promotion_lingotek',
        'message_close' => TRUE,
        'message_storage' => WebformMessage::STORAGE_STATE,
        'attached' => ['library' => ['webform/webform.promotions']],
        'access' => $this->currentUser->hasPermission('administer webform'),
        'uses' => FALSE,
        'reset_version' => TRUE,
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}/translate
          'entity.webform.config_translation_overview',
        ],
      ];
    }

    /**************************************************************************/

    // Initialize help.
    foreach ($help as $id => &$help_info) {
      $help_info += [
        'id' => $id,
        'uses' => TRUE,
        'reset_version' => FALSE,
      ];
    }

    // Reset storage state if the Webform module version has changed.
    if ($this->isUpdated()) {
      foreach ($help as $id => $help_info) {
        if (!empty($help_info['reset_version'])) {
          WebformMessage::resetClosed(WebformMessage::STORAGE_STATE, 'webform.help.' . $id);
        }
      }
      $this->state->set('webform.version', $this->getVersion());
    }

    return $help;
  }

}
