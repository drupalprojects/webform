<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformElementBase;

/**
 * Provides a 'password' element.
 *
 * @WebformElement(
 *   id = "password",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Password.php/class/Password",
 *   label = @Translation("Password"),
 *   category = @Translation("Basic elements"),
 * )
 */
class Password extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function formatText(array &$element, $value, array $options = []) {
    $format = $this->getFormat($element);
    switch ($format) {
      case 'obscured':
        return '********';

      default:
        return parent::formatText($element, $value, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat() {
    return 'obscured';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    return parent::getFormats() + [
      'obscured' => $this->t('Obscured'),
    ];
  }

}
