<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformDateHelper;

/**
 * Implements hook_token_info().
 */
function webform_token_info() {
  $types['webform-authenticated-user'] = [
    'name' => t('Webform authenticated user'),
    'description' => t('Tokens related to the currently authenticated user.'),
    'type' => 'user',
  ];
  $types['webform-submission'] = [
    'name' => t('Webform submissions'),
    'description' => t('Tokens related to webform submissions.'),
    'needs-data' => 'webform-submission',
  ];
  $types['webform'] = [
    'name' => t('Webforms'),
    'description' => t('Tokens related to webforms.'),
    'needs-data' => 'webform',
  ];

  // Webform submission related variables.
  $webform_submission['serial'] = [
    'name' => t('Webform submission serial number'),
    'description' => t('The serial number of the webform submission .'),
  ];
  $webform_submission['sid'] = [
    'name' => t('Webform submission ID'),
    'description' => t('The ID of the webform submission .'),
  ];
  $webform_submission['uuid'] = [
    'name' => t('UUID'),
    'description' => t('The UUID of the webform submission.'),
  ];
  $webform_submission['token'] = [
    'name' => t('Token'),
    'description' => t('A secure token used to look up a submission.'),
  ];
  $webform_submission['ip-address'] = [
    'name' => t('IP address'),
    'description' => t('The IP address that was used when submitting the webform submission.'),
  ];
  $webform_submission['source-url'] = [
    'name' => t('Source URL'),
    'description' => t('The URL the user submitted the webform submission.'),
  ];
  $webform_submission['update-url'] = [
    'name' => t('Update URL'),
    'description' => t("The URL that can used to update the webform submission. The webform must be configurated to allow users to update a submission using a secure token."),
  ];
  $webform_submission['url'] = [
    'name' => t('URL'),
    'description' => t("The URL of the webform submission. Replace the '?' with the link template. Defaults to 'canonical' which displays the submission's data."),
    'dynamic' => TRUE,
  ];
  $webform_submission['user'] = [
    'name' => t('Submitter'),
    'description' => t('The user that submitted the webform submission.'),
    'type' => 'user',
  ];
  $webform_submission['langcode'] = [
    'name' => t('Langcode'),
    'description' => t('The language code of the webform submission.'),
    'type' => 'user',
  ];
  $webform_submission['language'] = [
    'name' => t('Language'),
    'description' => t('The language name of the the webform submission.'),
    'type' => 'user',
  ];
  $webform_submission['created'] = [
    'name' => t('Date created'),
    'description' => t('The date the webform submission was created.'),
    'type' => 'date',
  ];
  $webform_submission['completed'] = [
    'name' => t('Date completed'),
    'description' => t('The date the webform submission was completed.'),
    'type' => 'date',
  ];
  $webform_submission['changed'] = [
    'name' => t('Date changed'),
    'description' => t('The date the webform submission was most recently updated.'),
    'type' => 'date',
  ];
  $webform_submission['values'] = [
    'name' => t('Webform submission values'),
    'description' => t("Webform tokens from submitted data. Replace the '?' with the 'element_key' or 'element_key:format'.") . ' ' .
    t("The 'format' can be 'value', 'raw', or custom format specifically associated with the <a href=\":href\">element</a>.", [':href' => Url::fromRoute('webform.element_plugins')->toString()]) . ' ' .
    t("For example, to display the Contact webform's 'Subject' element's value you would use the [webform-submission:values:subject] token."),
    'dynamic' => TRUE,
  ];

  // Webform related variables.
  $webform['id'] = [
    'name' => t('Webform ID'),
    'description' => t('The ID of the webform.'),
  ];
  $webform['title'] = [
    'name' => t('title'),
    'description' => t('The title of the webform.'),
  ];

  $webform['description'] = [
    'name' => t('Description'),
    'description' => t('The administrative description of the webform.'),
  ];
  $webform['url'] = [
    'name' => t('URL'),
    'description' => t("The URL of the webform. Replace the '?' with the link template. Defaults to 'canonical' which displays the submission webform."),
    'dynamic' => TRUE,
  ];

  // Chained tokens for webform submissions.
  $webform_submission['webform'] = [
    'name' => t('Webform'),
    'description' => t('The webform that the webform submission belongs to.'),
    'type' => 'webform',
  ];
  $webform_submission['source-entity'] = [
    'name' => t('Source entity'),
    'description' => t("The (source) entity that the webform submission was submitted from.  Use [webform-submission:source-entity] to get the source entity's label or [webform-submission:source-entity:{field_name|property}] retrieve a source entity's field or property value."),
    'type' => 'entity',
  ];

  return [
    'types' => $types,
    'tokens' => [
      'webform-submission' => $webform_submission,
      'webform' => $webform,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function webform_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  $replacements = [];
  if ($type == 'webform-authenticated-user') {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $account = User::load(\Drupal::currentUser()->id());
      $bubbleable_metadata->addCacheContexts(['user']);
      $replacements += $token_service->generate('user', $tokens, ['user' => $account], $options, $bubbleable_metadata);
    }
    else {
      foreach ($replacements as $name => $token) {
        $replacements[$name] = '';
      }
    }
  }
  elseif ($type == 'webform-submission' && !empty($data['webform-submission'])) {

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform-submission'];

    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

    /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $form_elements = $webform_submission->getWebform()->getElementsInitializedAndFlattened();
    $submission_data = $webform_submission->getData();

    $submission_options = isset($data['webform-submission-options']) ? $data['webform-submission-options'] : [];

    foreach ($tokens as $name => $original) {
      if (strpos($name, 'values:') === 0) {
        // Parse [values:{name}:{format}] token with optional format.
        $keys = explode(':', $name);
        $key = $keys[1];
        $format = (isset($keys[2])) ? $keys[2] : NULL;
        if (isset($submission_data[$key]) && isset($form_elements[$key])) {
          $element = $form_elements[$key];
          // Apply $format to the element.
          if (isset($format)) {
            $element['#format'] = $format;
          }
          $format_method = (empty($submission_options['html'])) ? 'formatText' : 'formatHtml';
          $value = $element_manager->invokeMethod($format_method, $element, $submission_data[$key], $submission_options);
          // Note, tokens can't include CSS and JS libraries since they will
          // can be included in an email.
          $replacements[$original] = (is_array($value)) ? \Drupal::service('renderer')->renderPlain($value) : (string) $value;
        }
      }
      elseif (strpos($name, 'url:') === 0) {
        $key = str_replace('url:', '', $name);
        if ($webform_submission->id() && $webform_submission->hasLinkTemplate($key)) {
          $replacements[$original] = $webform_submission->toUrl($key, $url_options)->toString();
        }
      }
      else {
        switch ($name) {
          case 'created':
          case 'completed':
          case 'changed':
            $replacements[$original] = WebformDateHelper::format($webform_submission->{$name}->value, 'medium', '', NULL, $langcode);
            break;

          case 'langcode':
          case 'serial':
          case 'sid':
          case 'uuid':
            $replacements[$original] = $webform_submission->{$name}->value;
            break;

          case 'ip-address':
            $replacements[$original] = $webform_submission->remote_addr->value;
            break;

          case 'in_draft':
            $replacements[$original] = $webform_submission->in_draft->value ? t('Yes') : t('No');
            break;

          case 'language':
            $replacements[$original] = \Drupal::languageManager()->getLanguage($webform_submission->langcode->value)->getName();
            break;

          case 'source-url':
            $replacements[$original] = $webform_submission->getSourceUrl()->toString();
            break;

          case 'update-url':
            $replacements[$original] = $webform_submission->getTokenUrl()->toString();
            break;

          case 'source-entity':
            $source_entity = $webform_submission->getSourceEntity();
            $replacements[$original] = ($source_entity) ? $source_entity->label() : '';
            break;

          case 'token':
            $replacements[$original] = $webform_submission->getToken();
            break;

          case 'url':
            if ($webform_submission->id()) {
              $replacements[$original] = $webform_submission->toUrl('canonical', $url_options)->toString();
            }
            break;

          case 'user':
            /** @var \Drupal\Core\Session\AccountInterface $account */
            $account = $webform_submission->uid->entity;
            $replacements[$original] = $account->getDisplayName();
            break;

          case 'values':
            $submission_format = (!empty($submission_options['html'])) ? 'html' : 'text';
            $build = $view_builder->buildElements(
              $webform_submission->getWebform()->getElementsInitialized(),
              $submission_data,
              $submission_options,
              $submission_format
            );
            // Note, tokens can't include CSS and JS libraries since they will
            // can be included in an email.
            $replacements[$original] = \Drupal::service('renderer')->renderPlain($build);
            break;

          case 'webform':
            $webform = $webform_submission->getWebform();
            $bubbleable_metadata->addCacheableDependency($webform);
            $replacements[$original] = $webform->label();
            break;

        }
      }
    }

    // Chained token relationships.
    if ($webform_tokens = $token_service->findWithPrefix($tokens, 'webform')) {
      $webform = Webform::load($webform_submission->bundle());
      $replacements += $token_service->generate('webform', $webform_tokens, ['webform' => $webform], $options, $bubbleable_metadata);
    }
    if ($entity_tokens = $token_service->findWithPrefix($tokens, 'source-entity')) {
      if ($entity = $webform_submission->getSourceEntity()) {
        $replacements += $token_service->generate($entity->getEntityTypeId(), $entity_tokens, [$entity->getEntityTypeId() => $entity], $options, $bubbleable_metadata);
      }
    }
  }
  elseif ($type == 'webform' && !empty($data['webform'])) {

    $webform = $data['webform'];
    foreach ($tokens as $name => $original) {
      if (strpos($name, 'url:') === 0) {
        $key = str_replace('url:', '', $name);
        if ($webform->hasLinkTemplate($key)) {
          $replacements[$original] = $webform->toUrl($key, $url_options)->toString();
        }
      }
      else {
        switch ($name) {
          case 'id':
            $replacements[$original] = $webform->id();
            break;

          case 'title':
            $replacements[$original] = $webform->label();
            break;

          case 'description':
            $replacements[$original] = $webform->getDescription();
            break;

          case 'url':
            $replacements[$original] = $webform->toUrl('canonical', $url_options)->toString();
            break;
        }
      }
    }
  }

  return $replacements;
}
