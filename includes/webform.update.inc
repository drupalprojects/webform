<?php

/**
 * @file
 * Webform module update hooks.
 */

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Render\Element;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Add support for HTML and file attachments to WebformEmailHandler.
 */
function webform_update_8001(&$sandbox) {
  $messages = [];

  // Update 'webform.settings' configuration.
  // Copied from \views_update_8001().
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $default_data = Yaml::decode(file_get_contents(drupal_get_path('module', 'webform') . '/config/install/webform.settings.yml'));

  $settings_config->clear('mail.default_body');
  $settings_config->set('mail.default_body_text', $default_data['mail']['mail.default_body_text']);
  $settings_config->set('mail.default_body_html', $default_data['mail']['mail.default_body_html']);
  $settings_config->save();

  $messages[] = \Drupal::translation()->translate("Update webform settings removed 'mail.default_body' and replaced with 'mail_default_body_text' and 'mail_default_body_html'");

  // Update 'webform.webform.*' configuration.
  // Copied from \views_update_8001().
  $ids = [];

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $handlers = $webform_config->get('handlers');

    $has_email_handler = FALSE;
    foreach ($handlers as $handler_id => $handler) {
      if ($handler['id'] != 'email') {
        continue;
      }
      $has_email_handler = TRUE;

      $base = "handlers.$handler_id";

      // Get debug setting so that we can move it last.
      $debug = $webform_config->get($base . '.settings.debug');
      $webform_config->clear($base . '.settings.debug');

      $webform_config->set($base . '.settings.html', FALSE);
      $webform_config->set($base . '.settings.attachments', FALSE);
      $webform_config->set($base . '.settings.debug', $debug);
    }

    if ($has_email_handler) {
      $ids[] = $webform_config->get('id');
      $webform_config->save(TRUE);
    }
  }

  if (!empty($ids)) {
    $messages[] = t('Updated email handler for webforms: @ids', ['@ids' => implode(', ', array_unique($ids))]);
  }

  return implode("\n", $messages);
}

/**
 * Issue #2701113: Rework webform submission view to use templates.
 */
function webform_update_8002(&$sandbox) {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->clear('display');
  $settings_config->save();
}

/**
 * Issue #2705859  Allow webform elements to define custom display formats. Add emptu formats to webform.settings.yml.
 */
function webform_update_8003(&$sandbox) {
  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $element_plugins = $element_manager->getInstances();
  $format = [];
  foreach ($element_plugins as $element_id => $element_plugin) {
    $formats = $element_plugin->getFormats();
    if (empty($formats)) {
      continue;
    }
    if (count($formats) == 1 && isset($formats['value'])) {
      continue;
    }

    $format[$element_id] = '';
  }
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('format', $format);
  $settings_config->save();
}

/**
 * Issue #2709933: Save export options.
 */
function webform_update_8004(&$sandbox) {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('export', [
    'delimiter' => ',',
    'header_keys' => 'label',
    'entity_reference_format' => 'link',
    'options_format' => 'compact',
    'options_item_format' => 'label',
  ]);
  $settings_config->save();
}

/**
 * Issue #2712457: Implement Wizard/Paging. Add 'current_page' field to 'webform_submission' entities.
 */
function webform_update_8005() {
  // Install the definition that this field had in
  // \Drupal\webform\Entity\WebformSubmission::baseFieldDefinitions()
  // at the time that this update function was written. If/when code is
  // deployed that changes that definition, the corresponding module must
  // implement an update function that invokes
  // \Drupal::entityDefinitionUpdateManager()->updateFieldStorageDefinition()
  // with the new definition.
  $storage_definition = BaseFieldDefinition::create('string')
    ->setLabel(t('Current page'))
    ->setDescription(t('The current wizard page.'))
    ->setSetting('max_length', 128);
  \Drupal::entityDefinitionUpdateManager()
    ->installFieldStorageDefinition('current_page', 'webform_submission', 'webform', $storage_definition);
}

/**
 * Issue #2712457: Implement Wizard/Paging. Update 'webform.settings' and 'webform.webform.*' configuration.
 */
function webform_update_8007() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_wizard_prev_button_label', '< Previous Page');
  $settings_config->set('settings.default_wizard_next_button_label', 'Next Page >');
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.wizard_progress_bar', TRUE);
    $webform_config->set('settings.wizard_prev_button_label', '');
    $webform_config->set('settings.wizard_next_button_label', '');
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2712463: Provide Entity–attribute–value model for submissions. Create 'webform_submission_data' table.
 */
function webform_update_8008() {
  $schema = webform_schema();
  \Drupal::database()->schema()->createTable('webform_submission_data', $schema['webform_submission_data']);
}

/**
 * Issue #2712463: Provide Entity–attribute–value model for submissions. Populate 'webform_submission_data' table using batch process.
 */
