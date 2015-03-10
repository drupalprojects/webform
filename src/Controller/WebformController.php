<?php

/**
 * @file
 * Contains \Drupal\webform\Controller\WebformController.
 */

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Webform routes.
 */
class WebformController extends ControllerBase {

  /**
   * Create an overview of webform content.
   */
  public function contentOverview() {
    // Determine whether views or hard-coded tables should be used for the webforms table.
    if (!webform_variable_get('webform_table')) {
      $view = views_get_view('webform_webforms');
      return $view->preview('default');
    }

    $query = db_select('webform', 'w');
    $query->join('node', 'n', 'w.nid = n.nid');
    $query->fields('n');
    $nodes = $query->execute()->fetchAllAssoc('nid');
    module_load_include('inc', 'webform', 'includes/webform.admin');
    return _theme('webform_admin_content', array('nodes' => $nodes));
  }

}
