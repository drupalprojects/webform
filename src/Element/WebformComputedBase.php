<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformXss;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base class for 'webform_computed' elements.
 */
abstract class WebformComputedBase extends FormElement {

  /**
   * Denotes HTML.
   *
   * @var string
   */
  const MODE_HTML = 'html';

  /**
   * Denotes plain text.
   *
   * @var string
   */
  const MODE_TEXT = 'text';

  /**
   * Denotes markup whose content type should be detected.
   *
   * @var string
   */
  const MODE_AUTO = 'auto';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processWebformComputed'],
      ],
      '#input' => TRUE,
      '#value' => '',
      '#mode' => NULL,
      '#hide_empty' => FALSE,
      // Note: Computed elements do not use the default #ajax wrapper, which is
      // why we can use #ajax as a boolean.
      // @see \Drupal\Core\Render\Element\RenderElement::preRenderAjaxForm
      '#ajax' => FALSE,
      '#webform_submission' => NULL,
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes a Webform computed token element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformComputed(&$element, FormStateInterface $form_state, &$complete_form) {
    $webform_submission = static::getWebformSubmission($element, $form_state);
    if ($webform_submission) {
      $value = static::processValue($element, $webform_submission);;

      // Hide empty computed element using display:none so that #states API
      // can still use the empty computed value.
      if ($value === '' && $element['#hide_empty']) {
        $element['#wrapper_attributes']['style'] = 'display:none';
      }

      // Set tree.
      $element['#tree'] = TRUE;

      // Display markup.
      $element['value']['#markup'] = $value;
      $element['value']['#allowed_tags'] = WebformXss::getAdminTagList();

      // Include hidden element so that computed value will be available to
      // conditions (#states).
      $element['hidden'] = [
        '#type' => 'hidden',
        '#value' => ['#markup' => $value],
        '#parents' => $element['#parents'],
      ];

      // Set #type to item to trigger #states behavior.
      // @see drupal_process_states;
      $element['#type'] = 'item';
    }

    if (!empty($element['#states'])) {
      webform_process_states($element, '#wrapper_attributes');
    }

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformComputed']);

    /**************************************************************************/
    // Ajax support
    /**************************************************************************/

    // Enabled Ajax support only for computed elements associated with a
    // webform submission form.
    if ($element['#ajax'] && $form_state->getFormObject() instanceof WebformSubmissionForm) {
      // Get button name and wrapper id.
      $button_name = 'webform-computed-' . implode('-', $element['#parents']) . '-button';
      $wrapper_id = 'webform-computed-' . implode('-', $element['#parents']) . '-wrapper';

      // Get computed value element keys which are used to trigger Ajax updates.
      preg_match_all('/(?:\[webform_submission:values:|data\.)([_a-z]+)/', $element['#value'], $matches);
      $element_keys = $matches[1] ?: [];
      $element_keys = array_unique($element_keys);

      // Wrapping the computed element is two div tags.
      // div.js-webform-computed is used to initialize the Ajax updates.
      // div#wrapper_id is used to display response from the Ajax updates.
      $element['#wrapper_id'] = $wrapper_id;
      $element['#prefix'] = '<div class="js-webform-computed" data-webform-element-keys="' . implode(',', $element_keys) . '">' .
        '<div class="js-webform-computed-wrapper" id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div></div>';

      // Add hidden update button.
      $element['update'] = [
        '#type' => 'submit',
        '#value' => t('Update'),
        '#validate' => [[get_called_class(), 'validateWebformComputedCallback']],
        '#submit' => [[get_called_class(), 'submitWebformComputedCallback']],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxWebformComputedCallback'],
          'wrapper' => $wrapper_id,
          'progress' => ['type' => 'none'],
        ],
        // Hide button and disable validation.
        '#attributes' => [
          'class' => [
            'js-hide',
            'js-webform-novalidate',
            'js-webform-computed-submit',
          ],
        ],
        // Issue #1342066 Document that buttons with the same #value need a unique
        // #name for the Form API to distinguish them, or change the Form API to
        // assign unique #names automatically.
        '#name' => $button_name,
      ];

      // Attached computed element library.
      $element['#attached']['library'][] = 'webform/webform.element.computed';
    }

    return $element;
  }

  /**
   * Process computed value.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array|string
   *   The string with tokens replaced.
   */
  public static function processValue(array $element, WebformSubmissionInterface $webform_submission) {
    return $element['#value'];
  }

  /**
   * Validates an computed element.
   */
  public static function validateWebformComputed(&$element, FormStateInterface $form_state, &$complete_form) {
    // Make sure the form's state value uses the computed value and not the
    // raw #value. This ensures conditional handlers are trigger using
    // the accurate computed value.
    $webform_submission = static::getWebformSubmission($element, $form_state);
    if ($webform_submission) {
      $value = static::processValue($element, $webform_submission);;
      $form_state->setValueForElement($element['value'], NULL);
      $form_state->setValueForElement($element['hidden'], NULL);
      $form_state->setValueForElement($element, $value);
    }
  }

  /****************************************************************************/
  // Form/Ajax callbacks.
  /****************************************************************************/

  /**
   * Webform computed element validate callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateWebformComputedCallback(array $form, FormStateInterface $form_state) {
    $form_state->clearErrors();
  }

  /**
   * Webform computed element submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitWebformComputedCallback(array $form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();

    // Build webform submission with validated and processed form state values.
    if ($form_object instanceof WebformSubmissionForm) {
      $entity = $form_object->buildEntity($form, $form_state);
      $form_object->setEntity($entity);
    }

    $form_state->setRebuild();
  }

  /**
   * Webform computed element Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The computed element element.
   */
  public static function ajaxWebformComputedCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Only return the wrapper id, this prevents the computed element from
    // being reinitialized via JS after each update.
    // @see js/webform.element.computed.js
    $element['#prefix'] = '<div class="js-webform-computed-wrapper" id="' . $element['#wrapper_id'] . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /****************************************************************************/
  // Form/Ajax helpers and callbacks.
  /****************************************************************************/

  /**
   * Get an element's value mode/type.
   *
   * @param array $element
   *   The element.
   *
   * @return string
   *   The markup type (html or text).
   */
  public static function getMode(array $element) {
    if (empty($element['#mode']) || $element['#mode'] === static::MODE_AUTO) {
      return (WebformHtmlHelper::containsHtml($element['#value'])) ? static::MODE_HTML : static::MODE_TEXT;
    }
    else {
      return $element['#mode'];
    }
  }

  /**
   * Get the Webform submission for element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   A webform submission.
   */
  protected static function getWebformSubmission(array $element, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if (isset($element['#webform_submission'])) {
      if (is_string($element['#webform_submission'])) {
        return WebformSubmission::load($element['#webform_submission']);
      }
      else {
        return $element['#webform_submission'];
      }
    }
    elseif ($form_object instanceof WebformSubmissionForm) {
      return $form_object->getEntity();
    }
    else {
      return NULL;
    }
  }

}
