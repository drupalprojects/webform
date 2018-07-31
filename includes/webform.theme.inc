<?php

/**
 * @file
 * Theme hooks, preprocessor, and suggestions.
 */

use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Template\Attribute;
use Drupal\webform\Utility\WebformAccessibilityHelper;
use Drupal\webform\Utility\WebformElementHelper;

/******************************************************************************/
// Theme hooks.
/******************************************************************************/

/**
 * Implements hook_theme().
 */
function webform_theme() {
  $info = [
    'webform_help' => [
      'variables' => ['info' => []],
    ],
    'webform_help_video_youtube' => [
      'variables' => ['youtube_id' => NULL, 'autoplay' => TRUE],
    ],

    'webform_contribute' => [
      'variables' => [
        'account' => [],
        'membership' => [],
        'contribution' => [],
      ],
    ],

    'webform' => [
      'render element' => 'element',
    ],
    'webform_actions' => [
      'render element' => 'element',
    ],
    'webform_handler_action_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],
    'webform_handler_debug_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],
    'webform_handler_email_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],
    'webform_handler_remote_post_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],
    'webform_handler_settings_summary' => [
      'variables' => ['settings' => NULL, 'handler' => NULL],
    ],

    'webform_confirmation' => [
      'variables' => ['webform' => NULL, 'source_entity' => NULL, 'webform_submission' => NULL],
    ],

    'webform_required' => [
      'variables' => ['label' => NULL],
    ],

    'webform_submission' => [
      'render element' => 'elements',
    ],

    'webform_submission_form' => [
      'render element' => 'form',
    ],

    'webform_submission_navigation' => [
      'variables' => ['webform_submission' => NULL],
    ],
    'webform_submission_information' => [
      'variables' => ['webform_submission' => NULL, 'source_entity' => NULL, 'open' => TRUE],
    ],

    'webform_element_base_html' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],
    'webform_element_base_text' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],

    'webform_container_base_html' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],
    'webform_container_base_text' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => []],
    ],

    'webform_element_help' => [
      'variables' => ['help' => NULL, 'help_title' => '', 'attributes' => []],
    ],

    'webform_element_more' => [
      'variables' => ['more' => NULL, 'more_title' => '', 'attributes' => []],
    ],

    'webform_element_managed_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],
    'webform_element_audio_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],
    'webform_element_document_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],
    'webform_element_image_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL, 'style_name' => NULL, 'format' => NULL],
    ],
    'webform_element_video_file' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
    ],

    'webform_email_message_html' => [
      'variables' => ['message' => '', 'webform_submission' => NULL, 'handler' => NULL],
    ],
    'webform_email_message_text' => [
      'variables' => ['message' => '', 'webform_submission' => NULL, 'handler' => NULL],
    ],

    'webform_horizontal_rule' => [
      'render element' => 'element',
    ],
    'webform_message' => [
      'render element' => 'element',
    ],
    'webform_section' => [
      'render element' => 'element',
    ],

    'webform_composite_address' => [
      'render element' => 'element',
    ],
    'webform_composite_contact' => [
      'render element' => 'element',
    ],
    'webform_composite_location' => [
      'render element' => 'element',
    ],
    'webform_composite_link' => [
      'render element' => 'element',
    ],
    'webform_composite_name' => [
      'render element' => 'element',
    ],
    'webform_composite_telephone' => [
      'render element' => 'element',
    ],

    'webform_codemirror' => [
      'variables' => ['code' => NULL, 'type' => 'text'],
    ],

    'webform_progress' => [
      'variables' => [
        'webform' => NULL,
        'current_page' => NULL,
        'operation' => NULL,
      ],
    ],

    'webform_progress_bar' => [
      'variables' => [
        'webform' => NULL,
        'current_page' => NULL,
        'operation' => NULL,
        'max_pages' => 10,
      ],
    ],

    'webform_progress_tracker' => [
      'variables' => [
        'webform' => NULL,
        'current_page' => NULL,
        'operation' => NULL,
        'max_pages' => 10,
      ],
    ],
  ];

  // Since any rendering of a webform is going to require 'webform.theme.inc'
  // we are going to just add it to every template.
  foreach ($info as &$template) {
    $template['file'] = 'includes/webform.theme.template.inc';
  }

  return $info;
}

