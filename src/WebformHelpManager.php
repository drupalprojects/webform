<?php

namespace Drupal\webform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;

/**
 * Webform help manager.
 */
class WebformHelpManager implements WebformHelpManagerInterface {

  use StringTranslationTrait;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * The Webform libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * Constructs a WebformHelpManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The Webform libraries manager.
   */
  public function __construct(AccountInterface $current_user, ModuleHandlerInterface $module_handler, StateInterface $state, PathMatcherInterface $path_matcher, WebformLibrariesManagerInterface $libraries_manager) {
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->pathMatcher = $path_matcher;
    $this->librariesManager = $libraries_manager;

    $this->help = $this->initHelp();
    $this->videos = $this->initVideos();
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
      ];

      if (!$help['access']) {
        continue;
      }

      $is_route_match = in_array($route_name, $help['routes']);
      $is_path_match = ($help['paths'] && $this->pathMatcher->matchPath($path, implode("\n", $help['paths'])));
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
    $build = [
      '#prefix' => '<div class="webform-help-accordion">',
      '#suffix' => '</div>',
    ];
    $build['about'] = $this->buildAbout();
    $build['uses'] = $this->buildUses();
    $build['libraries'] = $this->buildLibraries();
    $build['#attached']['library'][] = 'webform/webform.help';
    return $build;
  }

  /****************************************************************************/
  // Index sections.
  /****************************************************************************/

  /**
   * Build the about section.
   *
   * @return array
   *   An render array containing the about section.
   */
  protected function buildAbout() {
    return [
      'title' => [
        '#markup' => $this->t('About'),
        '#prefix' => '<h3 id="about">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t('The Webform module is a webform builder and submission manager for Drupal 8.') . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];
  }

  /**
   * Build the uses section.
   *
   * @return array
   *   An render array containing the uses section.
   */
  protected function buildUses() {
    $build = [
      'title' => [
        '#markup' => $this->t('Uses'),
        '#prefix' => '<h3 id="uses">',
        '#suffix' => '</h3>',
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
    foreach ($this->help as $id => $info) {
      // Title.
      $build['content']['help'][$id]['title'] = [
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      if (isset($info['url'])) {
        $build['content']['help'][$id]['title']['link'] = [
          '#type' => 'link',
          '#url' => $info['url'],
          '#title' => $info['title'],
        ];
      }
      else {
        $build['content']['help'][$id]['title']['#markup'] = $info['title'];
      }
      // Content.
      $build['content']['help'][$id]['content'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
        'content' => [
          '#theme' => 'webform_help',
          '#info' => $info,
        ],
      ];
    }
    return $build;
  }

  /**
   * Build the libraries section.
   *
   * @return array
   *   An render array containing the libraries section.
   */
  protected function buildLibraries() {
    // Libraries.
    $build = [
      'title' => [
        '#markup' => $this->t('External Libraries'),
        '#prefix' => '<h3 id="libraries">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'description' => [
          '#markup' => '<p>' . $this->t('The Webform module utilizes the third-party Open Source libraries listed below to enhance webform elements and to provide additional functionality. It is recommended that these libraries be installed in your Drupal installations /libraries directory. If these libraries are not installed, they are automatically loaded from a CDN.') . '</p>' .
            '<p>' . $this->t('Currently the best way to download all the needed third party libraries is to either add <a href=":href">webform.libraries.make.yml</a> to your drush make file or execute the below drush command from the root of your Drupal installation.', [':href' => 'http://cgit.drupalcode.org/webform/tree/webform.libraries.make.yml']) . '</p>' .
            '<hr/><pre>drush webform-libraries-download</pre><hr/><br/>',
        ],
        'libraries' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      $build['content']['libraries'][$library_name] = [
        'title' => [
          '#type' => 'link',
          '#title' => $library['title'],
          '#url' => $library['url'],
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ],
        'description' => [
          '#markup' => $library['description'] . '<br/><em>(' . $library['notes'] . ')</em>',
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];
    }
    return $build;
  }

  /**
   * Initialize videos.
   *
   * @return array
   *   An associative array containing videos.
   */
  protected function initVideos() {
    return $this->help;
  }

  /**
   * Initialize help.
   *
   * @return array
   *   An associative array containing help.
   */
  protected function initHelp() {
    $help = [];

    // Install.
    $t_args = [
      ':addons_href' => Url::fromRoute('webform.addons')->toString(),
      ':submodules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-webform'])->toString(),
      ':libraries_href' => Url::fromRoute('help.page', ['name' => 'webform'], ['fragment' => 'libraries'])->toString(),
    ];
    $help['install'] = [
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
      ],
      'title' => $this->t('Installing the Webform module'),
      'content' => $this->t('<strong>Congratulations!</strong> You have successfully installed the Webform module. Please make sure to install additional <a href=":libraries_href">third-party libraries</a>, <a href=":submodules_href">sub-modules</a>, and optional <a href=":addons_href">add-ons</a>.', $t_args),
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      // 'youtube_id' => 'install',
    ];

    // Release.
    $module_info = Yaml::decode(file_get_contents($this->moduleHandler->getModule('webform')->getPathname()));
    $version = isset($module_info['version']) ? $module_info['version'] : '8.x-1.x-dev';
    $installed_version = $this->state->get('webform.version');
    // Reset storage state if the version has changed.
    if ($installed_version != $version) {
      WebformMessage::resetClosed(WebformMessage::STORAGE_STATE, 'webform.help.release');
      $this->state->set('webform.version', $version);
    }
    $t_args = [
      '@version' => $version,
      ':href' => 'https://www.drupal.org/project/webform/releases/' . $version,
    ];
    $help['release'] = [
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
      'title' => $this->t('You have successfully updated...'),
      'content' => $this->t('You have successfully updated to the @version release of the Webform module. <a href=":href">Learn more</a>', $t_args),
      'message_type' => 'status',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
    ];

    // Introduction.
    $help['introduction'] = [
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
      'title' => $this->t('Welcome'),
      'content' => $this->t('Welcome to new Webform module for Drupal 8.'),
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_USER,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'youtube_id' => 'sQGsfQ_LZJ4',
    ];

    /****************************************************************************/
    // General.
    /****************************************************************************/

    // Webforms.
    $help['webforms'] = [
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
      'title' => $this->t('Managing webforms'),
      'url' => Url::fromRoute('entity.webform.collection'),
      'content' => $this->t('The Forms page lists all available webforms, which can be filtered by title, description, and/or elements.'),
      // 'youtube_id' => 'QyVytonGeH8',
    ];

    // Templates.
    if ($this->moduleHandler->moduleExists('webform_templates')) {
      $help['templates'] = [
        'routes' => [
          // @see /admin/structure/webform/templates
          'entity.webform.templates',
        ],
        'title' => $this->t('Using templates'),
        'url' => Url::fromRoute('entity.webform.templates'),
        'content' => $this->t('The Templates page lists reusable templates that can be duplicated and customized to create new webforms.'),
        // 'youtube_id' => 'tvMCqC-H0bI',
      ];
    }

    // Results.
    $help['results'] = [
      'routes' => [
        // @see /admin/structure/webform/results/manage
        'entity.webform_submission.collection',
      ],
      'title' => $this->t('Managing results'),
      'url' => Url::fromRoute('entity.webform_submission.collection'),
      'content' => $this->t('The Results page lists all incoming submissions for all webforms.'),
      // 'youtube_id' => 'EME1HoYTmVA',
    ];

    // Settings.
    $help['settings'] = [
      'routes' => [
        // @see /admin/structure/webform/settings
        'webform.settings',
      ],
      'title' => $this->t('Defining default settings'),
      'url' => Url::fromRoute('webform.settings'),
      'content' => $this->t('The Settings page allows administrators to manage global webform and UI configuration settings, including updating default labels & descriptions, settings default format, and defining test dataset.'),
      // 'youtube_id' => 'UWxlfu7PEQg',
    ];

    // Options.
    $help['options'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/options/manage
        'entity.webform_options.collection',
      ],
      'title' => $this->t('Defining options'),
      'url' => Url::fromRoute('entity.webform_options.collection'),
      'content' => $this->t('The Options page lists predefined options which are used to build select menus, radio buttons, checkboxes and likerts.'),
      // 'youtube_id' => 'vrL_TR8aQJo',
    ];

    // Elements.
    $help['elements'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/elements
        'webform.element_plugins',
      ],
      'title' => $this->t('Webform element plugins'),
      'url' => Url::fromRoute('webform.element_plugins'),
      'content' => $this->t('The Elements page lists all available webform element plugins.') . ' ' .
        $this->t('Webform element plugins are used to enhance existing render/form elements. Webform element plugins provide default properties, data normalization, custom validation, element configuration webform, and customizable display formats.'),
      // 'youtube_id' => 'WSNGzJwnpeQ',
    ];

    // Handlers.
    $help['handlers'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/handlers
        'webform.handler_plugins',
      ],
      'title' => $this->t('Webform handler plugins'),
      'url' => Url::fromRoute('webform.handler_plugins'),
      'content' => $this->t('The Handlers page lists all available webform handler plugins.') . ' ' .
        $this->t('Handlers are used to route submitted data to external applications and send notifications & confirmations.'),
      // 'youtube_id' => 'v5b4sOsUtn4',
    ];

    // Exporters.
    $help['exporters'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/exporters
        'webform.exporter_plugins',
      ],
      'title' => $this->t('Results exporter plugins'),
      'url' => Url::fromRoute('webform.exporter_plugins'),
      'content' => $this->t('The Exporters page lists all available results exporter plugins.') . ' ' .
        $this->t('Exporters are used to export results into a downloadable format that can be used by MS Excel, Google Sheets, and other spreadsheet applications.'),
      // 'youtube_id' => '',
    ];

    // Third party settings.
    $help['third_party'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/third-party
        'webform.admin_settings.third_party',
      ],
      'title' => $this->t('Configuring global third party settings'),
      'url' => Url::fromRoute('webform.admin_settings.third_party'),
      'content' => $this->t('The Third party settings page allows contrib and custom modules to define global settings that are applied to all webforms and submissions.'),
      // 'youtube_id' => 'kuguydtCWf0',
    ];

    // Addons.
    $help['addons'] = [
      'routes' => [
        // @see /admin/structure/webform/addons
        'webform.addons',
      ],
      'title' => $this->t('Extend the Webform module'),
      'url' => Url::fromRoute('webform.addons'),
      'content' => $this->t('The Add-ons page includes a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal\'s Webform API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">Webform module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/webform']),
      // 'youtube_id' => '',
    ];


    /****************************************************************************/
    // Webform.
    /****************************************************************************/

    // Webform elements.
    $help['form_elements'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}
        'entity.webform.edit_form',
      ],
      'title' => $this->t('Building a webform'),
      'content' => $this->t('The Webform elements page allows users to add, update, duplicate, and delete webform elements and wizard pages.'),
      // 'youtube_id' => 'OaQkqeJPu4M',
    ];

    // Webform source.
    $help['form_source'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/source
        'entity.webform.source_form',
      ],
      'title' => $this->t('Editing YAML source'),
      'content' => $this->t("The (View) Source page allows developers to edit a webform's render array using YAML markup.") . ' ' .
        $this->t("Developers can use the (View) Source page to quickly alter a webform's labels, cut-n-paste multiple elements, reorder elements, and add customize properties and markup to elements."),
      // 'youtube_id' => 'BQS5YdUWo5k',
    ];

    // Webform test.
    $help['form_test'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/test
        'entity.webform.test',
        // @see /node/{node}/webform/test
        'entity.node.webform.test',
      ],
      'title' => $this->t('Testing a webform'),
      'content' => $this->t("The Webform test page allows a webform to be tested using a customizable test dataset.") . ' ' .
        $this->t('Multiple test submissions can be created using the devel_generate module.'),
      // 'youtube_id' => 'PWwV7InvYmU',
    ];

    // Webform settings.
    $help['form_settings'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings
        'entity.webform.settings_form',
      ],
      'title' => $this->t('Customizing webform settings'),
      'content' => $this->t("The Webform settings page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
        $this->t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      // 'youtube_id' => 'g2RWTj7XrQo',
    ];

    // Webform assets.
    $help['form_assets'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/assets
        'entity.webform.assets_form',
      ],
      'title' => $this->t('Adding custom CSS/JS to a webform.'),
      'content' => $this->t("The Webform assets page allows site builders to attach custom CSS and JavaScript to a webform."),
      // 'youtube_id' => '',
    ];

    // Webform access controls.
    $help['form_access'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/access
        'entity.webform.access_form',
      ],
      'title' => $this->t('Controlling access to submissions'),
      'content' => $this->t('The Webform access control page allows administrator to determine who can create, update, delete, and purge webform submissions.'),
      // 'youtube_id' => 'xRlA1k5m09E',
    ];

    // Webform handlers.
    $help['form_handlers'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/handlers
        'entity.webform.handlers_form',
      ],
      'title' => $this->t('Enabling webform handlers'),
      'content' => $this->t('The Webform handlers page lists additional handlers (aka behaviors) that can process webform submissions.') . ' ' .
        $this->t('Handlers are <a href=":href">plugins</a> that act on a webform submission.', [':href' => 'https://www.drupal.org/developing/api/8/plugins']) . ' ' .
        $this->t('For example, sending email confirmations and notifications is done using the Email handler which is provided by the Webform module.'),
      // 'youtube_id' => 'bZ8WDjmVFz4',
    ];

    // Webform third party settings.
    $help['form_third_party'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/third_party
        'entity.webform.third_party_settings_form',
      ],
      'title' => $this->t('Configuring third party settings'),
      'content' => $this->t('The Third party settings page allows contrib and custom modules to define webform specific customization settings.'),
      // 'youtube_id' => 'Kq3Sor1b-fI',
    ];

    // Webform translations.
    $help['form_translations'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/translate
        'entity.webform.config_translation_overview',
      ],
      'title' => $this->t('Translating a webform'),
      'content' => $this->t("The Translation page allows a webform's configuration and elements to be translated into multiple languages."),
      // 'youtube_id' => '7nQuIpQ1pnE',
    ];

    /****************************************************************************/
    // Results.
    /****************************************************************************/

    // Webform results.
    $help['form_results'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/submissions
        'entity.webform.results_submissions',
        // @see /node/{node}/webform/results/submissions
        'entity.node.webform.results_submissions',
      ],
      'title' => $this->t('Managing results'),
      'content' => $this->t("The Results page displays an overview of a webform's submissions.") . ' ' .
        $this->t("Submissions can be reviewed, updated, flagged, annotated, and downloaded."),
      // 'youtube_id' => 'f1FYULMreA4',
    ];

    // Webform results.
    $help['form_table'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/table
        'entity.webform.results_table',
        // @see /node/{node}/webform/results/table
        'entity.node.webform.results_table',
      ],
      'title' => $this->t('Building a custom report'),
      'content' => $this->t("The Table page provides a customizable table of a webform's submissions. This page can be used to generate a customized report."),
      // 'youtube_id' => '-Y_8eUlvo8k',
    ];

    // Webform download.
    $help['form_download'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/download
        'entity.webform.results_export',
        // @see /node/{node}/webform/results/download
        'entity.node.webform.results_export',
      ],
      'title' => $this->t('Downloading results'),
      'content' => $this->t("The Download page allows a webform's submissions to be exported in to a customizable CSV (Comma Separated Values) file."),
      // 'youtube_id' => 'xHVXjhhVtHg',
    ];

    if ($this->moduleHandler->moduleExists('webform_devel')) {
      // Webform Export.
      $help['form_export'] = [
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}/export
          'entity.webform.export_form',
        ],
        'title' => $this->t('Exporting configuration'),
        'content' => $this->t("The Export (form) page allows developers to quickly export a single webform's configuration file.") . ' ' .
          $this->t('If you run into any issues with a webform, you can also attach the below configuration (without any personal information) to a new ticket in the Webform module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/webform']),
        // 'youtube_id' => 'ejzx4D0ldl0',
      ];
    }

    /****************************************************************************/
    // Modules
    /****************************************************************************/

    // Webform Node.
    $help['webform_node'] = [
      'paths' => [
        '/node/add/webform',
      ],
      'title' => $this->t('Creating a webform node'),
      'content' => $this->t("A webform node allows webforms to be fully integrated into a website as nodes."),
      // 'youtube_id' => 'ZvuMj4fBZDs',
    ];

    // Webform Block.
    $help['webform_block'] = [
      'paths' => [
        '/admin/structure/block/add/webform_block/*',
      ],
      'title' => $this->t('Creating a webform block'),
      'content' => $this->t("A webform block allows a webform to be placed anywhere on a website."),
      // 'youtube_id' => 'CkRQMS6eJII',
    ];

    // Webform to Webform.
    if ($this->moduleHandler->moduleExists('webform_to_webform')) {
      $help['webform_to_webform'] = [
        'routes' => [
          // @see /admin/structure/webform/migrate
          'webform_to_webform.migrate',
        ],
        'title' => $this->t('Migrating from Webform 8.x-1.x to Webform 8.x-1.x'),
        'content' => $this->t("The Migrate page will move your Webform configuration and modules to the Webform module."),
        'youtube_id' => 'sQGsfQ_LZJ4',
      ];
    }

    foreach ($help as $id => &$info) {
      $info['id'] = $id;
      // @todo Make video independent of help.
      // TEMP: Video IDs match Help IDs.
      if (!empty($info['youtube_id'])) {
        $info['video_id'] = $id;
      }
    }

    return $help;
  }

}