function webform_update_8009(&$sandbox) {
  // @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21module.api.php/function/hook_update_N/8.1.x
  // Loop through 100 webform submission at at time.
  $limit = 100;
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current_sid'] = 0;
    $sandbox['max'] = \Drupal::database()->query('SELECT COUNT(sid) FROM {webform_submission}')->fetchField();
  }

  // @see \Drupal\webform\Entity\WebformSubmission::save().
  $records = \Drupal::database()->select('webform_submission', 's')
    ->fields('s', ['sid', 'webform_id', 'data'])
    ->condition('sid', $sandbox['current_sid'], '>')
    ->range(0, $limit)
    ->orderBy('sid', 'ASC')
    ->execute();
  foreach ($records as $record) {
    $data = Yaml::decode($record->data);
    $webform_id  = $record->webform_id;
    $sid = $record->sid;

    $rows = [];
    $update_submission_record = FALSE;
    foreach ($data as $name => &$item) {
      if (is_array($item)) {
        foreach ($item as $key => $value) {
          // Remove target_id from saved entity_autocomplete fields.
          if (is_array($value) && isset($value['target_id'])) {
            $value = $value['target_id'];
            $item[$key] = $value;
            $update_submission_record = TRUE;
          }

          $rows[] = [
            'webform_id' => $webform_id,
            'sid' => $sid,
            'name' => $name,
            'delta' => (string) $key,
            'value' => (string) $value,
          ];
        }
      }
      else {
        $rows[] = [
          'webform_id' => $webform_id,
          'sid' => $sid,
          'name' => $name,
          'delta' => '',
          'value' => (string) $item,
        ];
      }
    }

    // Delete existing submission data rows.
    \Drupal::database()->delete('webform_submission_data')
      ->condition('sid', $sid)
      ->execute();

    // Insert new submission data rows.
    $query = \Drupal::database()
      ->insert('webform_submission_data')
      ->fields(['webform_id', 'sid', 'name', 'delta', 'value']);
    foreach ($rows as $row) {
      $query->values($row);
    }
    $query->execute();

    // Update submission record.
    if ($update_submission_record) {
      \Drupal::database()
        ->update('webform_submission')
        ->fields(['data' => Yaml::encode($data)])
        ->condition('sid', $sid)
        ->execute();
    }

    $sandbox['progress']++;
    $sandbox['current_sid'] = $sid;
  }

  $sandbox['#finished'] = ($sandbox['progress'] >= $sandbox['max']) ? TRUE : ($sandbox['progress'] / $sandbox['max']);

  if ($sandbox['#finished']) {
    return t("Populating the 'webform_submission_data' table is complete.");
  }
}

/**
 * Issue #2712463: Provide Entity–attribute–value model for submissions. Remove #default_value target_id.
 */
function webform_update_8010() {
  $ids = [];

  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    $inputs = $webform_config->get('inputs');
    if (preg_match('/-\s+target_id:/', $inputs)) {
      // Have to regex to replace inputs target_id: in YAML because if we parse
      // the inputs YAML all comments and formatting will be lost.
      $inputs = preg_replace('/-\s+target_id: /', '- ', $inputs);

      $webform_config->set('inputs', $inputs);
      $webform_config->save(TRUE);

      $ids[] = $webform_config->get('id');
    }
  }

  if ($ids) {
    return t('Updated webforms: @ids', ['@ids' => implode(', ', array_unique($ids))]);
  }
}

/**
 * Issue #2718005: Support Confidential submissions.
 */
function webform_update_8011() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_form_confidential_message', 'This form is confidential. You must <a href="[site:login-url]/logout?destination=[current-page:url:relative]">Log out</a> to submit it.');
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.form_confidential', FALSE);
    $webform_config->set('settings.form_confidential_message', '');
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2721539: Wizard page numbers and percentage.
 */
function webform_update_8012() {
  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.wizard_progress_pages', FALSE);
    $webform_config->set('settings.wizard_progress_percentage', FALSE);
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2721711: Add start and complete label to wizard settings.
 */
function webform_update_8013() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_wizard_start_label', 'Start');
  $settings_config->set('settings.default_wizard_complete_label', 'Complete');
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.wizard_start_label', '');
    $webform_config->set('settings.wizard_complete', TRUE);
    $webform_config->set('settings.wizard_complete_label', '');
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2748699: Change all references from Inputs to Elements.
 */
function webform_update_8014() {
  $config_factory = \Drupal::configFactory();

  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');

  $yaml = Yaml::encode($settings_config->getRawData());
  $yaml = preg_replace('/(\s+)inputs: /', '\1elements: ', $yaml);
  $yaml = preg_replace('/(\s+)default_inputs: /', '\1default_elements: ', $yaml);

  $settings_config->setData(Yaml::decode($yaml));
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  $webform_config_names = [
    'example_inputs' => 'example_elements',
    'example_inputs_extras' => 'example_elements_extras',
    'example_inputs_formats' => 'example_elements_formats',
    'example_inputs_masks' => 'example_elements_masks',
    'example_inputs_states' => 'example_elements_states',
  ];
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_id = str_replace('webform.webform.', '', $webform_config_name);

    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = preg_replace('/(\s+)inputs: /', '\1elements: ', $yaml);
    $yaml = preg_replace('/(\s+)excluded_inputs: /', '\1excluded_elements: ', $yaml);
    $yaml = str_replace('_inputs_', '_elements_', $yaml);
    $yaml = str_replace(' input ', ' element ', $yaml);
    $yaml = str_replace(' Inputs', ' Elements', $yaml);
    $yaml = str_replace(' inputs', ' elements', $yaml);
    $yaml = str_replace('Private input', 'Private element ', $yaml);

    if (isset($webform_config_names[$webform_id])) {
      $yaml = str_replace($webform_id, $webform_config_names[$webform_id], $yaml);
      $data = Yaml::decode($yaml);
      \Drupal::configFactory()->getEditable('webform.webform.' . $webform_id)->delete();
      \Drupal::configFactory()->getEditable('webform.webform.' . $webform_config_names[$webform_id])->setData($data)->save();
    }
    else {
      $data = Yaml::decode($yaml);
      \Drupal::configFactory()->getEditable('webform.webform.' . $webform_id)->setData($data)->save();
    }
  }
}

