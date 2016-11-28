<?php

namespace Drupal\webform\BreadCrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a webform breadcrumb builder.
 */
class WebformBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current route's entity or plugin type.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    // All routes must begin or contain 'webform.
    if (strpos($route_name, 'webform') === FALSE) {
      return FALSE;
    }

    $args = explode('.', $route_name);

    // Skip all config_translation routes except the overview
    // and allow Drupal to use the path as the breadcrumb.
    if (strpos($route_name, 'config_translation') !== FALSE && $route_name != 'entity.webform.config_translation_overview') {
      return FALSE;
    }

    if ($args[0] == 'entity' && ($args[2] == 'webform' ||  $args[2] == 'webform_submission')) {
      $this->type = 'webform_source_entity';
    }
    elseif (strpos($route_name, 'entity.webform.handler.') === 0) {
      $this->type = 'webform_handler';
    }
    elseif (strpos($route_name, 'entity.webform_ui.element') === 0) {
      $this->type = 'webform_element';
    }
    elseif (strpos($route_match->getRouteName(), 'webform.user.submissions') !== FALSE) {
      $this->type = 'webform_user_submissions';
    }
    elseif ($route_match->getParameter('webform_submission') instanceof WebformSubmissionInterface && strpos($route_name, 'webform.user.submission') !== FALSE) {
      $this->type = 'webform_user_submission';
    }
    elseif ($route_match->getParameter('webform_submission') instanceof WebformSubmissionInterface && $route_match->getParameter('webform_submission')->access('admin')) {
      $this->type = 'webform_submission';
    }
    elseif (($route_match->getParameter('webform') instanceof WebformInterface  && $route_match->getParameter('webform')->access('admin'))) {
      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $route_match->getParameter('webform');
      $this->type = ($webform->isTemplate() && \Drupal::moduleHandler()->moduleExists('webform_templates')) ? 'webform_template' : 'webform';
    }
    else {
      $this->type = NULL;
    }

    return ($this->type) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();

    if ($this->type == 'webform_source_entity') {
      /** @var \Drupal\webform\WebformRequestInterface $request_handler */
      $request_handler = \Drupal::service('webform.request');
      $source_entity = $request_handler->getCurrentSourceEntity(['webform', 'webform_submission']);
      $entity_type = $source_entity->getEntityTypeId();
      $entity_id = $source_entity->id();

      $breadcrumb = new Breadcrumb();
      $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
      $breadcrumb->addLink($source_entity->toLink());
      if ($webform_submission = $route_match->getParameter('webform_submission')) {

        if (strpos($route_match->getRouteName(), 'webform.user.submission') !== FALSE) {
          $breadcrumb->addLink(Link::createFromRoute($this->t('Submissions'), "entity.$entity_type.webform.user.submissions", [$entity_type => $entity_id]));
        }
        elseif ($source_entity->access('webform_submission_view') || $webform_submission->access('view_any')) {
          $breadcrumb->addLink(Link::createFromRoute($this->t('Results'), "entity.$entity_type.webform.results_submissions", [$entity_type => $entity_id]));
        }
        elseif ($webform_submission->access('view_own')) {
          $breadcrumb->addLink(Link::createFromRoute($this->t('Results'), "entity.$entity_type.webform.user.submissions", [$entity_type => $entity_id]));
        }
      }
    }
    else {
      $breadcrumb = new Breadcrumb();
      $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Structure'), 'system.admin_structure'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Webforms'), 'entity.webform.collection'));
      switch ($this->type) {
        case 'webform_template':
          $breadcrumb->addLink(Link::createFromRoute('Templates', 'entity.webform.templates'));
          break;

        case 'webform_element':
          /** @var \Drupal\webform\WebformInterface $webform */
          $webform = $route_match->getParameter('webform');
          $breadcrumb->addLink(Link::createFromRoute($webform->label(), 'entity.webform.canonical', ['webform' => $webform->id()]));
          $breadcrumb->addLink(Link::createFromRoute('Elements', 'entity.webform.edit_form', ['webform' => $webform->id()]));
          break;

        case 'webform_handler':
          if ($route_name != 'webform.handler_plugins') {
            /** @var \Drupal\webform\WebformInterface $webform */
            $webform = $route_match->getParameter('webform');
            $breadcrumb->addLink(Link::createFromRoute($webform->label(), 'entity.webform.canonical', ['webform' => $webform->id()]));
            $breadcrumb->addLink(Link::createFromRoute('Emails / Handlers', 'entity.webform.handlers_form', ['webform' => $webform->id()]));
          }
          break;

        case 'webform_options':
          if ($route_name != 'entity.webform_options.collection') {
            $breadcrumb->addLink(Link::createFromRoute($this->t('Options'), 'entity.webform_options.collection'));
          }
          break;

        case 'webform_submission':
          /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
          $webform_submission = $route_match->getParameter('webform_submission');
          $webform = $webform_submission->getWebform();

          $breadcrumb->addLink(Link::createFromRoute($webform->label(), 'entity.webform.canonical', ['webform' => $webform->id()]));
          $breadcrumb->addLink(Link::createFromRoute($this->t('Results'), 'entity.webform.results_submissions', ['webform' => $webform->id()]));
          break;

        case 'webform_user_submissions':
          /** @var \Drupal\webform\WebformInterface $webform */
          $webform = $route_match->getParameter('webform');

          $breadcrumb = new Breadcrumb();
          $breadcrumb->addLink(Link::createFromRoute($webform->label(), 'entity.webform.canonical', ['webform' => $webform->id()]));
          break;

        case 'webform_user_submission':
          /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
          $webform_submission = $route_match->getParameter('webform_submission');
          $webform = $webform_submission->getWebform();

          $breadcrumb = new Breadcrumb();
          $breadcrumb->addLink(Link::createFromRoute($webform->label(), 'entity.webform.canonical', ['webform' => $webform->id()]));
          $breadcrumb->addLink(Link::createFromRoute($this->t('Submissions'), 'entity.webform.user.submissions', ['webform' => $webform->id()]));
          break;

      }
    }

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
