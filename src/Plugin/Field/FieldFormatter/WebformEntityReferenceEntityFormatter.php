<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\WebformMessageManagerInterface;

/**
 * Plugin implementation of the 'Webform rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_entity_reference_entity_view",
 *   label = @Translation("Webform"),
 *   description = @Translation("Display the referenced webform with default submission data."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceEntityFormatter extends WebformEntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $source_entity = $items->getEntity();
    $this->messageManager->setSourceEntity($source_entity);

    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Do not display the webform if the current user can't create submissions.
      if ($entity->id() && !$entity->access('submission_create')) {
        continue;
      }

      if ($this->isOpen($entity, $items[$delta])) {
        $values = [
          'entity_type' => $source_entity->getEntityTypeId(),
          'entity_id' => $source_entity->id(),
        ];
        if (!empty($items[$delta]->default_data)) {
          $values['data'] = Yaml::decode($items[$delta]->default_data);
        }
        $elements[$delta] = $entity->getSubmissionForm($values);
      }
      else {
        $this->messageManager->setWebform($entity);
        $message_type = $this->isOpening($entity, $items[$delta]) ? WebformMessageManagerInterface::FORM_OPEN_MESSAGE : WebformMessageManagerInterface::FORM_CLOSE_MESSAGE;
        $elements[$delta] = $this->messageManager->build($message_type);
      }
    }

    return $elements;
  }

}
