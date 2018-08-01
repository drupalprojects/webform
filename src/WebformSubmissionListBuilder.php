<?php

namespace Drupal\webform;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\webform\Controller\WebformSubmissionController;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class WebformSubmissionListBuilder extends EntityListBuilder {

  /**
   * Submission state starred.
   */
  const STATE_STARRED = 'starred';

  /**
   * Submission state unstarred.
   */
  const STATE_UNSTARRED = 'unstarred';

  /**
   * Submission state locked.
   */
  const STATE_LOCKED = 'locked';

  /**
   * Submission state unlocked.
   */
  const STATE_UNLOCKED = 'unlocked';

  /**
   * Submission state completed.
   */
  const STATE_COMPLETED = 'completed';

  /**
   * Submission state draft.
   */
  const STATE_DRAFT = 'draft';

  /**
   * The route match.
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestManager;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The entity that a webform is attached to. Currently only applies to nodes.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The columns being displayed.
   *
   * @var array
   */
  protected $columns;

  /**
   * The table's header.
   *
   * @var array
   */
  protected $header;

  /**
   * The table's header and element format settings.
   *
   * @var array
   */
  protected $format = [
    'header_format' => 'label',
    'element_format' => 'value',
  ];

  /**
   * The submission link type. (canonical, table, or edit)
   *
   * @var array
   */
  protected $linkType = 'canonical';

  /**
   * The webform elements.
   *
   * @var array
   */
  protected $elements;

  /**
   * Search keys.
   *
   * @var string
   */
  protected $keys;

  /**
   * Sort by.
   *
   * @var string
   */
  protected $sort;

  /**
   * Sort direction.
   *
   * @var string
   */
  protected $direction;

  /**
   * Total number of submissions.
   *
   * @var int
   */
  protected $total;

  /**
   * Search state.
   *
   * @var string
   */
  protected $state;