/**
 * Issue #2749063: Load webform submission data using EAV table. Fix webform_submission_data table's deltas.
 */
function webform_update_8015(&$sandbox) {
  // @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21module.api.php/function/hook_update_N/8.1.x
  // Loop through 100 webform submission at at time.
  $limit = 100;
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current_sid'] = 0;
    $sandbox['max'] = \Drupal::database()->query('SELECT COUNT(sid) FROM {webform_submission}')->fetchField();
  }

  // @see \Drupal\webform\Entity\WebformSubmission::save().
  $records = \Drupal::database()->select('webform_submission', 's')
    ->fields('s', ['sid', 'webform_id', 'data'])
    ->condition('sid', $sandbox['current_sid'], '>')
    ->range(0, $limit)
    ->orderBy('sid', 'ASC')
    ->execute();
  foreach ($records as $record) {
    $data = Yaml::decode($record->data);
    $webform_id  = $record->webform_id;
    $sid = $record->sid;

    $rows = [];
    $update_submission_record = FALSE;
    foreach ($data as $name => &$item) {
      if (is_array($item)) {
        $index = 0;
        foreach ($item as $key => $value) {
          // Fix multi value element's delta to be an index.
          // Change ['value1' => 'value1', 'value2' => 'value2'] to
          // [0 => 'value1', 1 => 'value2'].
          if ($key == $value) {
            $key = $index;
          }
          $index++;
          $rows[] = [
            'webform_id' => $webform_id,
            'sid' => $sid,
            'name' => $name,
            'delta' => (string) $key,
            'value' => (string) $value,
          ];
        }
      }
      else {
        $rows[] = [
          'webform_id' => $webform_id,
          'sid' => $sid,
          'name' => $name,
          'delta' => '',
          'value' => (string) $item,
        ];
      }
    }

    // Delete existing submission data rows.
    \Drupal::database()->delete('webform_submission_data')
      ->condition('sid', $sid)
      ->execute();

    // Insert new submission data rows.
    $query = \Drupal::database()
      ->insert('webform_submission_data')
      ->fields(['webform_id', 'sid', 'name', 'delta', 'value']);
    foreach ($rows as $row) {
      $query->values($row);
    }
    $query->execute();

    // Update submission record.
    if ($update_submission_record) {
      \Drupal::database()
        ->update('webform_submission')
        ->fields(['data' => Yaml::encode($data)])
        ->condition('sid', $sid)
        ->execute();
    }

    $sandbox['progress']++;
    $sandbox['current_sid'] = $sid;
  }

  $sandbox['#finished'] = ($sandbox['progress'] >= $sandbox['max']) ? TRUE : ($sandbox['progress'] / $sandbox['max']);

  if ($sandbox['#finished']) {
    return t("Populating the 'webform_submission_data' table is complete.");
  }
}

/**
 * Issue #2749063: Load webform submission data using EAV table. Remove webform_submission.data field.
 */
function webform_update_8016() {
  db_drop_field('webform_submission', 'data');
}

/**
 * Issue #2748699: Change all references from Inputs to Elements. Update webform_id in the webform_submission_data table.
 */
function webform_update_8017() {
  $webform_config_names = [
    'example_inputs' => 'example_elements',
    'example_inputs_extras' => 'example_elements_extras',
    'example_inputs_formats' => 'example_elements_formats',
    'example_inputs_masks' => 'example_elements_masks',
    'example_inputs_states' => 'example_elements_states',
  ];
  foreach ($webform_config_names as $config_name_search => $config_name_replace) {
    db_query('UPDATE {webform_submission_data} SET webform_id=:new_id WHERE webform_id=:old_id', [':old_id' => $config_name_search, ':new_id' => $config_name_replace]);
  }
}

/**
 * Issue #2752817: Create creditcard composite element.
 */
