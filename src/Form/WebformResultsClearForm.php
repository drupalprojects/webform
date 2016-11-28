<?php

namespace Drupal\webform\Form;

use Drupal\Core\Url;

/**
 * Webform for webform results clear webform.
 */
class WebformResultsClearForm extends WebformSubmissionsDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_results_clear';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->sourceEntity) {
      $t_args = ['%title' => $this->sourceEntity->label()];
    }
    else {
      $t_args = ['%title' => $this->webform->label()];
    }
    return $this->t('Are you sure you want to delete all submissions to the %title webform?', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $route_name = $this->requestHandler->getRouteName($this->webform, $this->sourceEntity, 'webform.results_submissions');
    $route_parameters = $this->requestHandler->getRouteParameters($this->webform, $this->sourceEntity);
    return new Url($route_name, $route_parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    if ($this->sourceEntity) {
      $t_args = ['%title' => $this->sourceEntity->label()];
    }
    else {
      $t_args = ['%title' => $this->webform->label()];
    }
    $this->t('Webform %title submissions cleared.', $t_args);
  }

}
