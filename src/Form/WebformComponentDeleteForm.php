<?php

/**
 * @file
 * Contains \Drupal\book\Form\WebformComponentDeleteForm.
 */

namespace Drupal\webform\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\ComponentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove form for book module.
 */
class WebformComponentDeleteForm extends ConfirmFormBase {

  /**
   * The component manager service.
   *
   * @var \Drupal\webform\ComponentManager
   */
  protected $componentManager;

  /**
   * The node the webform belongs to.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * @todo
   */
  protected $component;

  /**
   * Constructs a WebformComponentDeleteForm object.
   *
   * @param ComponentManager $component_manager
   *   The component manager service.
   */
  public function __construct(ComponentManager $component_manager) {
    $this->componentManager = $component_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.component')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_component_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $component = NULL) {
    $this->node = $node;
    // @todo $component should be a fully loaded object, need to make our own ParamConverterInterface.
    $this->component = isset($node->webform['components'][$component]) ? $node->webform['components'][$component] : FALSE;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $component_type = $this->node->webform['components'][$this->component['cid']]['type'];
    if (webform_component_feature($component_type, 'group')) {
      return $this->t('This will immediately delete the %name @type component and all nested components within %name from the %webform webform. This cannot be undone.', [
        '%name' => $this->node->webform['components'][$this->component['cid']]['name'],
        '@type' => webform_component_property($component_type, 'label'),
        '%webform' => $this->node->getTitle()
      ]);
    }
    else {
      return $this->t('This will immediately delete the %name component from the %webform webform. This cannot be undone.', [
        '%name' => $this->node->webform['components'][$this->component['cid']]['name'],
        '%webform' => $this->node->getTitle()
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $component_type = $this->node->webform['components'][$this->component['cid']]['type'];
    if (webform_component_feature($component_type, 'group')) {
      return $this->t('Delete the %name fieldset?', ['%name' => $this->node->webform['components'][$this->component['cid']]['name']]);
    }
    else {
      return $this->t('Delete the %name component?', ['%name' => $this->node->webform['components'][$this->component['cid']]['name']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->node->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the component.
    $node = $this->node;
    $component = $this->component;
    // @todo This should be $this->componentManager->deleteComponent($this->node->id(), $this->component['cid']);
    webform_component_delete($node, $component);
    drupal_set_message($this->t('Component %name deleted.', ['%name' => $component['name']]));

    // Check if this webform still contains any information.
    unset($node->webform['components'][$component['cid']]);
    webform_check_record($node);

    // Since Webform components have been updated but the node itself has not
    // been saved, it is necessary to explicitly clear the cache to make sure
    // the updated webform is visible to anonymous users. This resets the page
    // and block caches (only);
    // @todo How should this be done in D8?
    Cache::invalidateTags(['rendered']);

    // Refresh the entity cache, should it be cached in persistent storage.
    // @todo Is this needed in D8?
    //entity_get_controller('node')->resetCache([$node->id()]);

    // @todo This should be the webform components route.
    $form_state->setRedirect('entity.node.webform', ['node' => $node->id()]);
  }

}