/**
 * Implements hook_theme_registry_alter().
 */
function webform_theme_registry_alter(&$theme_registry) {
  // Allow attributes to be defined for status messages so that #states
  // can be added to messages.
  // @see \Drupal\webform\Element\WebformMessage
  if (!isset($theme_registry['status_messages']['variables']['attributes'])) {
    $theme_registry['status_messages']['variables']['attributes'] = [];
  }
}

/******************************************************************************/
// Preprocessors.
/******************************************************************************/

/**
 * Prepares variables for single local action link templates.
 *
 * Applies custom link attributes to local actions.
 * Custom attributes are used to open Webform UI modals.
 *
 * @see template_preprocess_menu_local_action();
 * @see \Drupal\webform\WebformEntityHandlersForm
 * @see \Drupal\webform_ui\WebformUiEntityEditForm
 * @see https://www.drupal.org/node/2897396
 */
function webform_preprocess_menu_local_action(&$variables) {
  $link = $variables['element']['#link'];
  // Only need to update local actions with link attributes.
  if (!isset($link['attributes'])) {
    return;
  }

  $link += [
    'localized_options' => [],
  ];
  $link['localized_options']['attributes'] = $link['attributes'];
  $link['localized_options']['attributes']['class'][] = 'button';
  $link['localized_options']['attributes']['class'][] = 'button-action';
  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = [
    '#type' => 'link',
    '#title' => $link['title'],
    '#options' => $link['localized_options'],
    '#url' => $link['url'],
  ];
}

/**
 * Prepares variables for checkboxes templates.
 *
 * @see \Drupal\webform\Plugin\WebformElement\OptionsBase
 */
function webform_preprocess_checkboxes(&$variables) {
  if (!WebformElementHelper::isWebformElement($variables['element'])) {
    return;
  }

  _webform_preprocess_options($variables);
}

/**
 * Prepares variables for radios templates.
 *
 * @see \Drupal\webform\Plugin\WebformElement\OptionsBase
 */
function webform_preprocess_radios(&$variables) {
  if (!WebformElementHelper::isWebformElement($variables['element'])) {
    return;
  }

  _webform_preprocess_options($variables);
}

/******************************************************************************/
// Preprocess containers.
/******************************************************************************/

/**
 * Prepares variables for details element templates.
 */
function webform_preprocess_details(&$variables) {
  if (!WebformElementHelper::isWebformElement($variables['element'])) {
    return;
  }

  // Setup description, help, and more.
  _webform_preprocess_element($variables);

  $element = &$variables['element'];

  // Hide details title.
  if (isset($element['#title_display']) && $element['#title_display'] === 'invisible') {
    $variables['title'] = WebformAccessibilityHelper::buildVisuallyHidden($variables['title']);
  }

  // Remove invalid 'required' and 'aria-required' attributes from details.
  if (isset($element['#webform_key'])) {
    unset(
      $variables['attributes']['required'],
      $variables['attributes']['aria-required']
    );
  }
}

/**
 * Prepares variables for fieldset element templates.
 */
