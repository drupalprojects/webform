<?php

/**
 * @file
 * Webform help.
 */

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Implements hook_help().
 */
function webform_help($route_name, RouteMatchInterface $route_match) {
  $build = [];

  if ($route_name == 'help.page.webform') {
    // About.
    $build['about'] = [
      'title' => [
        '#markup' => t('About'),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . t('The Webform module is a webform builder and submission manager for Drupal 8.'),
      ],
    ];

    // Uses.
    $build['uses'] = [
      'title' => [
        '#markup' => t('Uses'),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ],
    ];
    $help = _webform_help();
    foreach ($help as $id => $info) {
      // Title.
      $build['uses']['content'][$id]['title'] = [
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      if (isset($info['url'])) {
        $build['uses']['content'][$id]['title']['link'] = [
          '#type' => 'link',
          '#url' => $info['url'],
          '#title' => $info['title'],
        ];
      }
      else {
        $build['uses']['content'][$id]['title']['#markup'] = $info['title'];
      }
      // Content.
      $build['uses']['content'][$id]['content'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
        'content' => [
          '#theme' => 'webform_help',
          '#info' => $info,
        ],
      ];
    }

    // Libraries.
    $build['libraries'] = [
      'title' => [
        '#markup' => t('External Libraries'),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ],
      'description' => [
        '#markup' => '<p>' . t('The Webform module utilizes the third-party Open Source libraries listed below to enhance webform elements and to provide additional functionality. It is recommended that these libraries be installed in your Drupal installations /libraries directory. If these libraries are not installed, they are automatically loaded from a CDN.') . '</p>' .
          '<p>' . t('Currently the best way to download all the needed third party libraries is to either add webform.libraries.make.yml to your drush make file or execute the below drush command from the root of your Drupal installation.') . '</p>' .
          '<pre>drush webform-libraries-download</pre>',
      ],
      'content' => [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ],
      'info'  => [],
    ];
    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libraries_manager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      $build['libraries']['content'][$library_name] = [
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

  }
  else {
    // Get path from route match.
    $path = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', Url::fromRouteMatch($route_match)->setAbsolute(FALSE)->toString());

    if (strpos($route_name, 'webform') === FALSE && strpos($path, '/webform') === FALSE) {
      return NULL;
    }

    /** @var \Drupal\Core\Path\PathMatcherInterface $path_matcher */
    $path_matcher = \Drupal::service('path.matcher');

    $help = _webform_help();
    $build = [];
    foreach ($help as $id => $info) {
      // Set default values.
      $info += [
        'routes' => [],
        'paths' => [],
        // 'youtube_id' => '',
      ];

      $is_route_match = in_array($route_name, $info['routes']);
      $is_path_match = ($info['paths'] && $path_matcher->matchPath($path, implode("\n", $info['paths'])));
      if ($is_route_match || $is_path_match) {
        $build[$id] = [
          '#theme' => 'webform_help',
          '#info' => $info,
        ];
      }
    }
  }

  if ($build) {
    $renderer = \Drupal::service('renderer');
    $config = \Drupal::config('webform.settings');
    $renderer->addCacheableDependency($build, $config);
    return $build;
  }
  else {
    return NULL;
  }
}

/**
 * Get an associative array for all webform help.
 *
 * @return array
 *   An associative array with all webform help.
 */
function _webform_help() {
  $help = [];

  // Overview.
  $help['webform'] = [
    'title' => t('The Webform module'),
    'content' => t('The Webform module is a FAPI based webform builder and submission manager for Drupal 8.'),
    // 'youtube_id' => '9jSOOEpzAy8',
  ];

  // Introduction.
  $help['introduction'] = [
    'title' => t('Welcome'),
    'content' => t('Welcome to new Webform module for Drupal 8.'),
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
    'title' => t('Managing webforms'),
    'url' => Url::fromRoute('entity.webform.collection'),
    'content' => t('The Forms page lists all available webforms, which can be filtered by title, description, and/or elements.'),
    // 'youtube_id' => 'QyVytonGeH8',
  ];

  // Templates.
  if (\Drupal::moduleHandler()->moduleExists('webform_templates')) {
    $help['templates'] = [
      'routes' => [
        // @see /admin/structure/webform/templates
        'entity.webform.templates',
      ],
      'title' => t('Using templates'),
      'url' => Url::fromRoute('entity.webform.templates'),
      'content' => t('The Templates page lists reusable templates that can be duplicated and customized to create new webforms.'),
      // 'youtube_id' => 'tvMCqC-H0bI',
    ];
  }

  // Results.
  $help['results'] = [
    'routes' => [
      // @see /admin/structure/webform/results/manage
      'entity.webform_submission.collection',
    ],
    'title' => t('Managing results'),
    'url' => Url::fromRoute('entity.webform_submission.collection'),
    'content' => t('The Results page lists all incoming submissions for all webforms.'),
    // 'youtube_id' => 'EME1HoYTmVA',
  ];

  // Settings.
  $help['settings'] = [
    'routes' => [
      // @see /admin/structure/webform/settings
      'webform.settings',
    ],
    'title' => t('Defining default settings'),
    'url' => Url::fromRoute('webform.settings'),
    'content' => t('The Settings page allows administrators to manage global webform and UI configuration settings, including updating default labels & descriptions, settings default format, and defining test dataset.'),
    // 'youtube_id' => 'UWxlfu7PEQg',
  ];

  // Options.
  $help['options'] = [
    'routes' => [
      // @see /admin/structure/webform/settings/options/manage
      'entity.webform_options.collection',
    ],
    'title' => t('Defining options'),
    'url' => Url::fromRoute('entity.webform_options.collection'),
    'content' => t('The Options page lists predefined options which are used to build select menus, radio buttons, checkboxes and likerts.'),
    // 'youtube_id' => 'vrL_TR8aQJo',
  ];

  // Elements.
  $help['elements'] = [
    'routes' => [
      // @see /admin/structure/webform/settings/elements
      'webform.element_plugins',
    ],
    'title' => t('Webform element plugins'),
    'url' => Url::fromRoute('webform.element_plugins'),
    'content' => t('The Elements page lists all available webform element plugins.') . ' ' .
    t('Webform element plugins are used to enhance existing render/form elements. Webform element plugins provide default properties, data normalization, custom validation, element configuration webform, and customizable display formats.'),
    // 'youtube_id' => 'WSNGzJwnpeQ',
  ];

  // Handlers.
  $help['handlers'] = [
    'routes' => [
      // @see /admin/structure/webform/settings/handlers
      'webform.handler_plugins',
    ],
    'title' => t('Webform handler plugins'),
    'url' => Url::fromRoute('webform.handler_plugins'),
    'content' => t('The Handlers page lists all available webform handler plugins.') . ' ' .
    t('Handlers are used to route submitted data to external applications and send notifications & confirmations.'),
    // 'youtube_id' => 'v5b4sOsUtn4',
  ];

  // Exporters.
  $help['exporters'] = [
    'routes' => [
      // @see /admin/structure/webform/settings/exporters
      'webform.exporter_plugins',
    ],
    'title' => t('Results exporter plugins'),
    'url' => Url::fromRoute('webform.exporter_plugins'),
    'content' => t('The Exporters page lists all available results exporter plugins.') . ' ' .
      t('Exporters are used to export results into a downloadable format that can be used by MS Excel, Google Sheets, and other spreadsheet applications.'),
    // 'youtube_id' => '',
  ];

  // Third party settings.
  $help['third_party'] = [
    'routes' => [
      // @see /admin/structure/webform/settings/third-party
      'webform.admin_settings.third_party',
    ],
    'title' => t('Configuring global third party settings'),
    'url' => Url::fromRoute('webform.admin_settings.third_party'),
    'content' => t('The Third party settings page allows contrib and custom modules to define global settings that are applied to all webforms and submissions.'),
    // 'youtube_id' => 'kuguydtCWf0',
  ];

  // Addons.
  $help['addons'] = [
    'routes' => [
      // @see /admin/structure/webform/addons
      'webform.addons',
    ],
    'title' => t('Extend the Webform module'),
    'url' => Url::fromRoute('webform.addons'),
    'content' => t('The Add-ons page includes a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal\'s Webform API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">Webform module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/webform']),
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
    'title' => t('Building a webform'),
    'content' => t('The Webform elements page allows users to add, update, duplicate, and delete webform elements and wizard pages.'),
    // 'youtube_id' => 'OaQkqeJPu4M',
  ];

  // Webform source.
  $help['form_source'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/source
      'entity.webform.source_form',
    ],
    'title' => t('Editing YAML source'),
    'content' => t("The (View) Source page allows developers to edit a webform's render array using YAML markup.") . ' ' .
    t("Developers can use the (View) Source page to quickly alter a webform's labels, cut-n-paste multiple elements, reorder elements, and add customize properties and markup to elements."),
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
    'title' => t('Testing a webform'),
    'content' => t("The Webform test page allows a webform to be tested using a customizable test dataset.") . ' ' .
    t('Multiple test submissions can be created using the devel_generate module.'),
    // 'youtube_id' => 'PWwV7InvYmU',
  ];

  // Webform settings.
  $help['form_settings'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/settings
      'entity.webform.settings_form',
    ],
    'title' => t('Customizing webform settings'),
    'content' => t("The Webform settings page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
    t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
    // 'youtube_id' => 'g2RWTj7XrQo',
  ];

  // Webform assets.
  $help['form_assets'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/assets
      'entity.webform.assets_form',
    ],
    'title' => t('Adding custom CSS/JS to a webform.'),
    'content' => t("The Webform assets page allows site builders to attach custom CSS and JavaScript to a webform."),
    // 'youtube_id' => '',
  ];

  // Webform access controls.
  $help['form_access'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/access
      'entity.webform.access_form',
    ],
    'title' => t('Controlling access to submissions'),
    'content' => t('The Webform access control page allows administrator to determine who can create, update, delete, and purge webform submissions.'),
    // 'youtube_id' => 'xRlA1k5m09E',
  ];

  // Webform handlers.
  $help['form_handlers'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/handlers
      'entity.webform.handlers_form',
    ],
    'title' => t('Enabling webform handlers'),
    'content' => t('The Webform handlers page lists additional handlers (aka behaviors) that can process webform submissions.') . ' ' .
    t('Handlers are <a href=":href">plugins</a> that act on a webform submission.', [':href' => 'https://www.drupal.org/developing/api/8/plugins']) . ' ' .
    t('For example, sending email confirmations and notifications is done using the Email handler which is provided by the Webform module.'),
    // 'youtube_id' => 'bZ8WDjmVFz4',
  ];

  // Webform third party settings.
  $help['form_third_party'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/third_party
      'entity.webform.third_party_settings_form',
    ],
    'title' => t('Configuring third party settings'),
    'content' => t('The Third party settings page allows contrib and custom modules to define webform specific customization settings.'),
    // 'youtube_id' => 'Kq3Sor1b-fI',
  ];

  // Webform translations.
  $help['form_translations'] = [
    'routes' => [
      // @see /admin/structure/webform/manage/{webform}/translate
      'entity.webform.config_translation_overview',
    ],
    'title' => t('Translating a webform'),
    'content' => t("The Translation page allows a webform's configuration and elements to be translated into multiple languages."),
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
    'title' => t('Managing results'),
    'content' => t("The Results page displays an overview of a webform's submissions.") . ' ' .
    t("Submissions can be reviewed, updated, flagged, annotated, and downloaded."),
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
    'title' => t('Building a custom report'),
    'content' => t("The Table page provides a customizable table of a webform's submissions. This page can be used to generate a customized report."),
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
    'title' => t('Downloading results'),
    'content' => t("The Download page allows a webform's submissions to be exported in to a customizable CSV (Comma Separated Values) file."),
    // 'youtube_id' => 'xHVXjhhVtHg',
  ];

  if (\Drupal::moduleHandler()->moduleExists('webform_devel')) {
    // Webform Export.
    $help['form_export'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/export
        'entity.webform.export_form',
      ],
      'title' => t('Exporting configuration'),
      'content' => t("The Export (form) page allows developers to quickly export a single webform's configuration file.") . ' ' .
      t('If you run into any issues with a webform, you can also attach the below configuration (without any personal information) to a new ticket in the Webform module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/webform']),
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
    'title' => t('Creating a webform node'),
    'content' => t("A webform node allows webforms to be fully integrated into a website as nodes."),
    // 'youtube_id' => 'ZvuMj4fBZDs',
  ];

  // Webform Block.
  $help['webform_block'] = [
    'paths' => [
      '/admin/structure/block/add/webform_block/*',
    ],
    'title' => t('Creating a webform block'),
    'content' => t("A webform block allows a webform to be placed anywhere on a website."),
    // 'youtube_id' => 'CkRQMS6eJII',
  ];

  // Webform to Webform.
  if (\Drupal::moduleHandler()->moduleExists('webform_to_webform')) {
    $help['webform_to_webform'] = [
      'routes' => [
        // @see /admin/structure/webform/migrate
        'webform_to_webform.migrate',
      ],
      'title' => t('Migrating from Webform 8.x-1.x to Webform 8.x-1.x'),
      'content' => t("The Migrate page will move your Webform configuration and modules to the Webform module."),
      // 'youtube_id' => 'XXXXXXXX',
    ];
  }

  foreach ($help as $id => &$info) {
    $info['id'] = $id;
  }

  return $help;
}

/**
 * Build documentation videos HTML markup.
 *
 * The below code snippet outputs the HTML markup.
 * print('<pre>' . htmlentities(_webform_help_doc()) .'</pre>'); exit;
 */
function _webform_help_doc() {

  $help = _webform_help();
  $html = '';
  foreach ($help as $info) {
    $info += [
      'youtube_id' => NULL,
    ];

    $title = $info['title'];
    $content = strip_tags($info['content']);
    $youtube_id = $info['youtube_id'];
    $html .= <<<EOT
### $title

$content

<div style="position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden;">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/$youtube_id" frameborder="0" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>
</div>
<br/>


EOT;

  }
  return $html;
}
