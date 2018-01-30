<?php

namespace Drupal\webform;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for webform submissions.
 */
class WebformSubmissionViewBuilder extends EntityViewBuilder implements WebformSubmissionViewBuilderInterface {

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestManager;

  /**
   * The webform element manager service.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformSubmissionViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, WebformRequestInterface $webform_request, WebformElementManagerInterface $element_manager) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->requestManager = $webform_request;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    foreach ($entities as $id => $webform_submission) {
      $webform = $webform_submission->getWebform();

      if ($view_mode == 'preview') {
        $options = [
          'excluded_elements' => $webform->getSetting('preview_excluded_elements'),
          'exclude_empty' => $webform->getSetting('preview_exclude_empty'),
        ];
      }
      else {
        $options = [
          'excluded_elements' => $webform->getSetting('excluded_elements'),
          'exclude_empty' => $webform->getSetting('exclude_empty'),
        ];
      }

      switch ($view_mode) {
        case 'yaml':
          $data = $webform_submission->toArray(TRUE, TRUE);
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => WebformYaml::tidy(Yaml::encode($data)),
            '#type' => 'yaml',
          ];
          break;

        case 'text':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => $this->buildElements($elements, $webform_submission, $options, 'text'),
          ];
          break;

        case 'table':
          $elements = $webform->getElementsInitializedFlattenedAndHasValue();
          $build[$id]['data'] = $this->buildTable($elements, $webform_submission, $options);
          break;

        default:
        case 'html':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = $this->buildElements($elements, $webform_submission, $options);
          break;
      }
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements(array $elements, WebformSubmissionInterface $webform_submission, array $options = [], $format = 'html') {
    $build_method = 'build' . ucfirst($format);
    $build = [];

    foreach ($elements as $key => $element) {
      if (!is_array($element) || Element::property($key) || !$this->isVisibleElement($element, $options) || isset($options['excluded_elements'][$key])) {
        continue;
      }

      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      // Check element view access.
      if (empty($options['ignore_access']) && !$webform_element->checkAccessRules('view', $element)) {
        continue;
      }

      if ($build_element = $webform_element->$build_method($element, $webform_submission, $options)) {
        $build[$key] = $build_element;
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTable(array $elements, WebformSubmissionInterface $webform_submission, array $options = []) {
    $rows = [];
    foreach ($elements as $key => $element) {
      if (isset($options['excluded_elements'][$key])) {
        continue;
      }

      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      // Check element view access.
      if (!$webform_element->checkAccessRules('view', $element)) {
        continue;
      }

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';
      $html = $webform_element->formatHtml($element, $webform_submission, $options);
      $rows[] = [
        ['header' => TRUE, 'data' => $title],
        ['data' => (is_string($html)) ? ['#markup' => $html] : $html],
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-submission__table'],
      ],
    ];
  }

  /**
   * Determines if an element is visible.
   *
   * Copied from: \Drupal\Core\Render\Element::isVisibleElement
   * but does not hide hidden or value elements.
   *
   * @param array $element
   *   The element to check for visibility.
   * @param array $options
   *   - excluded_elements: An array of elements to be excluded.
   *   - ignore_access: Flag to ignore private and/or access controls and always
   *     display the element.
   *   - email: Format element to be send via email.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   */
  protected function isVisibleElement(array $element, array $options) {
    if (!empty($options['ignore_access'])) {
      return TRUE;
    }
    return (!isset($element['#access']) || (($element['#access'] instanceof AccessResultInterface && $element['#access']->isAllowed()) || ($element['#access'] === TRUE)));
  }

}
