<?php

namespace Drupal\webform_devel\Logger;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class WebformDevelLog implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a SysLog object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactory $config_factory, LogMessageParserInterface $parser) {
    $this->configFactory = $config_factory;

    $this->config = $config_factory->get('webform_devel.settings');
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Never display log messages on the command line.
    if (php_sapi_name() == 'cli') {
      return;
    }

    // Never display log messages if the 'system.logging' 'error_level'
    // is set to 'hide'.
    if ($this->configFactory->get('system.logging')->get('error_level') === 'hide') {
      return;
    }

    $debug = $this->configFactory->get('webform_devel.settings')->get('logger.debug') ?: 0;
    if ($debug == 1 && in_array($context['channel'], ['theme', 'php'])) {
      // Populate the message placeholders and then replace them in the message.
      $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
      $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);
      $build = ['#markup' => $message];
      // IMPORTANT: Do not inject the renderer into WebformDevelLog because
      // it will cause...
      // "LogicException: The database connection is not serializable." errors
      // for all Ajax callbacks.
      // @see \Drupal\Core\Render\Renderer
      drupal_set_message(\Drupal::service('renderer')->renderPlain($build), ($level <= 3) ? 'error' : 'warning');
    }
  }

}
