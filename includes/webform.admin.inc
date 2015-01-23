<?php

use Drupal\Core\Url;
use Drupal\Core\Render\Element;

/**
 * Theme the components table of the Webform settings form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see \Drupal\webform\Form\WebformSettingsForm::buildForm().
 * @ingroup themeable
 */
function theme_webform_admin_settings_components_table($variables) {
  $form = $variables['form'];

  // Format the components into a table.
  $rows = array();
  foreach (Element::children($form) as $key) {
    $row = array();
    $row[] = $form[$key]['#title'];
    $row[] = $form[$key]['#description'];
    $form[$key]['#title'] = NULL;
    $form[$key]['#description'] = NULL;
    $row[] = array('data' => drupal_render($form[$key]), 'align' => 'center');
    $rows[] = $row;
  }
  $header = array(t('Name'), t('Description'), array('data' => t('Enabled'), 'class' => array('checkbox')));

  // Create the table inside the form.
  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return drupal_render($table);
}

/**
 * Create a comma-separate list of content types that are webform enabled.
 */
function webform_admin_type_list() {
  $webform_types = webform_node_types();
  $webform_type_list = '';
  $webform_type_count = count($webform_types);
  foreach ($webform_types as $n => $type) {
    $node_type = node_type_load($type);
    $webform_type_list .= _l($node_type->name, 'node/add/' . str_replace('_', '-', $type));
    if ($n + 1 < $webform_type_count) {
      $webform_type_list .= $webform_type_count == 2 ? ' ' : ', ';
    }
    if ($n + 2 == $webform_type_count) {
      $webform_type_list .= t('or') . ' ';
    }
  }
  return $webform_type_list;
}

/**
 * Generate a list of all webforms avaliable on this site.
 */
function theme_webform_admin_content($variables) {
  $nodes = $variables['nodes'];

  $header = array(
    t('Title'),
    array('data' => t('View'), 'colspan' => '4'),
    array('data' => t('Operations'), 'colspan' => '3')
  );

  $rows = array();
  if (!empty($nodes)) {
    foreach ($nodes as $node) {
      $rows[] = array(
        _l($node->title, 'node/' . $node->nid),
        _l(t('Submissions'), 'node/' . $node->nid . '/webform-results'),
        _l(t('Analysis'), 'node/' . $node->nid . '/webform-results/analysis'),
        _l(t('Table'), 'node/' . $node->nid . '/webform-results/table'),
        _l(t('Download'), 'node/' . $node->nid . '/webform-results/download'),
        node_access('update', $node) ? _l(t('Edit'), 'node/' . $node->nid . '/edit') : '',
        node_access('update', $node) ? _l(t('Components'), 'node/' . $node->nid . '/webform') : '',
        user_access('delete all webform submissions') ? _l(t('Clear'), 'node/' . $node->nid . '/webform-results/clear') : '',
      );
    }
  }

  if (empty($nodes)) {
    $webform_types = webform_node_types();
    if (empty($webform_types)) {
      $message = t('Webform is currently not enabled on any content types.') . ' ' . t('Visit the <a href="!url">Webform settings</a> page and enable Webform on at least one content type.', array('!url' => _url('admin/config/content/webform')));
    }
    else {
      $webform_type_list = webform_admin_type_list();
      $message = t('There are currently no webforms on your site. Create a !types piece of content.', array('!types' => $webform_type_list));
    }

    $rows[] = array(
      array('data' => $message, 'colspan' => 7),
    );
  }

  return _theme('table', array('header' => $header, 'rows' => $rows));
}