function webform_preprocess_fieldset(&$variables) {
  if (!WebformElementHelper::isWebformElement($variables['element'])) {
    return;
  }

  // Setup description, help, and more.
  _webform_preprocess_element($variables, ['legend', 'title']);

  $element = &$variables['element'];

  // Apply inline title defined by radios, checkboxes, and buttons.
  // @see \Drupal\webform\Plugin\WebformElement\OptionsBase::prepare
  if (isset($element['#_title_display'])) {
    $variables['attributes']['class'][] = 'webform-fieldset--title-inline';
  }

  // Add .js-webform-form-composite class to be used #states API.
  // @see js/webform.states.js
  if (isset($element['#type']) && in_array($element['#type'], ['checkboxes', 'radios'])) {
    $variables['attributes']['class'][] = 'js-webform-type-' . $element['#type'];
    $variables['attributes']['class'][] = 'webform-type-' . $element['#type'];
  }

  // Remove invalid 'required' and 'aria-required' attributes from fieldset.
  if (isset($element['#webform_key'])) {
    unset(
      $variables['attributes']['required'],
      $variables['attributes']['aria-required']
    );
  }
}

/******************************************************************************/
// Preprocess form element.
/******************************************************************************/

/**
 * Prepares variables for form element templates.
 */
function webform_preprocess_form_element(&$variables) {
  if (!WebformElementHelper::isWebformElement($variables['element'])) {
    return;
  }

  // Setup description, help, and more.
  _webform_preprocess_element($variables);

  $element = &$variables['element'];

  // Add #help, #help_attributes, and #_title_display to label.
  // Note: #_title_display is used to track inline titles.
  // @see \Drupal\webform\Plugin\WebformElementBase::prepare
  $variables['label'] += array_intersect_key($element, array_flip(['#help', '#help_title', '#help_attributes', '#_title_display']));
}

/**
 * Prepares variables for form label templates.
 */
function webform_preprocess_form_element_label(&$variables) {
  $element = &$variables['element'];

  // Restructure the label's title to include #help.
  _webform_preprocess_help_title($variables['title'], $element);
}

/******************************************************************************/
// Preprocess file/image elements.
/******************************************************************************/

/**
 * Prepares variables for file managed file templates.
 *
 * @see https://stackoverflow.com/questions/21842274/cross-browser-custom-styling-for-file-upload-button
 * @see template_preprocess_file_managed_file()
 */
function webform_preprocess_file_managed_file(&$variables) {
  if (!WebformElementHelper::isWebformElement($variables['element'])) {
    return;
  }

  $element = &$variables['element'];
  if (empty($element['#button'])) {
    return;
  }

  // Don't alter hidden file upload input.
  if (isset($element['upload']['#access']) && $element['upload']['#access'] === FALSE) {
    return;
  }

  // Create an unique id for the file upload input and label.
  $button_id = Html::getUniqueId($variables['element']['upload']['#id'] . '-button');

  // Create a label that is styled like an action button.
  $label = [
    '#type' => 'html_tag',
    '#tag' => 'label',
    '#value' => (isset($element['#button__title'])) ? $element['#button__title'] : (empty($element['#multiple']) ? t('Choose file') : t('Choose files')),
    '#attributes' => (isset($element['#button__attributes'])) ? $element['#button__attributes'] : [],
  ];

  // Add 'for' attribute.
  $label['#attributes']['for'] = $button_id;

  // Add default button classes.
  if (empty($label['#attributes']['class'])) {
    $label['#attributes']['class'][] = 'button';
    $label['#attributes']['class'][] = 'button-action';
  }

  // Add .webform-file-button.
  $label['#attributes']['class'][] = 'webform-file-button';

  // Make sure the label is first.
  $element = ['label' => $label] + $element;

  // Set the custom button ID for file upload input.
  $element['upload']['#attributes']['id'] = $button_id;

  // Hide the file upload.
  $element['upload']['#attributes']['class'][] = 'webform-file-button-input';

  // Attach library.
  $element['#attached']['library'][] = 'webform/webform.element.file.button';
}

/**
 * Prepares variables for file link templates.
 *
 * @see webform_file_access
 */
