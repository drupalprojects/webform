<?php

/**
 * @file
 * Webform module drush commands.
 */

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Controller\WebformResultsExportController;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Form\WebformResultsClearForm;
use Drupal\webform\Form\WebformSubmissionsPurgeForm;
use Drupal\webform\Utility\WebformTidy;

/**
 * Implements hook_drush_command().
 */
function webform_drush_command() {
  $items = [];

  /* Submissions */

  $items['webform-export'] = [
    'description' => 'Exports webform submissions to a file.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'arguments' => [
      'webform' => 'The webform ID you want to export (required unless --entity-type and --entity-id are specified)',
    ],
    'options' => [
      // Delimited export options.
      'delimiter' => 'Delimiter between columns (defaults to site-wide setting). This option may need to be wrapped in quotes. i.e. --delimiter="\t".',
      // Document and managed file export options.
      'file-name' => 'File name used to export submission and uploaded filed. You may use tokens.',
      // Tabular export options.
      'header-keys' => 'Set to "label" (default) or "key"',
      'options-item-format' => 'Set to "label" (default) or "key". Set to "key" to print select list values by their keys instead of labels.',
      'options-format' => 'Set to "separate" (default) or "compact" to determine how select list values are exported.',
      'entity-reference-format' => 'Set to "link" (default) or "id" to determine how entity references are exported.',
      'excluded-columns' => 'Comma-separated list of component IDs or webform keys to exclude.',
      // Download options.
      'entity-type' => 'The entity type to which this submission was submitted from.',
      'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
      'range-type' => 'Range of submissions to export: "all", "latest", "serial", "sid", or "date".',
      'range-latest' => 'Integer specifying the latest X submissions will be downloaded. Used if "range-type" is "latest" or no other range options are provided.',
      'range-start' => 'The submission ID or start date at which to start exporting.',
      'range-end' => 'The submission ID or end date at which to end exporting.',
      'state' => 'Submission state to be included: "completed", "draft" or "all" (default).',
      'sticky' => 'Flagged/starred submission status.',
      'files' => 'Download files: "1" or "0" (default). If set to 1, the exported CSV file and any submission file uploads will be download in a gzipped tar file.',
      // Output options.
      'destination' => 'The full path and filename in which the CSV or archive should be stored. If omitted the CSV file or archive will be outputted to the commandline.',
    ],
    'aliases' => ['wfx'],
  ];

  $items['webform-purge'] = [
    'description' => "Purge webform submissions from the databases",
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'arguments' => [
      'webform_id' => "A webform machine name. If not provided, user may choose from a list of names.",
    ],
    'options' => [
      'all' => 'Flush all submissions',
      'entity-type' => 'The entity type for webform submissions to be purged',
      'entity-id' => 'The ID of the entity for webform submissions to be purged',
    ],
    'examples' => [
      'drush webform-purge' => 'Pick a webform and then purge its submissions.',
      'drush webform-purge contact' => "Delete 'Contact' webform submissions.",
      'drush webform-purge --all' => 'Purge all webform submissions.',
    ],
    'aliases' => ['wfp'],
  ];

  /* Tidy */

  $items['webform-tidy'] = [
    'description' => "Tidy export webform configuration files",
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'options' => [
      'dependencies' => 'Add module dependencies to installed webform and options configuration entities.',
    ],
    'arguments' => [
      'module' => "The module that needs its YAML configuration files (config/install) tidied. (Defaults to webform)",
    ],
    'examples' => [
      'drush webform-tidy webform' => "Tidies YAML configuration files in 'webform/config' for the Webform module",
    ],
    'aliases' => ['wft'],
  ];

  /* Libraries */

  $items['webform-libraries-status'] = [
    'description' => 'Displays the status of third party libraries required by the Webform module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'webform-libraries-status' => 'Displays the status of third party libraries required by the Webform module.',
    ],
    'aliases' => ['wfls'],
  ];

  $items['webform-libraries-download'] = [
    'description' => 'Download third party libraries required by the Webform module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'webform-libraries-download' => 'Download third party libraries required by the Webform module.',
    ],
    'aliases' => ['wfld'],
  ];

  $items['webform-libraries-remove'] = [
    'description' => 'Removes all downloaded third party libraries required by the Webform module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'webform-libraries-remove' => 'Removes all downloaded third party libraries required by the Webform module.',
    ],
    'aliases' => ['wflr'],
  ];

  /* Devel Generate */

  $items['webform-generate'] = [
    'callback' => 'drush_devel_generate',
    'callback arguments' => [
      'plugin_id' => 'webform_submission',
    ],
    'description' => 'Create submissions in specified webform.',
    'arguments' => [
      'webform_id' => 'Webform id into which new submissions will be inserted.',
      'num' => 'Number of submissions to insert. Defaults to 50.',
    ],
    'options' => [
      'kill' => 'Delete all submissions in specified webform before generating.',
      'feedback' => 'An integer representing interval for insertion rate logging. Defaults to 1000',
      'entity-type' => 'The entity type to which this submission was submitted from.',
      'entity-id' => 'The ID of the entity of which this webform submission was submitted from.',
    ],
    'aliases' => ['wfg'],
  ];

  /* Repair */

  $items['webform-repair'] = [
    'description' => 'Makes sure all Webform admin settings and webforms are up-to-date.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'webform-repair' => 'Repairs admin settings and webforms are up-to-date.',
    ],
    'aliases' => ['wfr'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function webform_drush_help($section) {
  switch ($section) {
    case 'drush:webform-export':
      return dt('This command will export webform submissions to a file.');

    case 'drush:webform-purge':
      return dt('This command will purge webform submissions.');

    case 'drush:webform-libraries-status':
      return dt('This command displays the status of third party libraries required by the Webform module.');

    case 'drush:webform-libraries-download':
      return dt('This command downloads third party libraries required by the Webform module.');

    case 'drush:webform-libraries-remove':
      return dt('This command removes all downloaded third party libraries required by the Webform module.');

    case 'drush:webform-repair':
      return dt('Makes sure all Webform admin settings and webforms are up-to-date. Only use this command if you are having problems with your existing Webform configuration.');

    case 'drush:webform-tidy':
      return dt('This command tidies exported YAML configuration files.') . "\n\n" .
        dt('Changes include...') . "\n" .
        dt('- Preserving newlines using pipe (|).') . "\n" .
        dt('- Removing returns after array dashes (-).');

    case 'drush:webform-generate':
      return dt('This command will create submissions in specified webform.');

    case 'meta:webform:title':
      return dt('Webform commands');

    case 'meta:webform:summary':
      return dt('Enables the creation of webforms and questionnaires.');
  }
}

/******************************************************************************/
// Export
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_export_validate($webform_id = NULL) {
  return _drush_webform_validate($webform_id);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_export($webform_id = NULL) {
  if (!$webform_id) {
    $webforms = array_keys(Webform::loadMultiple());
    $choices = array_combine($webforms, $webforms);
    $webform_id = drush_choice($choices, dt("Choose a webform to export submissions from."));
    if ($webform_id === FALSE) {
      return drush_user_abort();
    }
  }

  $webform = Webform::load($webform_id);
  // @todd Determine if we should get source entity from options entity type
  // and id.
  $source_entity = NULL;

  /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
  $submission_exporter = \Drupal::service('webform_submission.exporter');
  $submission_exporter->setWebform($webform);
  $submission_exporter->setSourceEntity($source_entity);

  // Get command options as export options.
  $export_options = drush_redispatch_get_options();
  // Convert dashes to underscores.
  foreach ($export_options as $key => $value) {
    unset($export_options[$key]);
    $export_options[str_replace('-', '_', $key)] = $value;
  }
  $export_options += $submission_exporter->getDefaultExportOptions();
  $submission_exporter->setExporter($export_options);

  WebformResultsExportController::batchSet($webform, $source_entity, $export_options);
  drush_backend_batch_process();

  $file_path = ($submission_exporter->isArchive()) ? $submission_exporter->getArchiveFilePath() : $submission_exporter->getExportFilePath();
  if (isset($export_options['destination'])) {
    drush_print(dt('Created @destination', ['@destination' => $export_options['destination']]));
    file_unmanaged_copy($file_path, $export_options['destination'], FILE_EXISTS_REPLACE);
  }
  else {
    drush_print(file_get_contents($file_path));
  }
  @unlink($file_path);

  return NULL;
}

/******************************************************************************/
// Purge
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_purge_validate($webform_id = NULL) {
  // If webform id is set to 'all' or  not included skip validation.
  if (drush_get_option('all') || $webform_id == NULL) {
    return;
  }

  return _drush_webform_validate($webform_id);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_purge($webform_id = NULL) {
  if (drush_get_option('all')) {
    $webform_id = 'all';
  }

  if (!$webform_id) {
    $webforms = array_keys(Webform::loadMultiple());
    $choices = array_combine($webforms, $webforms);
    $choices = array_merge(['all' => 'all'], $choices);
    $webform_id = drush_choice($choices, dt("Choose a webform to purge submissions from."));
    if ($webform_id === FALSE) {
      return drush_user_abort();
    }
  }

  // Set the webform.
  $webform = ($webform_id == 'all') ? NULL : Webform::load($webform_id);

  /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
  $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
  /** @var \Drupal\webform\WebformRequestInterface $request_handler */
  $request_handler = \Drupal::service('webform.request');

  // Make sure there are submissions that need to be deleted.
  if (!$submission_storage->getTotal($webform)) {
    return drush_set_error(dt('There are no submissions that need to be deleted.'));
  }

  if (!$webform) {
    $submission_total = \Drupal::entityQuery('webform_submission')->count()->execute();
    $form_total = \Drupal::entityQuery('webform')->count()->execute();
    $t_args = [
      '@submission_total' => $submission_total,
      '@submissions' => \Drupal::translation()->formatPlural($submission_total, t('submission'), t('submissions')),
      '@form_total' => $form_total,
      '@forms' => \Drupal::translation()->formatPlural($form_total, t('webform'), t('webforms')),
    ];
    if (!drush_confirm(dt('Are you sure you want to delete @submission_total @submissions in @form_total @forms?', $t_args))) {
      return drush_user_abort();
    }
    $form = new WebformResultsClearForm($submission_storage, $request_handler);
    $form->batchSet();
    drush_backend_batch_process();
  }
  else {
    // Set source entity.
    $entity_type = drush_get_option('entity-type');
    $entity_id = drush_get_option('entity-id');
    $source_entity = ($entity_type && $entity_id) ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id) : NULL;

    $t_args = [
      '@title' => ($source_entity) ? $source_entity->label() : $webform->label(),
    ];
    if (!drush_confirm(dt("Are you sure you want to delete all submissions from '@title' webform?", $t_args))) {
      return drush_user_abort();
    }
    $form = new WebformSubmissionsPurgeForm($submission_storage, $request_handler);
    $form->batchSet($webform, $source_entity);
    drush_backend_batch_process();
  }
}

/******************************************************************************/
// Tidy
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_tidy_validate($module = 'webform') {
  if (!file_exists(drupal_get_path('module', $module) . '/config')) {
    $t_args = [
      '@module' => $module,
      '@directory' => drupal_get_path('module', $module) . '/config',
    ];
    return drush_set_error(dt("@module does not contain a '@module/config' directory (@directory).", $t_args));
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_tidy($module = 'webform') {
  $dependencies = drush_get_option('dependencies');
  $file_directory_path = drupal_get_path('module', $module) . '/config';
  $files = file_scan_directory($file_directory_path, '/.*\.yml$/');
  drush_print(dt('Reviewing @count YAML configuration files for the @module.module.', ['@count' => count($files), '@module' => $module]));
  $total = 0;
  foreach ($files as $file) {
    $original_yaml = file_get_contents($file->uri);
    $tidied_yaml = $original_yaml;

    // Add module dependency to exporter webform and webform options config entities.
    if ($dependencies && preg_match('/^(webform\.webform\.|webform\.webform_options\.)/', $file->filename)) {
      $data = Yaml::decode($tidied_yaml);
      if (empty($data['dependencies']['enforced']['module']) || !in_array($module, $data['dependencies']['enforced']['module'])) {
        drush_print(dt('Adding module dependency to @file...', ['@file' => $file->filename]));
        $data['dependencies']['enforced']['module'][] = $module;
        $tidied_yaml = Yaml::encode($data);
      }
    }

    // Tidy and add new line to the end of the tidied file.
    $tidied_yaml = WebformTidy::tidy($tidied_yaml) . "\n";
    if ($tidied_yaml != $original_yaml) {
      drush_print(dt('Tidying @file...', ['@file' => $file->filename]));
      file_put_contents($file->uri, $tidied_yaml);
      $total++;
    }
  }

  if ($total) {
    drush_print(dt('@total YAML file(s) tidied.', ['@total' => $total]));
  }
  else {
    drush_print(dt('No YAML files needed to be tidied.'));
  }
}

/******************************************************************************/
// Devel Generate.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_webform_generate_validate() {
  // Array of "Callback arguments" and "command line args".
  $params = func_get_args();
  $params[0] = 'webform_submission';
  _drush_plugin_validate($params);
}

/******************************************************************************/
// Libraries
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_status() {
  module_load_include('install', 'webform');

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $requirements = $libraries_manager->requirements();
  foreach ($requirements as $requirement) {
    $library = $requirement['library'];
    drush_print($library['title'] . ' (' . $library['name'] . ') ' . $requirement['value']);
    // drush_print();
    drush_print(strip_tags($requirement['description']));
    drush_print();
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_remove($status = TRUE) {
  if ($status) {
    drush_print(dt('Beginning to remove libraries...'));
  }
  $removed = FALSE;

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $libraries = $libraries_manager->getLibraries();
  foreach ($libraries as $library_name => $library) {
    $library_path = '/' . $library['destination'] . '/' . $library['directory_name'];
    $library_exists = (file_exists(DRUPAL_ROOT . $library_path)) ? TRUE : FALSE;
    if ($library_exists) {
      $t_args = [
        '@name' => $library_name,
        '@path' => $library_path,
      ];
      if ($status) {
        drush_print(dt('@name removed from @path...', $t_args));
        drush_delete_dir(DRUPAL_ROOT . $library_path, TRUE);
      }
      $removed = TRUE;
    }
  }

  if ($status) {
    drupal_flush_all_caches();
  }
  return $removed;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_libraries_download() {
  // Remove all existing libraries.
  if (drush_webform_libraries_remove(FALSE)) {
    drush_print(dt('Removing existing libraries...'));
  }

  // Download libraries using drush.
  $commandline_args = [
    DRUPAL_ROOT . '/' . drupal_get_path('module', 'webform') . '/webform.libraries.make.yml',
    DRUPAL_ROOT,
  ];
  $commandline_options = ['no-core' => 'no-core'];
  drush_invoke_process('@self', 'make', $commandline_args, $commandline_options);

  drupal_flush_all_caches();
}

/******************************************************************************/
// Repair.
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 */
function drush_webform_repair() {
  if (!drush_confirm(dt("Are you sure you want repair the Webform module's admin settings and webforms?"))) {
    return drush_user_abort();
  }

  module_load_include('install', 'webform');

  drush_print('Repairing admin settings...');
  _webform_update_admin_settings();

  drush_print('Repairing webform settings...');
  _webform_update_form_settings();
}

/******************************************************************************/
// Helper functions.
/******************************************************************************/

/**
 * Validate webform_id argument and source entity-type and entity-id options.
 */
function _drush_webform_validate($webform_id = NULL) {
  if (empty($webform_id)) {
    return drush_set_error(dt('Webform id required'));
  }

  if (!empty($webform_id) && !Webform::load($webform_id)) {
    return drush_set_error(dt('Webform @id not recognized.', ['@id' => $webform_id]));
  }

  $entity_type = drush_get_option('entity-type');
  $entity_id = drush_get_option('entity-id');
  if ($entity_type || $entity_id) {
    if (empty($entity_type)) {
      return drush_set_error(dt('Entity type is required when entity id is specified.'));
    }
    if (empty($entity_id)) {
      return drush_set_error(dt('Entity id is required when entity type is specified.'));
    }

    $dt_args = [
      '@webform_id' => $webform_id,
      '@entity_type' => $entity_type,
      '@entity_id' => $entity_id,
    ];

    $source_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    if (!$source_entity) {
      return drush_set_error(dt('Unable to load @entity_type:@entity_id', $dt_args));
    }

    $dt_args['@title'] = $source_entity->label();

    if (empty($source_entity->webform) || empty($source_entity->webform->target_id)) {
      return drush_set_error(dt("'@title' (@entity_type:@entity_id) does not reference a webform.", $dt_args));
    }

    if ($source_entity->webform->target_id != $webform_id) {
      return drush_set_error(dt("'@title' (@entity_type:@entity_id) does not have a '@webform_id' webform associated with it.", $dt_args));
    }
  }
  return NULL;
}