function webform_update_8018() {
  $config_factory = \Drupal::configFactory();

  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $yaml = Yaml::encode($settings_config->getRawData());
  $yaml = str_replace('creditcard', 'creditcard_number', $yaml);
  $settings_config->setData(Yaml::decode($yaml));
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = str_replace('creditcard', 'creditcard_number', $yaml);
    $data = Yaml::decode($yaml);
    \Drupal::configFactory()->getEditable($webform_config_name)->setData($data)->save();
  }
}

/**
 * Issue #2756643: Allow entity specific submission total and user limits to be set.
 */
function webform_update_8019(&$sandbox) {
  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.entity_limit_user', NULL);
    $webform_config->set('settings.entity_limit_total', NULL);
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2757479: Create dedicated webform node module.
 */
function webform_update_8020(&$sandbox) {
  // Since the webform node configuration files are already installed, we
  // need to manually install the module.
  $extension_config = \Drupal::configFactory()->getEditable('core.extension');
  $extension_config
    ->set("module.webform_node", 0)
    ->set('module', module_config_sort($extension_config->get('module')))
    ->save(TRUE);
  drupal_static_reset('system_rebuild_module_data');
  drupal_set_installed_schema_version('webform_node', \Drupal::CORE_MINIMUM_SCHEMA_VERSION);
  \Drupal::logger('system')->info('%module module installed.', ['%module' => 'webform']);

  return (string) t("The new Webform Node module has been enabled.");
}

/**
 * Issue #2757981: Improve webform node integration.
 */
function webform_update_8021(&$sandbox) {
  // Clear entity type and id that are pointing to the submission's webform.
  \Drupal::database()->query("UPDATE {webform_submission} SET entity_type=NULL, entity_id=NULL WHERE entity_type='webform' AND webform_id=entity_id");
}

/**
 * Issue #2764531: Add ownership to webform entity.
 */
function webform_update_8022(&$sandbox) {
  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('uid', NULL);
    $webform_config->set('template', FALSE);
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2765057: Remove default elements.
 */
function webform_update_8023(&$sandbox) {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $data = $settings_config->getRawData();

  // Move customized default elements to a template.
  if (md5($data['elements']['default_elements']) != 'c5a931226e09a0d1f30a032d587bceb4') {
    Webform::create([
      'id' => 'template_default_elements',
      'title' => 'Template: Default Elements',
      'description' => 'Default elements previously used when a new webform is created.<br/>For more information see: Issue <a href="https://www.drupal.org/node/2765057">#2765057</a>: Remove default elements.',
      'template' => TRUE,
      'elements' => $data['elements']['default_elements'],
    ])->save();
    $customized_default_elements = TRUE;
  }
  else {
    $customized_default_elements = FALSE;
  }

  // Remove default elements.
  unset($data['elements']['default_elements']);
  $settings_config->setData($data);
  $settings_config->save();

  // Return message is custom default elements exist.
  if ($customized_default_elements) {
    return t("Moved existing default elements to a new webform template called 'Template: Default Elements' (template_default_elements)");
  }
  else {
    return NULL;
  }
}

/**
 * Issue #2765831: Improve text field autocompletion support.
 */
function webform_update_8024() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    if (strpos($yaml, "'#autocomplete'") !== FALSE) {
      $yaml = str_replace("'#autocomplete'", "'#autocomplete_options'", $yaml);
      $data = Yaml::decode($yaml);
      $webform_config->setData($data)->save();
    }
  }
}

/**
 * Issue #2766453: Add sticky and notes to submissions.
 */
function webform_update_8025(&$sandbox) {
  // @see https://www.drupal.org/node/2554097
  // From: \Drupal\webform\Entity\WebformSubmission::baseFieldDefinitions
  $definitions = [];
  $definitions['sticky'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Sticky'))
    ->setDescription(t('A flag that indicate the status of the webform submission.'))
    ->setDefaultValue(FALSE);
  $definitions['notes'] = BaseFieldDefinition::create('string_long')
    ->setLabel(t('Notes'))
    ->setDescription(t('Administrative notes about the webform submission.'))
    ->setDefaultValue('');
  foreach ($definitions as $name => $definition) {
    \Drupal::entityDefinitionUpdateManager()
      ->installFieldStorageDefinition($name, 'webform_submission', 'webform_submission', $definition);
  }
}

/**
 * Issue #2767637: Allow table columns to be customized.
 */
function webform_update_8026(&$sandbox) {
  // Remove broken webform export settings.
  db_query("DELETE FROM {key_value} WHERE collection='state' AND name LIKE 'webform%'");
}

/**
 * Issue #2767891: Allow dialogs to be disabled.
 */
function webform_update_8027(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('ui.dialog_disabled', FALSE);
  $settings_config->save();
}

/**
 * Issue #2770823: Save open/close state of detail webform element.
 */
function webform_update_8028(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('ui.details_save', TRUE);
  $settings_config->save();
}

/**
 * Issue #2770071 by jrockowitz: Compatibility with SMTP authentication.
 */
function webform_update_8029(&$sandbox) {
  /** @var \Drupal\webform\WebformEmailProviderInterface $email_provider */
  $email_provider = \Drupal::service('webform.email_provider');
  $email_provider->check();
}

/**
 * Issue #2773325: Remove captcha formatting.
 */
function webform_update_8030(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->clear('format.captcha');
  $settings_config->save();
}

/**
 * Issue #2772697: Option to disable HTML5 validations.
 */
function webform_update_8031() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_form_novalidate', FALSE);
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('settings.form_novalidate', FALSE);
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2775181 by jrockowitz: Address fatal error when handler plugin is missing.
 */
function webform_update_8032() {
  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->set('dependencies', []);
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2778521: Refactor email_confirm element to support customization.
 */
function webform_update_8033() {
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = str_replace('#confirm_title', '#confirm__title', $yaml);
    $data = Yaml::decode($yaml);
    \Drupal::configFactory()->getEditable($webform_config_name)->setData($data)->save();
  }
}

/**
 * Issue #2778715: Refactor other elements.
 */
function webform_update_8034() {
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = str_replace('#other_option_label', '#other__option_label', $yaml);
    $yaml = str_replace('#other_placeholder', '#other__placeholder', $yaml);
    $yaml = str_replace('#other_description', '#other__description', $yaml);
    $data = Yaml::decode($yaml);
    \Drupal::configFactory()->getEditable($webform_config_name)->setData($data)->save();
  }
}