function webform_preprocess_file_link(&$variables) {
  /** @var \Drupal\file\FileInterface $file */
  $file = $variables['file'];
  $file = ($file instanceof File) ? $file : File::load($file->fid);

  // Remove link to temporary anonymous private file uploads.
  if ($file->isTemporary() && $file->getOwner()->isAnonymous() && strpos($file->getFileUri(), 'private://webform/') === 0) {
    $variables['link'] = $file->getFilename();
  }
}

/**
 * Prepares variables for image.
 *
 * Make sure the image src for the 'webform_image_file' src is an absolute URL.
 */
function webform_preprocess_image(&$variables) {
  global $base_url;
  if (isset($variables['attributes']['class']) && in_array('webform-image-file', (array) $variables['attributes']['class'])) {
    $variables['attributes']['src'] = $base_url . preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $variables['attributes']['src']);
  }
}

/******************************************************************************/
// Preprocess webform specific elements.
/******************************************************************************/

/**
 * Prepares variables for webform section element templates.
 */
function webform_preprocess_webform_section(&$variables) {
  // Setup description, help, and more.
  _webform_preprocess_element($variables);
}

/******************************************************************************/
// Preprocess helpers.
/******************************************************************************/

/**
 * Prepares variables for checkboxes and radios options templates.
 *
 * Below code must be called by template_preprocess_(radios|checkboxes) which
 * reset the element's 'attributes';
 */
function _webform_preprocess_options(array &$variables) {
  $element =& $variables['element'];

  $variables['attributes']['class'][] = Html::getClass('js-webform-' . $element['#type']);

  if (!empty($element['#options_display'])) {
    $variables['attributes']['class'][] = Html::getClass('webform-options-display-' . $element['#options_display']);
    $variables['#attached']['library'][] = 'webform/webform.element.options';
  }
}

/**
 * Prepares webform element description, help, and more templates.
 *
 * @see template_preprocess_form_element()
 * @see core/modules/system/templates/form-element.html.twig
 * @see template_preprocess_details()
 * @see /core/modules/system/templates/details.html.twig
 * @see template_preprocess_fieldset()
 * @see /core/modules/system/templates/fieldset.html.twig
 * @see template_preprocess_webform_section()
 * @see /webform/templates/webform-section.html.twig
 */
function _webform_preprocess_element(array &$variables, $title_parents = ['title']) {
  $element =& $variables['element'];
  $type = $element['#type'];

  // Fix details 'description' property which does not have description.content.
  // @see template_preprocess_details
  // @see Issue #2896169: Details elements have incorrect aria-describedby attributes
  if (!empty($element['#description'])) {
    $description = (is_array($element['#description'])) ? $element['#description'] : ['#markup' => $element['#description']];
    if ($type === 'details') {
      $description_attributes = [];
      if (!empty($element['#id'])) {
        $description_attributes['id'] = $element['#id'] . '--description';
      }
      $variables['description'] = [];
      $variables['description']['content'] = [
        '#type' => 'container',
        '#attributes' => new Attribute($description_attributes),
      ] + $description;
    }
    else {
      // Wrap description in a container.
      $variables['description']['content'] = [
        '#type' => 'container',
        '#attributes' => $variables['description']['attributes'],
      ] + $description;
      $variables['description']['attributes'] = new Attribute();
    }

    $variables['description']['content']['#attributes']->addClass('webform-element-description');

    // Handle invisible descriptions.
    if (isset($element['#description_display']) && $element['#description_display'] === 'invisible') {
      $variables['description']['content']['#attributes']->addClass('visually-hidden');
      $variables['description_display'] = 'after';
    }

    // Nest description content so that we can a more link
    // below the description.
    $variables['description']['content'] = [
      'description' => $variables['description']['content'],
    ];
  }

  $title =& NestedArray::getValue($variables, $title_parents);

  // Move #description to #help for webform admin routes.
  _webform_preprocess_description_help($variables);

  // Add (read) more to #description.
  _webform_preprocess_form_element_description_more($variables);

  // Add help to title (aka label).
  _webform_preprocess_help_title($title, $element);
}

