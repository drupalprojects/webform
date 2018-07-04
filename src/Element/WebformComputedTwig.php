<?php

namespace Drupal\webform\Element;

use Drupal\webform\Twig\TwigExtension;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an item to display computed webform submission values using Twig.
 *
 * @RenderElement("webform_computed_twig")
 */
class WebformComputedTwig extends WebformComputedBase {


  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#spaceless' => FALSE,
      '#trim' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processValue(array $element, WebformSubmissionInterface $webform_submission) {
    $template = $element['#value'];
    if (!empty($element['#spaceless'])) {
      $template = '{% spaceless %}' . $template . '{% endspaceless %}';
    }
    $options = ['html' => (static::getMode($element) === static::MODE_HTML)];

    $value = TwigExtension::renderTwigTemplate($webform_submission, $template, $options);
    return (!empty($element['#trim'])) ? trim($value) : $value;
  }

}