/**
 * Issue #2778857: Description of elements.
 */
function webform_update_8035() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.default_description_display', '');
  $settings_config->save();
}

/**
 * Issue #2781663: Add URL with message to webform confirmation types.
 */
function webform_update_8036() {
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = str_replace('confirmation_type: url', 'confirmation_type: url_message', $yaml);
    $data = Yaml::decode($yaml);
    \Drupal::configFactory()->getEditable($webform_config_name)->setData($data)->save();
  }
}

/**
 * Issue #2781713: Remove id, title, and description from setting.
 */
function webform_update_8037() {
  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $webform_config->clear('settings.id');
    $webform_config->clear('settings.title');
    $webform_config->clear('settings.description');
    $webform_config->save(TRUE);
  }
}

/**
 * Issue #2783527: Order webform settings when saved and apply defaults.
 */
function webform_update_8038() {
  _webform_update_form_settings();
}

/**
 * Issue #2783575: Add autofocus webform setting.
 */
function webform_update_8039() {
  _webform_update_form_settings();
}

/**
 * Issue #2783361: Replace embedded videos by links to them.
 */
function webform_update_8040() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('ui.video_display', 'dialog');
  $settings_config->save();
}

/**
 * Issue #2783771: Add setting for #allowed_tags.
 */
function webform_update_8041() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.allowed_tags', 'admin');
  $settings_config->save();
}

/**
 * Issue #2783855: Date field display and validation.
 */
function webform_update_8042() {
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    $yaml = str_replace('#date_format', '#date_date_format', $yaml);
    $data = Yaml::decode($yaml);
    \Drupal::configFactory()
      ->getEditable($webform_config_name)
      ->setData($data)
      ->save();
  }
}

/**
 * Issue #2783785: Add html editor to UI.
 */
function webform_update_8043(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('ui.html_editor_disabled', FALSE);
  $settings_config->save();
}

/**
 * Issue #2783785: Add html editor to UI.
 */
function webform_update_8044(&$sandbox) {
  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();

    // Decode $elements. Skip any invalid or empty elements.
    try {
      $original_elements = Yaml::decode($data['elements']);
      if (!is_array($original_elements)) {
        continue;
      }
    }
    catch (\Exception $exception) {
      continue;
    }

    $updated_elements = $original_elements;
    _webform_update_8044($updated_elements);
    if ($updated_elements != $original_elements) {
      $data['elements'] = Yaml::encode($updated_elements);
      \Drupal::configFactory()
        ->getEditable($webform_config_name)
        ->setData($data)
        ->save();
    }
  }
}

/**
 * Change #type to autocomplete.
 */
function _webform_update_8044(array &$elements) {
  foreach ($elements as $key => &$element) {
    if (Element::property($key) || !is_array($element)) {
      continue;
    }

    if (!empty($element['#autocomplete_existing']) || !empty($element['#autocomplete_options'])) {
      $element['#type'] = 'autocomplete';
    }

    _webform_update_8044($element);
  }
}

/**
 * Issue #2791823: Improve element attributes class handling.
 */
