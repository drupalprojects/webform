<?php

namespace Drupal\webform_devel\Commands;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformYaml;
use Drush\Commands\DrushCommands;
use Psr\Log\LogLevel;


/**
 * Webform scheduled email commands for Drush 9.x.
 */
class WebformDevelCommands extends DrushCommands {

  /**
   * Executes devel export config.
   *
   * @command webform:devel:config:update
   * @aliases wfdcu
   */
  public function drush_webform_devel_config_update() {
    module_load_include('inc', 'webform', 'includes/webform.install');

    $files = file_scan_directory(drupal_get_path('module', 'webform'), '/^webform\.webform\..*\.yml$/');
    $total = 0;
    foreach ($files as $filename => $file) {
      try {
        $original_yaml = file_get_contents($filename);

        $tidied_yaml = $original_yaml;
        $data = Yaml::decode($tidied_yaml);

        // Skip translated configu files which don't include a langcode.
        // @see tests/modules/webform_test_translation/config/install/language
        if (empty($data['langcode'])) {
          continue;
        }

        $data = _webform_update_webform_setting($data);
        $tidied_yaml = WebformYaml::tidy(Yaml::encode($data)) . PHP_EOL;

        if ($tidied_yaml != $original_yaml) {
           $this->output()->writeln(dt('Updating @file...', ['@file' => $file->filename]));
          file_put_contents($file->uri, $tidied_yaml);
          $total++;
        }
      }
      catch (\Exception $exception) {
        $message = 'Error parsing: ' . $file->filename . PHP_EOL . $exception->getMessage();
        if (strlen($message) > 255) {
          $message = substr($message, 0, 255) . '...';
        }
        $this->logger()->log($message, LogLevel::ERROR);
         $this->output()->writeln($message);
      }
    }

    if ($total) {
       $this->output()->writeln(dt('@total webform.webform.* configuration file(s) updated.', ['@total' => $total]));
    }
    else {
       $this->output()->writeln(dt('No webform.webform.* configuration files updated.'));
    }
  }

}
