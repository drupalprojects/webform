<?php

/**
 * @file
 * Webform module editor file upload hooks.
 *
 * Because Webform's are config entities the editor.module's file uploading
 * is not supported. The below code add supports for file uploads.
 *
 * Below functions are copied from editor.module.
 */

use Drupal\webform\WebformInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;

/**
 * Implements hook_webform_insert().
 */
function webform_webform_insert(WebformInterface $webform) {
  $uuids = _webform_get_file_uuids($webform);
  _webform_record_file_usage($uuids, $webform);
}

/**
 * Implements hook_webform_update().
 */
function webform_webform_update(WebformInterface $webform) {
  $original_uuids = _webform_get_file_uuids($webform->original);
  $uuids = _webform_get_file_uuids($webform);

  // Detect file usages that should be incremented.
  $added_files = array_diff($uuids, $original_uuids);
  _webform_record_file_usage($added_files, $webform);

  // Detect file usages that should be decremented.
  $removed_files = array_diff($original_uuids, $uuids);
  _webform_delete_file_usage($removed_files, $webform, 1);
}

/**
 * Implements hook_webform_delete().
 */
function webform_webform_delete(WebformInterface $webform) {
  $uuids = _webform_get_file_uuids($webform);
  _webform_delete_file_usage($uuids, $webform, 0);
}

/**
 * Records file usage of files referenced by formatted text fields.
 *
 * Every referenced file that does not yet have the FILE_STATUS_PERMANENT state,
 * will be given that state.
 *
 * @param array $uuids
 *   An array of file entity UUIDs.
 * @param \Drupal\webform\WebformInterface $webform
 *   An entity whose fields to inspect for file references.
 */
function _webform_record_file_usage(array $uuids, WebformInterface $webform) {
  if (empty($uuids)) {
    return;
  }

  /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
  $file_usage = \Drupal::service('file.usage');

  foreach ($uuids as $uuid) {
    if ($file = \Drupal::entityManager()->loadEntityByUuid('file', $uuid)) {
      if ($file->status !== FILE_STATUS_PERMANENT) {
        $file->status = FILE_STATUS_PERMANENT;
        $file->save();
      }
      $file_usage->add($file, 'editor', $webform->getEntityTypeId(), $webform->id());
    }
  }
}

/**
 * Deletes file usage of files referenced by formatted text fields.
 *
 * @param array $uuids
 *   An array of file entity UUIDs.
 * @param \Drupal\webform\WebformInterface $webform
 *   An entity whose fields to inspect for file references.
 * @param $count
 *   The number of references to delete. Should be 1 when deleting a single
 *   revision and 0 when deleting an entity entirely.
 *
 * @see \Drupal\file\FileUsage\FileUsageInterface::delete()
 */
function _webform_delete_file_usage(array $uuids, WebformInterface $webform, $count) {
  if (empty($uuids)) {
    return;
  }

  /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
  $file_usage = \Drupal::service('file.usage');

  $make_unused_managed_files_temporary = \Drupal::config('webform.settings')->get('html_editor.make_unused_managed_files_temporary');
  foreach ($uuids as $uuid) {
    if ($file = \Drupal::entityManager()->loadEntityByUuid('file', $uuid)) {
      $file_usage->delete($file, 'editor', $webform->getEntityTypeId(), $webform->id(), $count);
      // Make unused files temporary.
      if ($make_unused_managed_files_temporary && empty($file_usage->listUsage($file)) && !$file->isTemporary()) {
        $file->setTemporary();
        $file->save();
      }
    }
  }
}


/**
 * Finds all files referenced (data-entity-uuid) by formatted text fields.
 *
 * @param \Drupal\webform\WebformInterface $webform
 *   An entity whose fields to analyze.
 *
 * @return array
 *   An array of file entity UUIDs.
 */
function _webform_get_file_uuids(WebformInterface $webform) {
  $text = Yaml::encode($webform->toArray());
  return _webform_parse_file_uuids($text);
}

/**
 * Parse an HTML snippet for any linked file with data-entity-uuid attributes.
 *
 * @param string $text
 *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
 *   import.
 *
 * @return array
 *   An array of all found UUIDs.
 */
function _webform_parse_file_uuids($text) {
  if (strpos($text, 'data-entity-uuid') === FALSE) {
    return [];
  }

  $dom = Html::load($text);
  $xpath = new \DOMXPath($dom);
  $uuids = [];
  foreach ($xpath->query('//*[@data-entity-type="file" and @data-entity-uuid]') as $node) {
    $uuids[] = $node->getAttribute('data-entity-uuid');
  }
  return array_unique($uuids);
}