function webform_update_8045(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.wrapper_classes', "container-inline clearfix
form--inline clearfix    
messages messages--error
messages messages--warning
messages messages--status");
  $settings_config->set('elements.classes', "container-inline clearfix
form--inline clearfix    
messages messages--error
messages messages--warning
messages messages--status");
  $settings_config->save();
}

/**
 * Issue #2792681: Allow a Webform's source entity to be (optionally) populated using query string parameters.
 */
function webform_update_8046() {
  // Add 'form_prepopulate_source_entity' settings for webforms.
  _webform_update_form_settings();
}

/**
 * Issue #2793273 by smaz: Send email to webform element: Use 'option value' instead of 'option text'.
 */
function webform_update_8047(&$sandbox) {
  $ids = [];

  // Update 'webform.webform.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $handlers = $webform_config->get('handlers');

    $has_mail_token = FALSE;
    foreach ($handlers as $handler_id => $handler) {
      if ($handler['id'] != 'email') {
        continue;
      }

      $base = "handlers.$handler_id";

      $mail_fields = ['to_mail', 'cc_mail', 'bcc_mail', 'from_mail'];
      foreach ($mail_fields as $mail_field) {
        $setting_name = $base . '.settings.' . $mail_field;
        $mail = $webform_config->get($setting_name);
        if (preg_match('/:value\]$/', $mail)) {
          $mail = preg_replace('/:value\]$/', ':raw]', $mail);
          $webform_config->set($setting_name, $mail);
          $has_mail_token = TRUE;
        }
      }
    }

    if ($has_mail_token) {
      $ids[] = $webform_config->get('id');
      $webform_config->save(TRUE);
    }
  }

  if (!empty($ids)) {
    $messages[] = t('Updated email handler for webforms: @ids', ['@ids' => implode(', ', array_unique($ids))]);
  }

  return implode("\n", $messages);
}

/**
 * Issue #2802637: Add prefix support to CSV export column headers.
 */
function webform_update_8048() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('export.header_format', $settings_config->get('export.header_keys'));
  $settings_config->clear('export.header_keys');
  $settings_config->clear('export.likert_questions_format');
  $settings_config->set('export.header_prefix', TRUE);
  $settings_config->set('export.header_prefix_label_delimiter', ': ');
  $settings_config->set('export.header_prefix_key_delimiter', '__');
  $settings_config->save();
}

/**
 * Issue #2803139: Add details toggle support to webform settings.
 */
function webform_update_8049() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_form_details_toggle', FALSE);
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  _webform_update_form_settings();
}

/**
 * Issue #2804147: Allow anonymous submission to be updated using a secure token.
 */
function webform_update_8050() {
  // Update 'webform.webform.*' configuration.
  _webform_update_form_settings();
}

/**
 * Issue #2806263: Add property column to submission data table. Fix text_format test value..
 */
function webform_update_8051() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $test_types = Yaml::decode($settings_config->get('test.types'));
  $test_types['text_format'] = [
    ['value' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Negat esse eam, inquit, propter se expetendam. Primum Theophrasti, Strato, physicum se voluit; Id mihi magnum videtur. Itaque mihi non satis videmini considerare quod iter sit naturae quaeque progressio. Quare hoc videndum est, possitne nobis hoc ratio philosophorum dare. Est enim tanti philosophi tamque nobilis audacter sua decreta defendere.</p>'],
    ['value' => '<p>Huius, Lyco, oratione locuples, rebus ipsis ielunior. Duo Reges: constructio interrete. Sed haec in pueris; Sed utrum hortandus es nobis, Luci, inquit, an etiam tua sponte propensus es? Sapiens autem semper beatus est et est aliquando in dolore; Immo videri fortasse. Paulum, cum regem Persem captum adduceret, eodem flumine invectio? Et ille ridens: Video, inquit, quid agas;</p>'],
    ['value' => '<p>Quae cum dixisset, finem ille. Quamquam non negatis nos intellegere quid sit voluptas, sed quid ille dicat. Progredientibus autem aetatibus sensim tardeve potius quasi nosmet ipsos cognoscimus. Gloriosa ostentatio in constituendo summo bono. Qui-vere falsone, quaerere mittimus-dicitur oculis se privasse; Duarum enim vitarum nobis erunt instituta capienda. Comprehensum, quod cognitum non habet? Qui enim existimabit posse se miserum esse beatus non erit. Causa autem fuit huc veniendi ut quosdam hinc libros promerem. Nunc omni virtuti vitium contrario nomine opponitur.</p>'],
  ];
  $settings_config->set('test.types', Yaml::encode($test_types));
  $settings_config->save();
}

/**
 * Issue #2806263: Add property column to submission data table. Update 'webform_submission_data' schema.
 */
function webform_update_8052() {
  $table = 'webform_submission_data';

  // Delete primary keys.
  db_drop_primary_key($table);

  // Move delta to property.
  $field = 'delta';
  $new_field = 'property';
  $property_spec = [
    'description' => "The property of the element's value.",
    'type' => 'varchar',
    'length' => 128,
    'not null' => TRUE,
    'default' => '',
  ];
  db_change_field($table, $field, $new_field, $property_spec);

  // Add new delta.
  $delta_spec = [
    'description' => "The delta of the element's value.",
    'type' => 'int',
    'unsigned' => TRUE,
    'not null' => TRUE,
    'default' => 0,
  ];
  db_add_field($table, 'delta', $delta_spec);

  // Add new primary keys.
  db_add_primary_key($table, ['sid', 'name', 'property', 'delta']);

  // Flush all caches to make sure the db schema is up-to-date.
  drupal_flush_all_caches();
}

/**
 * Issue #2806263: Add property column to submission data table. Fix 'webform_submission_data' records.
 */
