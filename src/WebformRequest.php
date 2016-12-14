<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles webform requests.
 */
class WebformRequest implements WebformRequestInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a WebformSubmissionExporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->request = $request_stack->getCurrentRequest();
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSourceEntity($ignored_types = NULL) {
    // See if source entity is being set via query string parameters.
    if ($source_entity = $this->getCurrentSourceEntityFromQuery()) {
      return $source_entity;
    }

    $entity_types = $this->entityTypeRepository->getEntityTypeLabels();
    if ($ignored_types) {
      if (is_array($ignored_types)) {
        $entity_types = array_diff_key($entity_types, array_flip($ignored_types));
      }
      else {
        unset($entity_types[$ignored_types]);
      }
    }
    foreach ($entity_types as $entity_type => $entity_label) {
      $entity = $this->routeMatch->getParameter($entity_type);
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebform() {
    $source_entity = self::getCurrentSourceEntity('webform');
    if ($source_entity && method_exists($source_entity, 'hasField') && $source_entity->hasField('webform')) {
      return $source_entity->webform->entity;
    }
    else {
      return $this->routeMatch->getParameter('webform');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformEntities() {
    $webform = $this->getCurrentWebform();
    $source_entity = $this->getCurrentSourceEntity('webform');
    return [$webform, $source_entity];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmissionEntities() {
    $webform_submission = $this->routeMatch->getParameter('webform_submission');
    $source_entity = $this->getCurrentSourceEntity('webform_submission');
    return [$webform_submission, $source_entity];
  }

  /****************************************************************************/
  // Routing helpers
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getRouteName(EntityInterface $webform_entity, EntityInterface $source_entity = NULL, $route_name) {
    return $this->getBaseRouteName($webform_entity, $source_entity) . '.' . $route_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if (self::isValidSourceEntity($webform_entity, $source_entity)) {
      if ($webform_entity instanceof WebformSubmissionInterface) {
        return [
          'webform_submission' => $webform_entity->id(),
          $source_entity->getEntityTypeId() => $source_entity->id(),
        ];
      }
      else {
        return [$source_entity->getEntityTypeId() => $source_entity->id()];
      }
    }
    elseif ($webform_entity instanceof WebformSubmissionInterface) {
      return [
        'webform_submission' => $webform_entity->id(),
        'webform' => $webform_entity->getWebform()->id(),
      ];
    }
    else {
      return [$webform_entity->getEntityTypeId() => $webform_entity->id()];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteName(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if ($webform_entity instanceof WebformSubmissionInterface) {
      $webform = $webform_entity->getWebform();
    }
    elseif ($webform_entity instanceof WebformInterface) {
      $webform = $webform_entity;
    }
    else {
      throw new \InvalidArgumentException('Webform entity');
    }

    if (self::isValidSourceEntity($webform, $source_entity)) {
      return 'entity.' . $source_entity->getEntityTypeId();
    }
    else {
      return 'entity';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValidSourceEntity(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if ($webform_entity instanceof WebformSubmissionInterface) {
      $webform = $webform_entity->getWebform();
    }
    elseif ($webform_entity instanceof WebformInterface) {
      $webform = $webform_entity;
    }
    else {
      throw new \InvalidArgumentException('Webform entity');
    }

    if ($source_entity
      && method_exists($source_entity, 'hasField')
      && $source_entity->hasField('webform')
      && $source_entity->webform->target_id == $webform->id()
    ) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get webform submission source entity from query string.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   A source entity.
   */
  protected function getCurrentSourceEntityFromQuery() {
    // Get and check for webform.
    $webform = $this->routeMatch->getParameter('webform');
    if (!$webform) {
      return NULL;
    }

    // Get and check source entity type.
    $source_entity_type = $this->request->query->get('source_entity_type');
    if (!$source_entity_type || !$this->entityTypeManager->hasDefinition($source_entity_type)) {
      return NULL;
    }

    // Get and check source entity id.
    $source_entity_id = $this->request->query->get('source_entity_id');
    if (!$source_entity_id) {
      return NULL;
    }

    // Get and check source entity.
    $source_entity = $this->entityTypeManager->getStorage($source_entity_type)->load($source_entity_id);
    if (!$source_entity) {
      return NULL;
    }

    // Check source entity access.
    if (!$source_entity->access('view')) {
      return NULL;
    }

    // Check that the webform is referenced by the source entity.
    if (!$webform->getSetting('form_prepopulate_source_entity')) {
      // Get source entity's webform field.
      $webform_field_name = $this->getSourceEntityWebformFieldName($source_entity);
      if (!$webform_field_name) {
        return NULL;
      }

      // Check that source entity's reference webform is the current YAML
      // webform.
      if ($source_entity->$webform_field_name->target_id != $webform->id()) {
        return NULL;
      }
    }

    return $source_entity;
  }

  /**
   * Get the source entity's webform field name.
   *
   * @param EntityInterface $source_entity
   *   A webform submission's source entity.
   *
   * @return string
   *   The name of the webform field, or an empty string.
   */
  protected function getSourceEntityWebformFieldName(EntityInterface $source_entity) {
    if ($source_entity instanceof ContentEntityInterface) {
      $fields = $source_entity->getFieldDefinitions();
      foreach ($fields as $field_name => $field_definition) {
        if ($field_definition->getType() == 'webform') {
          return $field_name;
        }
      }
    }
    return '';
  }

}
