<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route responses for webform.
 */
class WebformController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a new WebformSubmissionController object.
   *
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(WebformRequestInterface $request_handler) {
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.request')
    );
  }

  /**
   * Returns a webform to add a new submission to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform this submission will be added to.
   *
   * @return array
   *   The webform submission webform.
   */
  public function addForm(Request $request, WebformInterface $webform) {
    return $webform->getSubmissionForm();
  }

  /**
   * Returns a webform's CSS.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function css(Request $request, WebformInterface $webform) {
    return new Response($webform->getCss(), 200, ['Content-Type' => 'text/css']);
  }

  /**
   * Returns a webform's JavaScript.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function javascript(Request $request, WebformInterface $webform) {
    return new Response($webform->getJavaScript(), 200, ['Content-Type' => 'text/javascript']);
  }

  /**
   * Returns a webform confirmation page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   *
   * @return array
   *   A render array representing a webform confirmation page
   */
  public function confirmation(Request $request, WebformInterface $webform = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    if (!$webform) {
      list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    }
    else {
      $source_entity = $this->requestHandler->getCurrentSourceEntity('webform');
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = NULL;

    if ($token = $request->get('token')) {
      /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
      $webform_submission_storage = $this->entityTypeManager()->getStorage('webform_submission');
      if ($entities = $webform_submission_storage->loadByProperties(['token' => $token])) {
        $webform_submission = reset($entities);
      }
    }

    return [
      '#title' => ($source_entity) ? $source_entity->label() : $webform->label(),
      '#theme' => 'webform_confirmation',
      '#webform' => $webform,
      '#source_entity' => $source_entity,
      '#webform_submission' => $webform_submission,
    ];
  }

  /**
   * Returns a webform filter webform autocomplete matches.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param bool $templates
   *   If TRUE, limit autocomplete matches to webform templates.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function autocomplete(Request $request, $templates = FALSE) {
    $q = $request->query->get('q');

    $webform_storage = $this->entityTypeManager()->getStorage('webform');

    $query = $webform_storage->getQuery()
      ->condition('title', $q, 'CONTAINS')
      ->range(0, 10)
      ->sort('title');

    // Limit query to templates.
    if ($templates) {
      $query->condition('template', TRUE);
    }
    elseif ($this->moduleHandler()->moduleExists('webform_templates')) {
      // Filter out templates if the webform_template.module is enabled.
      $query->condition('template', FALSE);
    }

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      return new JsonResponse([]);
    }
    $webforms = $webform_storage->loadMultiple($entity_ids);

    $matches = [];
    foreach ($webforms as $webform) {
      if ($webform->access('view')) {
        $value = new FormattableMarkup('@label (@id)', ['@label' => $webform->label(), '@id' => $webform->id()]);
        $matches[] = ['value' => $value, 'label' => $value];
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   *
   * @return string
   *   The webform label as a render array.
   */
  public function title(WebformInterface $webform = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    if (!$webform) {
      list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    }
    else {
      $source_entity = $this->requestHandler->getCurrentSourceEntity('webform');
    }
    return ($source_entity) ? $source_entity->label() : $webform->label();
  }

}