function webform_update_8053() {
  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  foreach ($webforms as $webform) {
    $elements = $webform->getElementsFlattenedAndHasValue();

    foreach ($elements as $key => $element) {
      $element_handler = $element_manager->getElementInstance($element);
      $args = [
        ':webform_id' => $webform->id(),
        ':name' => $key,
      ];

      if ($element_handler->hasMultipleValues($element) && !$element_handler->isComposite()) {
        // Fix broken property where property value is string when is should be
        // numeric delta. Applies only to checkboxes within wizard pages.
        $result = db_query("SELECT * FROM {webform_submission_data}  WHERE webform_id=:webform_id AND name=:name ORDER BY webform_id, sid, name, property", $args);
        $deltas = [];
        while ($record = $result->fetchAssoc()) {
          if (!is_numeric($record['property'])) {
            $delta_key = $record['webform_id'] . '-' . $record['sid'] . $record['name'];
            if (isset($deltas[$delta_key])) {
              $deltas[$delta_key]++;
              $delta = $deltas[$delta_key];
            }
            else {
              $deltas[$delta_key] = 0;
              $delta = 0;
            }
            $update_args = $args + [
                ':delta' => $delta,
                ':property' => $record['property'],
              ];
            db_query("UPDATE {webform_submission_data} SET property=:delta WHERE webform_id=:webform_id AND name=:name AND property=:property", $update_args);
          }
        }

        // Move 'delta' from 'property' column for element's that have
        // multiple values.
        db_query("UPDATE {webform_submission_data} SET delta = property WHERE webform_id=:webform_id AND name=:name", $args);
        db_query("UPDATE {webform_submission_data} SET property = '' WHERE webform_id=:webform_id AND name=:name", $args);
      }

      // Make sure value property is set for text_format element.
      if (isset($element['#type']) && $element['#type'] == 'text_format') {
        db_query("UPDATE {webform_submission_data} SET property = 'value' WHERE webform_id=:webform_id AND name=:name", $args);
      }
    }
  }
}

/**
 * Issue #2806263: Add property column to submission data table. Add serial column.
 */
function webform_update_8054() {
  // @see https://www.drupal.org/node/2554097
  // From: \Drupal\webform\Entity\WebformSubmission::baseFieldDefinitions
  $definitions = [];
  $definitions['serial'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Serial number'))
    ->setDescription(t('The serial number of the webform submission entity.'))
    ->setReadOnly(TRUE);
  foreach ($definitions as $name => $definition) {
    \Drupal::entityDefinitionUpdateManager()
      ->installFieldStorageDefinition($name, 'webform_submission', 'webform_submission', $definition);
  }
}

/**
 * Issue #2806263: Add property column to submission data table. Populate serial column.
 */
function webform_update_8055() {
  db_query("UPDATE {webform_submission} SET serial=sid");

  $webforms = Webform::loadMultiple();
  $result = db_query("SELECT webform_id, MAX(serial) AS next_serial FROM {webform_submission} GROUP BY webform_id");
  while ($record = $result->fetchAssoc()) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webforms[$record['webform_id']];
    $webform->setState('next_serial', $record['next_serial'] + 1);
  }
}

/**
 * Issue #2809791: Geolocation. Add Google Maps API key to admin settings.
 */
function webform_update_8056() {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.default_google_maps_api_key', '');
  $settings_config->save();
}

/**
 * Issue #2817535: Drupal file upload by anonymous or untrusted users into public file systems -- PSA-2016-003.
 */
function webform_update_8057() {
  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $element_plugins = $element_manager->getInstances();

  $types = [];
  foreach ($element_plugins as $element_id => $element_plugin) {
    $types[$element_id] = $element_id;
  }

  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.types', $types);
  $settings_config->set('elements.file_public', TRUE);
  $settings_config->save();
}

/**
 * Issue #2819319: Add Time element (with timepicker).
 */
function webform_update_8058() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.types.webform_time', 'webform_time');
  $settings_config->save();
}

/**
 * Issue #2818881: Improve date element. Add message element.
 */
function webform_update_8059() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.types.webform_message', 'webform_message');
  $settings_config->save();
}

/**
 * Issue #2820180: Allow disable results warning and behavior to disabled.
 */
function webform_update_8060() {
  _webform_update_form_settings();
}

/**
 * Issue #2811167: Sortable options table element.
 */
function webform_update_8061() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('elements.types.webform_table_sort', 'webform_table_sort');
  $settings_config->save();
}

/**
 * Issue #2820844: Change webform.setting.element.types to webform.setting.element.excluded.types.
 */
function webform_update_8062() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');

  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $element_plugins = $element_manager->getInstances();

  $element_types = [];
  foreach ($element_plugins as $element_id => $element_plugin) {
    $element_types[$element_id] = $element_id;
  }
  ksort($element_types);

  $included_types = $settings_config->get('elements.types');
  $excluded_types = array_diff($element_types, $included_types);

  $settings_config->set('elements.excluded_types', $excluded_types);
  $settings_config->clear('elements.types');
  $settings_config->save();
}

/**
 * Issue #2821781: Provide a document exporter.
 */
