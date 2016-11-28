<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an edit webform for a webform element.
 */
class WebformUiElementEditForm extends WebformUiElementFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL) {
    $this->element = $webform->getElementDecoded($key);
    if ($this->element === NULL) {
      throw new NotFoundHttpException();
    }

    // Handler changing element type.
    if ($type = $this->getRequest()->get('type')) {
      $webform_element = $this->getWebformElement();
      $related_types = $webform_element->getRelatedTypes($this->element);
      if (!isset($related_types[$type])) {
        throw new NotFoundHttpException();
      }
      $this->originalType = $this->element['#type'];
      $this->element['#type'] = $type;
    }

    $form['#title'] = $this->t('Edit @title element', [
      '@title' => (!empty($this->element['#title'])) ? $this->element['#title'] : $key,
    ]);

    $this->action = $this->t('updated');
    return parent::buildForm($form, $form_state, $webform, $key);
  }

}