  /**
   * Track if table can be customized.
   *
   * @var bool
   */
  protected $customize;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.message_manager')
    );
  }

  /**
   * Constructs a new WebformSubmissionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, RequestStack $request_stack, AccountInterface $current_user, WebformRequestInterface $webform_request, WebformElementManagerInterface $element_manager, WebformMessageManagerInterface $message_manager) {
    parent::__construct($entity_type, $storage);
    $this->routeMatch = $route_match;
    $this->request = $request_stack->getCurrentRequest();
    $this->currentUser = $current_user;
    $this->requestManager = $webform_request;
    $this->elementManager = $element_manager;
    $this->messageManager = $message_manager;

    $this->keys = $this->request->query->get('search');
    $this->state = $this->request->query->get('state');

    list($this->webform, $this->sourceEntity) = $this->requestManager->getWebformEntities();

    $this->messageManager->setWebform($this->webform);
    $this->messageManager->setSourceEntity($this->sourceEntity);

    /** @var WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->getStorage();

    $route_name = $this->routeMatch->getRouteName();
    $base_route_name = ($this->webform) ? $this->requestManager->getBaseRouteName($this->webform, $this->sourceEntity) : '';

    // Set account and state based on the current route.
    switch ($route_name) {
      case "webform.user.submissions":
        $this->account = $this->currentUser;
        break;

      case "$base_route_name.webform.user.submissions":
        $this->account = $this->currentUser;
        $this->state = self::STATE_COMPLETED;
        break;

      case "$base_route_name.webform.user.drafts":
        $this->account = $this->currentUser;
        $this->state = self::STATE_DRAFT;
        break;

      default:
        $this->account = NULL;
        break;
    }

    // Set default display settings.
    $this->direction = 'desc';
    $this->limit = 20;
    $this->customize = FALSE;
    $this->sort = 'created';
    $this->total = $this->getTotal($this->keys, $this->state);

    switch ($route_name) {
      // Display webform submissions which includes properties and elements.
      // @see /admin/structure/webform/manage/{webform}/results/submissions
      // @see /node/{node}/webform/results/submissions
      case "$base_route_name.webform.results_submissions":
        $this->columns = $webform_submission_storage->getCustomColumns($this->webform, $this->sourceEntity, $this->account, TRUE);
        $this->sort = $webform_submission_storage->getCustomSetting('sort', 'created', $this->webform, $this->sourceEntity);
        $this->direction = $webform_submission_storage->getCustomSetting('direction', 'desc', $this->webform, $this->sourceEntity);
        $this->limit = $webform_submission_storage->getCustomSetting('limit', 20, $this->webform, $this->sourceEntity);
        $this->format = $webform_submission_storage->getCustomSetting('format', $this->format, $this->webform, $this->sourceEntity);
        $this->linkType = $webform_submission_storage->getCustomSetting('link_type', $this->linkType, $this->webform, $this->sourceEntity);
        $this->customize = $this->webform->access('update');
        if ($this->format['element_format'] == 'raw') {
          foreach ($this->columns as &$column) {
            $column['format'] = 'raw';
            if (isset($column['element'])) {
              $column['element']['#format'] = 'raw';
            }
          }
        }
        break;

      // Display all submissions.
      // @see /admin/structure/webform/submissions/manage
      case 'entity.webform_submission.collection':
        // Display only submission properties.
        // @see /admin/structure/webform/submissions/manage
        $this->columns = $webform_submission_storage->getSubmissionsColumns();
        break;

      // Display user's submissions.
      // @see /user/{user}/submissions
      case 'webform.user.submissions':
        $this->columns = $webform_submission_storage->getUsersSubmissionsColumns();
        break;

      // Display user's submissions.
      // @see /webform/{webform}/submissions
      // @see /webform/{webform}/drafts
      // @see /admin/structure/webform/manage/{webform}/results/user
      // @see /node/{node}/webform/submissions
      // @see /node/{node}/webform/drafts
      // @see /node/{node}/webform/results/submissions
      case "$base_route_name.webform.user.drafts":
      case "$base_route_name.webform.user.submissions":
      case "$base_route_name.webform.results_user":
      default:
        $this->columns = $webform_submission_storage->getUserColumns($this->webform, $this->sourceEntity, $this->account, TRUE);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Set user specific page title.
    if ($this->webform && $this->account) {
      $t_args = [
        '%webform' => $this->webform->label(),
        '%user' => $this->account->getDisplayName(),
      ];
      if ($this->state == self::STATE_DRAFT) {
        $build['#title'] = $this->t('Drafts for %webform for %user', $t_args);
      }
      else {
        $build['#title'] = $this->t('Submissions to %webform for %user', $t_args);
      }
    }
    elseif ($this->account) {
      $build['#title'] = $this->account->getDisplayName();
    }

    // Display warning when the webform has a submission but saving of results.
    // are disabled.
    if ($this->webform && $this->webform->getSetting('results_disabled')) {
      $this->messageManager->display(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'warning');
    }

    // Filter form.
    if (empty($this->account)) {
      $build['filter_form'] = $this->buildFilterForm();
    }

    // Customize buttons.
    if ($this->customize) {
      $build['custom_top'] = $this->buildCustomizeButton();
    }

    // Display info.
    if ($this->total) {
      $build['info'] = $this->buildInfo();
    }

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-results-table';

    // Customize.
    // Only displayed when more than 20 submissions are being displayed.
    if ($this->customize && isset($build['table']['#rows']) && count($build['table']['#rows']) >= 20) {
      $build['custom_bottom'] = $this->buildCustomizeButton();
      if (isset($build['pager'])) {
        $build['pager']['#weight'] = 10;
      }
    }

    $build['#attached']['library'][] = 'webform/webform.admin';

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    $state_options = [
      '' => $this->t('All [@total]', ['@total' => $this->getTotal(NULL, NULL)]),
      self::STATE_STARRED => $this->t('Starred [@total]', ['@total' => $this->getTotal(NULL, self::STATE_STARRED)]),
      self::STATE_UNSTARRED => $this->t('Unstarred [@total]', ['@total' => $this->getTotal(NULL, self::STATE_UNSTARRED)]),
      self::STATE_LOCKED => $this->t('Locked [@total]', ['@total' => $this->getTotal(NULL, self::STATE_LOCKED)]),
      self::STATE_UNLOCKED => $this->t('Unlocked [@total]', ['@total' => $this->getTotal(NULL, self::STATE_UNLOCKED)]),
    ];
    // Add draft to state options.
    if (!$this->webform || $this->webform->getSetting('draft') != WebformInterface::DRAFT_NONE) {
      $state_options += [
        self::STATE_COMPLETED => $this->t('Completed [@total]', ['@total' => $this->getTotal(NULL, self::STATE_COMPLETED)]),
        self::STATE_DRAFT => $this->t('Draft [@total]', ['@total' => $this->getTotal(NULL, self::STATE_DRAFT)]),
      ];
    }
    return \Drupal::formBuilder()->getForm('\Drupal\webform\Form\WebformSubmissionFilterForm', $this->keys, $this->state, $state_options);
  }

  /**
   * Build the customize button.
   *
   * @return array
   *   A render array representing the customize button.
   */
  protected function buildCustomizeButton() {
    $route_name = $this->requestManager->getRouteName($this->webform, $this->sourceEntity, 'webform.results_submissions.custom');
    $route_parameters = $this->requestManager->getRouteParameters($this->webform, $this->sourceEntity) + ['webform' => $this->webform->id()];
    return [
      '#type' => 'link',
      '#title' => $this->t('Customize'),
      '#url' => $this->ensureDestination(Url::fromRoute($route_name, $route_parameters)),
      '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button-action', 'button--small', 'button-webform-table-setting']),
    ];
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    if ($this->account && $this->state == self::STATE_DRAFT) {
      $info = $this->formatPlural($this->total, '@total draft', '@total drafts', ['@total' => $this->total]);
    }
    else {
      $info = $this->formatPlural($this->total, '@total submission', '@total submissions', ['@total' => $this->total]);
    }
    return [
      '#markup' => $info,
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /****************************************************************************/
  // Header functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    if (isset($this->header)) {
      return $this->header;
    }

    $responsive_priorities = [
      'created' => RESPONSIVE_PRIORITY_MEDIUM,
      'langcode' => RESPONSIVE_PRIORITY_LOW,
      'remote_addr' => RESPONSIVE_PRIORITY_LOW,
      'uid' => RESPONSIVE_PRIORITY_MEDIUM,
      'webform' => RESPONSIVE_PRIORITY_LOW,
    ];

    $header = [];
    foreach ($this->columns as $column_name => $column) {
      $header[$column_name] = $this->buildHeaderColumn($column);

      // Apply custom sorting to header.
      if ($column_name === $this->sort) {
        $header[$column_name]['sort'] = $this->direction;
      }

      // Apply responsive priorities to header.
      if (isset($responsive_priorities[$column_name])) {
        $header[$column_name]['class'][] = $responsive_priorities[$column_name];
      }
    }
    $this->header = $header;
    return $this->header;
  }

  /**
   * Build table header column.
   *
   * @param array $column
   *   The column.
   *
   * @return array
   *   A renderable array containing a table header column.
   *
   * @throws \Exception
   *   Throw exception if table header column is not found.
   */
  protected function buildHeaderColumn(array $column) {
    $name = $column['name'];
    if ($this->format['header_format'] == 'key') {
      $title = isset($column['key']) ? $column['key'] : $column['name'];
    }
    else {
      $title = $column['title'];
    }

    switch ($name) {
      case 'notes':
      case 'sticky':
      case 'locked':
        return [
          'data' => new FormattableMarkup('<span class="webform-icon webform-icon-@name webform-icon-@name--link"></span><span class="visually-hidden">@title</span> ', ['@name' => $name, '@title' => $title]),
          'class' => ['webform-results-table__icon'],
          'field' => $name,
          'specifier' => $name,
        ];

      default:
        if (isset($column['sort']) && $column['sort'] === FALSE) {
          return ['data' => $title];
        }
        else {
          return [
            'data' => $title,
            'field' => $name,
            'specifier' => $name,
          ];
        }
    }
  }

  /****************************************************************************/
  // Row functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $url = $this->requestManager->getUrl($entity, $this->sourceEntity, $this->getSubmissionRouteName());
    $row = [
      'data' => [],
      'data-webform-href' => $url->toString(),
    ];
    foreach ($this->columns as $column_name => $column) {
      $row['data'][$column_name] = $this->buildRowColumn($column, $entity);
    }

    return $row;
  }

  /**
   * Build row column.
   *
   * @param array $column
   *   Column settings.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform submission.
   *
   * @return array|mixed
   *   The row column value or renderable array.
   *
   * @throws \Exception
   *   Throw exception if table row column is not found.
   */
  public function buildRowColumn(array $column, EntityInterface $entity) {
    /** @var $entity \Drupal\webform\WebformSubmissionInterface */

    $is_raw = ($column['format'] == 'raw');
    $name = $column['name'];

    switch ($name) {
      case 'created':
      case 'completed':
      case 'changed':
        return ($is_raw) ? $entity->{$name}->value : $entity->{$name}->value ? \Drupal::service('date.formatter')->format($entity->{$name}->value) : '';

      case 'entity':
        $source_entity = $entity->getSourceEntity();
        if (!$source_entity) {
          return '';
        }
        return ($is_raw) ? $source_entity->getEntityTypeId . ':' . $source_entity->id() : ($source_entity->hasLinkTemplate('canonical') ? $source_entity->toLink() : '');

      case 'langcode':
        $langcode = $entity->langcode->value;
        if (!$langcode) {
          return '';
        }
        if ($is_raw) {
          return $langcode;
        }
        else {
          $language = \Drupal::languageManager()->getLanguage($langcode);
          return ($language) ? $language->getName() : $langcode;
        }

      case 'notes':
        $notes_url = $this->ensureDestination($this->requestManager->getUrl($entity, $entity->getSourceEntity(), 'webform_submission.notes_form'));
        $state = $entity->get('notes')->value ? 'on' : 'off';
        $t_args = ['@label' => $entity->label()];
        $label = $entity->get('notes')->value ? $this->t('Edit @label notes', $t_args) : $this->t('Add notes to @label', $t_args);
        return [
          'data' => [
            '#type' => 'link',
            '#title' => new FormattableMarkup('<span class="webform-icon webform-icon-notes webform-icon-notes--@state"></span><span class="visually-hidden">@label</span>', ['@state' => $state, '@label' => $label]),
            '#url' => $notes_url,
            '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
          ],
          'class' => ['webform-results-table__icon'],
        ];

      case 'operations':
        return ['data' => $this->buildOperations($entity), 'class' => ['webform-dropbutton-wrapper']];

      case 'remote_addr':
        return $entity->getRemoteAddr();

      case 'sid':
        return $entity->id();

      case 'serial':
      case 'label':
        // Note: Use the submission's token URL which points to the
        // submission's source URL with a secure token.
        // @see \Drupal\webform\Entity\WebformSubmission::getTokenUrl
        if ($entity->isDraft()) {
          $link_url = $entity->getTokenUrl();
        }
        else {
          $link_url = $this->requestManager->getUrl($entity, $entity->getSourceEntity(), $this->getSubmissionRouteName());
        }
        if ($name == 'serial') {
          $link_text = $entity->serial();
        }
        else {
          $link_text = $entity->label();
        }
        $link = Link::fromTextAndUrl($link_text, $link_url)->toRenderable();
        if ($name == 'serial') {
          $link['#attributes']['title'] = $entity->label();
          $link['#attributes']['aria-label'] = $entity->label();
        }
        if ($entity->isDraft()) {
          $link['#suffix'] = ' (' . $this->t('draft') . ')';
        }
        return ['data' => $link];

      case 'in_draft':
        return ($entity->isDraft()) ? $this->t('Yes') : $this->t('No');

      case 'sticky':
        // @see \Drupal\webform\Controller\WebformSubmissionController::sticky
        $route_name = 'entity.webform_submission.sticky_toggle';
        $route_parameters = ['webform' => $entity->getWebform()->id(), 'webform_submission' => $entity->id()];
        return [
          'data' => [
            '#type' => 'link',
            '#title' => WebformSubmissionController::buildSticky($entity),
            '#url' => Url::fromRoute($route_name, $route_parameters),
            '#attributes' => [
              'id' => 'webform-submission-' . $entity->id() . '-sticky',
              'class' => ['use-ajax'],
            ],
          ],
          'class' => ['webform-results-table__icon'],
        ];

      case 'locked':
        // @see \Drupal\webform\Controller\WebformSubmissionController::locked
        $route_name = 'entity.webform_submission.locked_toggle';
        $route_parameters = ['webform' => $entity->getWebform()->id(), 'webform_submission' => $entity->id()];
        return [
          'data' => [
            '#type' => 'link',
            '#title' => WebformSubmissionController::buildLocked($entity),
            '#url' => Url::fromRoute($route_name, $route_parameters),
            '#attributes' => [
              'id' => 'webform-submission-' . $entity->id() . '-locked',
              'class' => ['use-ajax'],
            ],
          ],
          'class' => ['webform-results-table__icon'],
        ];

      case 'uid':
        return ($is_raw) ? $entity->getOwner()->id() : ($entity->getOwner()->getAccountName() ?: t('Anonymous'));

      case 'uuid':
        return $entity->uuid();

      case 'webform_id':
        return ($is_raw) ? $entity->getWebform()->id() : $entity->getWebform()->toLink();

      default:
        if (strpos($name, 'element__') === 0) {
          $element = $column['element'];
          $options = $column;

          /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
          $element_plugin = $column['plugin'];
          $html = $element_plugin->formatTableColumn($element, $entity, $options);
          return (is_array($html)) ? ['data' => $html] : $html;
        }
        else {
          return '';
        }
    }
  }

  /****************************************************************************/
  // Operations.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $entity->getWebform();

    $operations = [];

    if ($this->account) {
      if ($entity->access('update')) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform.user.submission.edit'),
        ];
      }

      if ($entity->access('view')) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'weight' => 20,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform.user.submission'),
        ];
      }

      if ($entity->access('create') && $webform->getSetting('submission_user_duplicate')) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'weight' => 23,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform.user.submission.duplicate'),
        ];
      }

      if ($entity->access('delete')) {
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'weight' => 100,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform.user.submission.delete'),
        ];
      }
    }
    else {
      if ($entity->access('update')) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.edit_form'),
        ];
      }

      if ($entity->access('view')) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'weight' => 20,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.canonical'),
        ];
      }

      if ($entity->access('notes')) {
        $operations['notes'] = [
          'title' => $this->t('Notes'),
          'weight' => 21,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.notes_form'),
        ];
      }

      if ($webform->access('submission_update_any') && $webform->hasMessageHandler()) {
        $operations['resend'] = [
          'title' => $this->t('Resend'),
          'weight' => 22,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.resend_form'),
        ];
      }
      if ($webform->access('submission_update_any')) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'weight' => 23,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.duplicate_form'),
        ];
      }

      if ($entity->access('delete')) {
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'weight' => 100,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.delete_form'),
        ];
      }

      if ($entity->access('view_any') && $this->currentUser->hasPermission('access webform submission log')) {
        $operations['log'] = [
          'title' => $this->t('Log'),
          'weight' => 100,
          'url' => $this->requestManager->getUrl($entity, $this->sourceEntity, 'webform_submission.log'),
        ];
      }
    }

    // Add destination to all operation links.
    foreach ($operations as &$operation) {
      $this->ensureDestination($operation['url']);
    }

    return $operations;
  }

  /****************************************************************************/
  // Route functions.
  /****************************************************************************/

  /**
   * Get submission route name based on the current route.
   *
   * @return string
   *   The submission route name which can be either 'webform.user.submission'
   *   or 'webform_submission.canonical.
   */
  protected function getSubmissionRouteName() {
    return (strpos($this->routeMatch->getRouteName(), 'webform.user.submissions') !== FALSE) ? 'webform.user.submission' : 'webform_submission.' . $this->linkType;
  }

  /**
   * Get base route name for the webform or webform source entity.
   *
   * @return string
   *   The base route name for webform or webform source entity.
   */
  protected function getBaseRouteName() {
    return $this->requestManager->getBaseRouteName($this->webform, $this->sourceEntity);
  }

  /**
   * Get route parameters for the webform or webform source entity.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   Route parameters for the webform or webform source entity.
   */
  protected function getRouteParameters(WebformSubmissionInterface $webform_submission) {
    $route_parameters = ['webform_submission' => $webform_submission->id()];
    if ($this->sourceEntity) {
      $route_parameters[$this->sourceEntity->getEntityTypeId()] = $this->sourceEntity->id();
    }
    return $route_parameters;
  }

  /****************************************************************************/
  // Query functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getQuery($this->keys, $this->state);
    $query->pager($this->limit);

    $header = $this->buildHeader();
    $order = tablesort_get_order($header);
    $direction = tablesort_get_sort($header);

    // If query is order(ed) by 'element__*' we need to build a custom table
    // sort using hook_query_alter().
    // @see: webform_query_alter()
    if ($order && strpos($order['sql'], 'element__') === 0) {
      $name = $order['sql'];
      $column = $this->columns[$name];
      $query->addMetaData('webform_submission_element_name', $column['key']);
      $query->addMetaData('webform_submission_element_property_name', $column['property_name']);
      $query->addMetaData('webform_submission_element_direction', $direction);
      $result = $query->execute();
      // Must manually initialize the pager because the DISTINCT clause in the
      // query is breaking the row counting.
      // @see webform_query_alter()
      pager_default_initialize($this->total, $this->limit);
      return $result;
    }
    else {
      $order = $this->request->query->get('order', '');
      if ($order) {
        $query->tableSort($header);
      }
      else {
        // If no order is specified, make sure the first column is sortable,
        // else default sorting to the sid.
        // @see \Drupal\Core\Entity\Query\QueryBase::tableSort
        // @see tablesort_get_order()
        $default = reset($header);
        if (isset($default['specified'])) {
          $query->tableSort($header);
        }
        else {
          $query->sort('sid', 'DESC');
        }
      }
      return $query->execute();
    }
  }

  /**
   * Get the total number of submissions.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $state
   *   (optional) Submission state.
   *
   * @return int
   *   The total number of submissions.
   */
  protected function getTotal($keys = '', $state = '') {
    return $this->getQuery($keys, $state)
      ->count()
      ->execute();
  }

  /**
   * Get the base entity query filtered by webform and search.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $state
   *   (optional) Submission state.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $state = '') {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = $this->getStorage();
    $query = $submission_storage->getQuery();
    $submission_storage->addQueryConditions($query, $this->webform, $this->sourceEntity, $this->account);

    // Filter by key(word).
    if ($keys) {
      // Search values.
      $sub_query = Database::getConnection()->select('webform_submission_data', 'sd')
        ->fields('sd', ['sid'])
        ->condition('value', '%' . $keys . '%', 'LIKE');
      $submission_storage->addQueryConditions($sub_query, $this->webform);

      // Search UUID and Notes.
      $or_condition = $query->orConditionGroup();
      $or_condition->condition('notes', '%' . $keys . '%', 'LIKE');
      // Only search UUID if keys is alphanumeric with dashes.
      // @see Issue #2978420: Error SQL with accent mark submissions filter.
      if (preg_match('/^[0-9a-z-]+$/', $keys)) {
        $or_condition->condition('uuid', $keys);
      }
      $query->condition(
        $query->orConditionGroup()
          ->condition('sid', $sub_query, 'IN')
          ->condition($or_condition)
      );
    }

    // Filter by (submission) state.
    switch ($state) {
      case self::STATE_STARRED:
        $query->condition('sticky', 1);
        break;

      case self::STATE_UNSTARRED:
        $query->condition('sticky', 0);
        break;

      case self::STATE_LOCKED:
        $query->condition('locked', 1);
        break;

      case self::STATE_UNLOCKED:
        $query->condition('locked', 0);
        break;

      case self::STATE_DRAFT:
        $query->condition('in_draft', 1);
        break;

      case self::STATE_COMPLETED:
        $query->condition('in_draft', 0);
        break;
    }

    return $query;
  }

}
