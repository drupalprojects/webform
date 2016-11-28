<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_entity_select' element.
 *
 * @WebformElement(
 *   id = "webform_entity_select",
 *   label = @Translation("Entity select"),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class WebformEntitySelect extends Select implements WebformEntityReferenceInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

}
