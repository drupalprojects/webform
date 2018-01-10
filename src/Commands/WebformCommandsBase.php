<?php

namespace Drupal\webform\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for Webform commands for Drush 9.x.
 */
abstract class WebformCommandsBase extends DrushCommands {

  /**
   * The webform CLI service.
   *
   * @var \Drupal\webform\Commands\WebformCliServiceInterface
   */
  protected $cliService;

  /**
   * Constructs a WebformCommandsBase object.
   *
   * @param \Drupal\webform\Commands\WebformCliServiceInterface $cli_service
   *   The webform CLI service.
   */
  public function __construct(WebformCliServiceInterface $cli_service) {
    $this->cliService = $cli_service;

    // Injecting the WebformCommand into the CLI service so that calls to
    // drush functions can be delegatef back the below methods.
    // @see \Drupal\webform\Commands\WebformCliService::__call
    $this->cliService->setCommand($this);
  }

  public function drush_confirm($question) {
    return $this->io()->confirm($question);
  }

  public function drush_choice($msg, $choices, $default) {
    return $this->io()->choice($msg, $choices, $default);
  }

  public function drush_log($message, $type = LogLevel::INFO) {
    $this->logger()->log($type, $message);
  }

  public function drush_print($message) {
    $this->output()->writeln($message);
  }

  public function drush_get_option($name) {
    return $this->input()->getOption($name);
  }

  public function drush_user_abort() {
    throw new UserAbortException();
  }

  public function drush_set_error($error) {
    throw new \Exception($error);
  }

  public function drush_redispatch_get_options() {
    return Drush::redispatchOptions();
  }

  public function drush_mkdir($path) {
    $fs = new Filesystem();
    $fs->mkdir($path);
    return true;
  }

  public function drush_tarball_extract($path, $destination = FALSE) {
    $this->drush_mkdir($destination);
    $return = drush_shell_cd_and_exec(dirname($path), "unzip %s -d %s", $path, $destination);
    if (!$return) {
      throw new \Exception(dt('Unable to unzip !filename.', array('!filename' => $path)));
    }
    return $return;
  }

}
