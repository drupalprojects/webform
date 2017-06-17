<?php


namespace Drupal\webform_devel\Logger;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Render\RendererInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class WebformDevelLog implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * A configuration object MSK admin settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SysLog object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactory $config_factory, LogMessageParserInterface $parser, RendererInterface $renderer) {
    $this->config = $config_factory->get('webform_devel.settings');
    $this->parser = $parser;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Never display log messages on the command line.
    if (php_sapi_name() == 'cli') {
      return;
    }

    $debug = $this->config->get('logger.debug') ?: 0;
    if ($debug == 1 && in_array($context['channel'], ['theme', 'php'])) {
      // Populate the message placeholders and then replace them in the message.
      $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
      $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);
      $build = ['#markup' => $message];
      drupal_set_message($this->renderer->renderPlain($build), ($level <= 3) ? 'error' : 'warning');
    }
  }

}
