<?php

/**
 * @file
 * Hooks related to Webform module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformElement.
 *
 * @param array $elements
 *   The array of webform handlers, keyed on the machine-readable element name.
 */
function hook_webform_element_info_alter(array &$elements) {

}

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformHandler.
 *
 * @param array $handlers
 *   The array of webform handlers, keyed on the machine-readable handler name.
 */
function hook_webform_handler_info_alter(array &$handlers) {

}

/**
 * Alter the webform options by id.
 *
 * @param array $options
 *   An associative array of options.
 * @param array $element
 *   The webform element that the options is for.
 */
function hook_webform_options_WEBFORM_OPTIONS_ID_alter(array &$options, array &$element = []) {

}

/**
 * Perform alterations before a webform submission form is rendered.
 *
 * This hook is identical to hook_form_alter() but allows the
 * hook_webform_submission_form_alter() function to be stored in a dedicated
 * include file and it also allows the Webform module to implement webform alter
 * logic on another module's behalf.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param string $form_id
 *   String representing the webform's id.
 *
 * @see webform.honeypot.inc
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 *
 * @ingroup form_api
 */
function hook_webform_submission_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

}

/**
 * @} End of "addtogroup hooks".
 */
