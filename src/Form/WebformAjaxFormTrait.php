<?php

namespace Drupal\webform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\webform\Ajax\ScrollTopCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Trait class for Webform Ajax support.
 */
trait WebformAjaxFormTrait {

  /**
   * Returns if webform is using Ajax.
   *
   * @return bool
   *   TRUE if webform is using Ajax.
   */
  abstract protected function isAjax();

  /**
   * Cancel form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that display validation error messages or redirects
   *   to a URL
   */
  abstract public function cancelAjaxForm(array &$form, FormStateInterface $form_state);

  /**
   * Get the form's Ajax wrapper id.
   * @return string
   */
  protected function getWrapperId() {
    return $this->getFormId() . '-ajax';
  }

  /**
   * Add Ajax support to a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $setting
   *   Ajax settings.
   *
   * @return array
   *   The form with Ajax callbacks.
   */
  protected function buildAjaxForm(array &$form, FormStateInterface $form_state, array $settings = []) {
    if (!$this->isAjax()) {
      return $form;
    }

    // Apply default settings.
    $settings += [
      'disable-refocus' => TRUE,
      'effect' => 'fade',
      'speed' => 1000,
      'progress' => [
        'type' => 'throbber',
        'message' => '',
      ],
    ];

    // Make sure the form has (submit) actions.
    if (!isset($form['actions'])) {
      return $form;
    }

    // Add Ajax callback to all submit buttons.
    foreach (Element::children($form['actions']) as $key) {
      $is_submit_button = (isset($form['actions'][$key]['#type']) && $form['actions'][$key]['#type'] == 'submit');
      if ($is_submit_button) {
        $form['actions'][$key]['#ajax'] = [
          'callback' => '::submitAjaxForm',
          'event' => 'click',
        ] + $settings;
      }
    }

    // Add Ajax wrapper around the form.
    $form['#form_wrapper_id'] = $this->getWrapperId();
    $form['#prefix'] = '<div id="' . $this->getWrapperId() . '">';
    $form['#suffix'] = '</div>';

    // Add Ajax library which contains 'Scroll to top' Ajax command and
    // Ajax callback for confirmation back to link.
    $form['#attached']['library'][] = 'webform/webform.ajax';

    return $form;
  }

  /**
   * Submit form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that display validation error messages or redirects
   *   to a URL
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      // Display validation errors.
      return $this->replaceForm($form);
    }
    elseif ($form_state->isRebuilding()) {
      // Rebuild form.
      return $this->replaceForm($form);
    }
    elseif ($redirect_url = $this->getFormStateRedirectUrl($form_state)){
      // Redirect to URL.
      $response = new AjaxResponse();
      $response->addCommand(new RedirectCommand($redirect_url));
      return $response;
    }
    else {
      return $this->cancelAjaxForm($form, $form_state);
    }
  }

  /**
   * Replace form via an Ajax response.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that replaces a form.
   */
  protected function replaceForm(array $form) {
    // Display messages first by prefixing it the form and setting its weight
    // to -1000.
    $form = [
      'status_messages' => [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ],
    ] + $form;

    // Remove wrapper.
    unset($form['#prefix'], $form['#suffix']);

    // Get wrapper id.
    $wrapper_id  = '#' . $this->getWrapperId();

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand($wrapper_id, $form));
    $response->addCommand(new ScrollTopCommand($wrapper_id));
    return $response;
  }

  /**
   * Get redirect URL from the form's state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool|\Drupal\Core\GeneratedUrl|string
   *   The redirect URL or FALSE if the form is not redirecting.
   */
  protected function getFormStateRedirectUrl(FormStateInterface $form_state) {
    // Always check the ?destination which is used by the off-canvas/system tray.
    if ($this->requestStack->getCurrentRequest()->get('destination')) {
      return base_path() . $this->getRedirectDestination()->get();
    }

    // ISSUE:
    // Can't get the redirect URL from the form state during an AJAX submission.
    //
    // WORKAROUND:
    // Re-enable redirect, grab the URL, and then disable again.
    $no_redirect = $form_state->isRedirectDisabled();
    $form_state->disableRedirect(FALSE);
    $redirect = $form_state->getRedirect() ?: $form_state->getResponse();
    $form_state->disableRedirect($no_redirect);

    if ($redirect instanceof RedirectResponse) {
      return $redirect->getTargetUrl();
    }
    elseif ($redirect instanceof Url) {
      return $redirect->setAbsolute()->toString();
    }
    else {
      return FALSE;
    }
  }
  
}
