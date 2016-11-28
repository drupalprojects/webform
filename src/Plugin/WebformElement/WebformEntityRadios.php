<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_entity_radios' element.
 *
 * @WebformElement(
 *   id = "webform_entity_radios",
 *   label = @Translation("Entity radios"),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class WebformEntityRadios extends Radios implements WebformEntityReferenceInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

}
