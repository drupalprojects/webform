<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'tel' element.
 *
 * @WebformElement(
 *   id = "tel",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tel.php/class/Tel",
 *   label = @Translation("Telephone"),
 *   description = @Translation("Provides a form element for entering a telephone number."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Telephone extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'multiple' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array &$element, $value, array $options = []) {
    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'link':
        // Issue #2484693: Telephone Link field formatter breaks Drupal with 5
        // digits or less in the number
        // return [
        //  '#type' => 'link',
        //  '#title' => $value,
        //  '#url' => \Drupal::pathValidator()->getUrlIfValid('tel:' . $value),
        // ];
        // Workaround: Manually build a static HTML link.
        $t_args = [':tel' => 'tel:' . $value, '@tel' => $value];
        return t('<a href=":tel">@tel</a>', $t_args);

      default:
        return parent::formatHtmlItem($element, $value, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'link' => $this->t('Link'),
    ];
  }

}