/**
 * Prepares #description and #help properties for form element templates.
 */
function _webform_preprocess_description_help(array &$variables) {
  $element = &$variables['element'];
  // Move #description to #help for webform admin routes.
  if (\Drupal::config('webform.settings')->get('ui.description_help')
    && \Drupal::service('webform.request')->isWebformAdminRoute()
    && \Drupal::routeMatch()->getRouteName() != 'webform.contribute.settings'
    && !isset($element['#help'])
    && !empty($element['#title']) && (empty($element['#title_display']) || !in_array($element['#title_display'], ['attribute', 'invisible']))
    && !empty($element['#description']) && (empty($element['#description_display']) || !in_array($element['#description_display'], ['invisible']))
  ) {
    $element['#help'] = (is_array($element['#description'])) ? \Drupal::service('renderer')->render($element['#description']) : $element['#description'];

    // We must still render the description as visually hidden because the input
    // has an 'aria-describedby' attribute pointing to the description's id.
    $variables['description_display'] = 'after';
    $variables['description']['content']['description']['#attributes']->addClass('visually-hidden');

    // Remove all links from the #description since it will be .visually-hidden
    // and unreachable via tabbing.
    if (isset($variables['description']['content']['description']['#markup'])) {
      $variables['description']['content']['description']['#markup'] = strip_tags($variables['description']['content']['description']['#markup']);
    }
  }
}

/**
 * Append #help to title variable.
 */
function _webform_preprocess_help_title(&$variables, array &$element) {
  if (empty($variables) || empty($element['#help'])) {
    return;
  }

  // Default #help_title to element's #title.
  if (empty($element['#help_title']) && !empty($element['#title'])) {
    $element['#help_title'] = $element['#title'];
  }

  $variables = [
    'title' => (is_array($variables)) ? $variables : ['#markup' => $variables],
    'help' => [
      '#type' => 'webform_help',
    ] + array_intersect_key($element, array_flip(['#help', '#help_title'])),
  ];

  // Add help attributes.
  if (isset($element['#help_attributes'])) {
    $variables['help']['#attributes'] = $element['#help_attributes'];
  }

  // Get #title_display and move help before title for 'inline' titles.
  if (isset($element['#_title_display'])) {
    // #_title_display is set via WebformElementBase::prepare.
    // @see \Drupal\webform\Plugin\WebformElementBase::prepare.
    $title_display = $element['#_title_display'];
  }
  elseif (isset($element['#title_display'])) {
    $title_display = $element['#title_display'];
  }
  else {
    $title_display = NULL;
  }

  if ($title_display === 'inline') {
    $variables['title']['#weight'] = 0;
    $variables['help']['#weight'] = -1;
  }
}

/**
 * Prepares #more property for form element template.
 *
 * @see template_preprocess_form_element()
 * @see form-element.html.twig
 * @see template_preprocess_datetime_wrapper()
 * @see datetime-wrapper.html.twig
 */
function _webform_preprocess_form_element_description_more(array &$variables) {
  $element = &$variables['element'];
  if (empty($element['#more'])) {
    return;
  }

  // Make sure description is displayed.
  if (!isset($variables['description_display'])) {
    $variables['description_display'] = 'after';
  }

  // Add more element.
  $variables['description']['content']['more'] = [
    '#type' => 'webform_more',
    '#attributes' => (!empty($element['#id'])) ? ['id' => $element['#id'] . '--more'] : [],
  ] + array_intersect_key($element, array_flip(['#more', '#more_title']));
}

/******************************************************************************/
// Theme suggestions.
/******************************************************************************/

/**
 * Provides alternate named suggestions for a specific theme hook.
 *
 * @param array $variables
 *   An array of variables passed to the theme hook.
 * @param string $hook
 *   The base hook name.
 *
 * @return array
 *   An array of theme suggestions.
 */