function webform_update_8063() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('export.file_name', 'submission-[webform-submission:serial]');
  $settings_config->save();
}

/**
 * Issue #2823627: Provide dedicated video, audio, and image file upload with media capture.
 */
function webform_update_8064() {
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');

  // Move file specific settings from 'elements' to 'file'.
  $settings_config->set('file.default_max_filesize', $settings_config->get('elements.default_max_filesize'));
  $settings_config->clear('elements.default_max_filesize');

  $settings_config->set('file.file_public', $settings_config->get('elements.file_public'));
  $settings_config->clear('elements.file_public');

  // Rename 'elements.default_file_extensions' to 'file.default_managed_file_extensions'.
  $settings_config->set('file.default_managed_file_extensions', $settings_config->get('elements.default_file_extensions'));
  $settings_config->clear('elements.default_file_extensions');

  // Add new media specific file extensions.
  $settings_config->set('elements.default_audio_file_extensions', 'mp3 ogg wav');
  $settings_config->set('elements.default_document_file_extensions', 'txt rtf pdf doc docx odt ppt pptx odp xls xlsx ods');
  $settings_config->set('elements.default_image_file_extensions', 'gif jpg png svg');
  $settings_config->set('elements.default_video_file_extensions', 'avi mov mp4 ogg wav webm');
  $settings_config->save();
}

/**
 * Issue #2823918: Add custom classes, styles, and properties to webform settings and class names to submit buttons.
 */
function webform_update_8065(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.classes', "container-inline clearfix
form--inline clearfix    
messages messages--error
messages messages--warning
messages messages--status");
  $settings_config->save();
}

/**
 * Issue #2825278: Add settings to suppress CDN warning on Status Report.
 */
function webform_update_8066(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('library.cdn', FALSE);
  $settings_config->save();
}

/**
 * Issue #2825410: Allow webforms labels to be translatable.
 *
 * @see \Drupal\config_translation\Form\ConfigTranslationFormBase::submitForm
 */
function webform_update_8067(&$sandbox) {
  // Skip updates if the Configuration translation module is not enabled.
  if (!\Drupal::moduleHandler()->moduleExists('config_translation')) {
    return;
  }

  $language_manager = \Drupal::languageManager();

  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $languages = $language_manager->getLanguages();
  unset($languages[$language_manager->getDefaultLanguage()->getId()]);

  foreach ($languages as $langcode => $language) {
    $config_factory = \Drupal::configFactory();
    foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
      /** @var Drupal\language\Config\LanguageConfigOverride $config_translation */
      $config_translation = $language_manager->getLanguageConfigOverride($langcode, $webform_config_name);
      $config_elements = $config_translation->get('elements');
      if (empty($config_elements)) {
        continue;
      }

      // Flatten and get any array containging translatable properties.
      // @see \Drupal\webform\WebformTranslationManager::getBaseElements()
      $elements = Yaml::decode($config_elements);
      $elements = WebformElementHelper::getFlattened($elements);
      $translatable_properties = WebformElementHelper::addPrefix($element_manager->getTranslatableProperties());
      foreach ($elements as $element_key => &$element) {
        foreach ($element as $property_key => $property_value) {
          $translatable_property_key = $property_key;
          if (preg_match('/^.*__(.*)$/', $translatable_property_key, $match)) {
            $translatable_property_key = '#' . $match[1];
          }

          if (in_array($translatable_property_key, ['#options', '#answers']) && is_string($property_value)) {
            unset($element[$property_key]);
          }
          elseif (!isset($translatable_properties[$translatable_property_key])) {
            unset($element[$property_key]);
          }
        }
        if (empty($element)) {
          unset($elements[$element_key]);
        }
      }

      // DEBUG:
      // drush_print($webform_config_name); drush_print($config_elements); drush_print(Yaml::encode($elements));
      $config_translation->set('elements', Yaml::encode($elements))->save();
    }
  }
}

/**
 * Issue #2826973: Alert for unsaved changes in webform.
 */
function webform_update_8068(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_form_unsaved', FALSE);
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  _webform_update_form_settings();
}

/**
 * Issue #2826976: Allow CSS and JavaScript to be injected into a webform.
 */
function webform_update_8069(&$sandbox) {
  // Update 'webform.webform.*' configuration.
  _webform_update_form_settings();
}

/**
 * Issue #2828138: Disable the previous functionality for a wizard page.
 */
function webform_update_8070(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('settings.default_form_disable_back', FALSE);
  $settings_config->save();

  // Update 'webform.webform.*' configuration.
  _webform_update_form_settings();
}

/**
 * Issue #2826300: webform.settings.yml is out-of-sync and missing default values.
 */
function webform_update_8071(&$sandbox) {
  // Update 'webform.settings' configuration.
  _webform_update_admin_settings();
}

/**
 * Issue #2830472: Add test to confirm that webform.settings are being correctly updated via the UI.
 */
function webform_update_8072(&$sandbox) {
  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $settings_config->set('format', array_filter($settings_config->get('format')));
  $settings_config->save();
}
