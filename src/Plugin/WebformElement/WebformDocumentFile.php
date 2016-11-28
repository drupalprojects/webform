<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_document_file' element.
 *
 * @WebformElement(
 *   id = "webform_document_file",
 *   label = @Translation("Document file"),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformDocumentFile extends WebformManagedFileBase {}