function _webform_theme_suggestions(array $variables, $hook) {
  $suggestions = [];

  if ($hook == 'webform' && isset($variables['element']) && isset($variables['element']['#webform_id'])) {
    $suggestions[] = $hook . '__' . $variables['element']['#webform_id'];
  }
  elseif ($hook == 'webform_submission_form' && isset($variables['form']) && isset($variables['form']['#webform_id'])) {
    $suggestions[] = $hook . '__' . $variables['form']['#webform_id'];
  }
  elseif (strpos($hook, 'webform_element_base_') === 0 || strpos($hook, 'webform_container_base_') === 0) {
    $element = $variables['element'];

    if (!empty($element['#type'])) {
      $type = $element['#type'];
      $name = $element['#webform_key'];

      $suggestions[] = $hook . '__' . $type;
      $suggestions[] = $hook . '__' . $type . '__' . $name;
    }
  }
  else {
    $webform = NULL;
    $webform_submission = NULL;
    $sanitized_view_mode = NULL;
    if (isset($variables['elements']) && isset($variables['elements']['#webform_submission'])) {
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $variables['elements']['#webform_submission'];
      $webform = $webform_submission->getWebform();
      $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
    }
    elseif (isset($variables['webform_submission'])) {
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $variables['webform_submission'];
      $webform = $webform_submission->getWebform();
    }
    elseif (isset($variables['webform'])) {
      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $variables['webform'];
    }

    if ($webform) {
      $suggestions[] = $hook . '__' . $webform->id();
      if (isset($variables['handler'])) {
        /** @var \Drupal\webform\Plugin\WebformHandlerInterface $handler */
        $handler = $variables['handler'];
        $suggestions[] = $hook . '__' . $webform->id() . '__' . $handler->getPluginId();
        $suggestions[] = $hook . '__' . $webform->id() . '__' . $handler->getPluginId() . '__' . $handler->getHandlerId();
      }
      if ($sanitized_view_mode) {
        $suggestions[] = $hook . '__' . $webform->id() . '__' . $sanitized_view_mode;
        $suggestions[] = $hook . '__' . $sanitized_view_mode;
      }
    }
  }

  return $suggestions;
}

/**
 * Helper function used to generate hook_theme_suggestions_HOOK().
 */
function _webform_devel_hook_theme_suggestions_generate() {
  $theme = webform_theme();
  print '<pre>';
  foreach ($theme as $hook => $info) {
    $suggestion = FALSE;
    if ($hook == 'webform') {
      $suggestion = TRUE;
    }
    elseif (strpos($hook, 'webform_element_base_') === 0 || strpos($hook, 'webform_container_base_') === 0) {
      $suggestion = TRUE;
    }
    elseif (isset($info['variables'])
      && !array_key_exists('element', $info['variables'])
      && (array_key_exists('webform_submission', $info['variables']) || array_key_exists('webform', $info['variables']))) {
      $suggestion = TRUE;
    }

    if ($suggestion) {
      print "/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_$hook(array \$variables) {
  return _webform_theme_suggestions(\$variables, '$hook');
}

";
    }
  }
  print '</pre>';
  exit;
}

/******************************************************************************/
// Theme suggestions.
// Generate using _webform_devel_hook_theme_suggestions_generate();
/******************************************************************************/

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform(array $variables) {
  return _webform_theme_suggestions($variables, 'webform');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_confirmation(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_confirmation');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_preview(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_preview');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_navigation(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_navigation');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_form(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_form');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_submission_information(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_submission_information');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_element_base_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_element_base_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_element_base_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_element_base_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_container_base_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_container_base_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_container_base_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_container_base_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_email_message_html(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_email_message_html');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_email_message_text(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_email_message_text');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_progress(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_progress');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_progress_bar(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_progress_bar');
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function webform_theme_suggestions_webform_progress_tracker(array $variables) {
  return _webform_theme_suggestions($variables, 'webform_progress_tracker');
}
