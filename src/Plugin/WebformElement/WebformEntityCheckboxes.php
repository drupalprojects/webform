<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_entity_checkboxes' element.
 *
 * @WebformElement(
 *   id = "webform_entity_checkboxes",
 *   label = @Translation("Entity checkboxes"),
 *   category = @Translation("Entity reference elements"),
 *   multiple = TRUE,
 * )
 */
class WebformEntityCheckboxes extends Checkboxes implements WebformEntityReferenceInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

}
